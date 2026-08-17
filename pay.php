<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$userId = participant_session_user_id();
$event = get_event_by_id($eventId);

if (!$event || !$userId) {
    set_flash('error', __('flash.register_first'));
    redirect(base_url('events.php'));
}

$participant = get_user_event_registration($eventId, $userId);

if (!$participant || $participant['registration_status'] === 'waitlist') {
    set_flash('error', __('flash.registration_not_found'));
    redirect(base_url('event.php?id=' . $eventId));
}

if ($participant['payment_status'] === 'approved') {
    redirect(base_url('ticket.php?event_id=' . $eventId));
}

$pageTitle = __('pay_page.payment_for', ['title' => $event['title']]);
$errors = [];
$paymentDetails = resolve_event_payment_details($event);
$defaultMethod = get_default_payment_method_slug();
$isCardGateway = $defaultMethod === 'stripe'
    && payment_gateway_status_label(get_payment_gateway('stripe') ?? []) === 'ready';

$payment = db()->prepare('SELECT * FROM payments WHERE event_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
$payment->execute([$eventId, $userId]);
$paymentRow = $payment->fetch();
$amount = (float) ($paymentRow['amount'] ?? $event['ticket_price']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_redirect();

    if (($_POST['action'] ?? '') === 'stripe_checkout') {
        $paymentId = $paymentRow['id'] ?? null;

        if (!$paymentId) {
            $insert = db()->prepare(
                'INSERT INTO payments (event_id, user_id, amount, payment_method, payment_status, original_amount)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([$eventId, $userId, $amount, 'stripe', 'pending', $amount]);
            $paymentId = (int) db()->lastInsertId();
        }

        try {
            $session = create_stripe_checkout_session($event, ['id' => $paymentId, 'amount' => $amount, 'user_id' => $userId]);
            db()->prepare('UPDATE payments SET gateway_reference = ? WHERE id = ?')->execute([$session->id, $paymentId]);
            redirect($session->url);
        } catch (Throwable $e) {
            error_log('Stripe checkout session creation failed: ' . $e->getMessage());
            set_flash('error', __('pay_page.stripe_checkout_failed'));
            redirect(base_url('pay.php?event_id=' . $eventId));
        }
    }

    if (rate_limit_exceeded('slip_upload', client_ip(), 10, 300)) {
        $errors[] = __('validation.too_many_attempts');
    } elseif (!isset($_FILES['slip_image'])) {
        $errors[] = __('validation.upload_slip_required');
    } else {
        rate_limit_hit('slip_upload', client_ip());
        $path = save_upload($_FILES['slip_image'], 'slips', 'slip');

        if (!$path) {
            $errors[] = __('validation.invalid_file');
        } else {
            if ($paymentRow) {
                $update = db()->prepare(
                    'UPDATE payments SET slip_image = ?, payment_status = ? WHERE id = ?'
                );
                $update->execute([$path, 'pending', $paymentRow['id']]);
            } else {
                $insert = db()->prepare(
                    'INSERT INTO payments (event_id, user_id, amount, payment_method, payment_status, slip_image, original_amount)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([$eventId, $userId, $amount, $defaultMethod, 'pending', $path, $amount]);
            }

            set_flash('success', __('flash.payment_slip_uploaded'));
            redirect(base_url('pay.php?event_id=' . $eventId));
        }
    }
}

$payment->execute([$eventId, $userId]);
$paymentRow = $payment->fetch();

$promptpayId = $paymentDetails['promptpay_enabled'] ? ($paymentDetails['promptpay_number'] ?? '') : '';
$qrUrl = $promptpayId ? promptpay_qr_url($promptpayId, $amount) : null;
$showBank = $paymentDetails['bank_transfer_enabled'] && ($paymentDetails['bank_name'] ?? '') !== '';
$showSlipUpload = $paymentDetails['slip_upload_required'];

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <h1><?php _e('pay_page.title'); ?> <span class="gradient-text"><?php _e('pay_page.title_highlight'); ?></span></h1>
        <p><?php _e('pay_page.subtitle'); ?></p>
        <div class="payment-flow-steps">
            <span class="flow-step is-done" data-step="&#10003;"><?php _e('pay_page.step_register'); ?></span>
            <span class="flow-step is-active" data-step="2"><?php _e('pay_page.step_qr'); ?></span>
            <span class="flow-step" data-step="3"><?php _e('pay_page.step_scan'); ?></span>
            <span class="flow-step" data-step="4"><?php _e('pay_page.step_slip'); ?></span>
            <span class="flow-step" data-step="5"><?php _e('pay_page.step_verify'); ?></span>
            <span class="flow-step" data-step="6"><?php _e('pay_page.step_ticket'); ?></span>
        </div>
    </div>
</section>

<section class="section content-section" style="padding-top:0;">
    <div class="container two-col">
        <div class="card">
            <h3><?php _e('pay_page.instructions'); ?></h3>
            <div class="payment-amount-block">
                <span><?php _e('common.amount'); ?></span>
                <div class="gradient-text payment-amount"><?= e(format_currency($amount)) ?></div>
                <?php if ($paymentRow && (float) ($paymentRow['discount_amount'] ?? 0) > 0): ?>
                    <p class="form-help"><?= e(__('pay_page.discount_applied', ['amount' => format_currency((float) $paymentRow['discount_amount'])])) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($paymentDetails['instructions'] !== '' && !$isCardGateway): ?>
                <div class="alert alert-info"><?= nl2br(e($paymentDetails['instructions'])) ?></div>
            <?php endif; ?>

            <?php if ($isCardGateway): ?>
                <p class="form-help"><?php _e('pay_page.card_instructions'); ?></p>
            <?php elseif ($qrUrl): ?>
                <div class="promptpay-qr-block">
                    <h4><?php _e('pay_page.scan_qr'); ?></h4>
                    <img src="<?= e($qrUrl) ?>" alt="PromptPay QR" class="promptpay-qr">
                    <p><?php _e('pay_page.promptpay_number'); ?>: <strong><?= e($promptpayId) ?></strong></p>
                </div>
            <?php elseif ($promptpayId): ?>
                <p><?php _e('pay_page.promptpay_number'); ?>: <strong><?= e($promptpayId) ?></strong></p>
            <?php endif; ?>

            <?php if ($showBank && !$isCardGateway): ?>
                <div class="card card-glass bank-block">
                    <strong><?php _e('pay_page.bank_transfer'); ?></strong>
                    <p><?= e($paymentDetails['bank_name']) ?><br><?= e($paymentDetails['bank_account_name']) ?><br><?= e($paymentDetails['bank_account_number']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!$isCardGateway): ?>
            <p class="form-help future-note"><?php _e('pay_page.future_ocr'); ?></p>
            <?php endif; ?>
        </div>

        <div class="card">
            <?php if ($isCardGateway && $paymentRow && $paymentRow['payment_status'] === 'pending' && !empty($_GET['stripe'])): ?>
                <div class="alert alert-warning"><?php _e('pay_page.card_confirming'); ?></div>
            <?php elseif ($paymentRow && $paymentRow['payment_status'] === 'pending' && $paymentRow['slip_image']): ?>
                <div class="alert alert-warning"><?php _e('pay_page.pending_approval'); ?></div>
                <img src="<?= e(upload_url($paymentRow['slip_image'])) ?>" alt="Payment slip" class="slip-preview">
            <?php elseif ($paymentRow && $paymentRow['payment_status'] === 'rejected'): ?>
                <div class="alert alert-error"><?php _e('pay_page.payment_rejected'); ?></div>
            <?php endif; ?>

            <?php if (!$paymentRow || $paymentRow['payment_status'] !== 'approved'): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endforeach; ?>

                <?php if ($isCardGateway): ?>
                <form method="post" data-loading>
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <input type="hidden" name="action" value="stripe_checkout">
                    <button type="submit" class="btn btn-primary btn-block"><?php _e('pay_page.pay_with_card'); ?></button>
                </form>
                <?php elseif ($showSlipUpload): ?>
                <h3><?php _e('pay_page.upload_slip'); ?></h3>
                <form method="post" enctype="multipart/form-data" data-loading>
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <div class="form-group">
                        <?php
                        render_file_drop([
                            'id' => 'slip_image',
                            'name' => 'slip_image',
                            'label' => __('pay_page.slip_label'),
                            'variant' => 'slip',
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?php _e('pay_page.upload_btn'); ?></button>
                </form>
                <?php else: ?>
                <div class="alert alert-info"><?php _e('pay_page.awaiting_manual_confirm'); ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <a href="<?= base_url('event.php?id=' . $eventId) ?>" class="btn btn-outline btn-block" style="margin-top:1rem;"><?php _e('common.back_to_event'); ?></a>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

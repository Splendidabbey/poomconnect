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

$payment = db()->prepare('SELECT * FROM payments WHERE event_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
$payment->execute([$eventId, $userId]);
$paymentRow = $payment->fetch();
$amount = (float) ($paymentRow['amount'] ?? $event['ticket_price']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['slip_image'])) {
        $errors[] = __('validation.upload_slip_required');
    } else {
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
                $insert->execute([$eventId, $userId, $amount, 'promptpay', 'pending', $path, $amount]);
            }

            set_flash('success', __('flash.payment_slip_uploaded'));
            redirect(base_url('pay.php?event_id=' . $eventId));
        }
    }
}

$payment->execute([$eventId, $userId]);
$paymentRow = $payment->fetch();

$promptpayId = $event['promptpay_number'] ?? '';
$qrUrl = $promptpayId ? promptpay_qr_url($promptpayId, $amount) : null;

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <h1><?php _e('pay_page.title'); ?> <span class="gradient-text"><?php _e('pay_page.title_highlight'); ?></span></h1>
        <p><?php _e('pay_page.subtitle'); ?></p>
        <div class="payment-flow-steps">
            <span class="flow-step is-done"><?php _e('pay_page.step_register'); ?></span>
            <span class="flow-step is-active"><?php _e('pay_page.step_qr'); ?></span>
            <span class="flow-step"><?php _e('pay_page.step_scan'); ?></span>
            <span class="flow-step"><?php _e('pay_page.step_slip'); ?></span>
            <span class="flow-step"><?php _e('pay_page.step_verify'); ?></span>
            <span class="flow-step"><?php _e('pay_page.step_ticket'); ?></span>
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

            <?php if ($qrUrl): ?>
                <div class="promptpay-qr-block">
                    <h4><?php _e('pay_page.scan_qr'); ?></h4>
                    <img src="<?= e($qrUrl) ?>" alt="PromptPay QR" class="promptpay-qr">
                    <p><?php _e('pay_page.promptpay_number'); ?>: <strong><?= e($promptpayId) ?></strong></p>
                </div>
            <?php elseif ($promptpayId): ?>
                <p><?php _e('pay_page.promptpay_number'); ?>: <strong><?= e($promptpayId) ?></strong></p>
            <?php endif; ?>

            <?php if ($event['bank_name']): ?>
                <div class="card card-glass bank-block">
                    <strong><?php _e('pay_page.bank_transfer'); ?></strong>
                    <p><?= e($event['bank_name']) ?><br><?= e($event['bank_account_name']) ?><br><?= e($event['bank_account_number']) ?></p>
                </div>
            <?php endif; ?>

            <p class="form-help future-note"><?php _e('pay_page.future_ocr'); ?></p>
        </div>

        <div class="card">
            <?php if ($paymentRow && $paymentRow['payment_status'] === 'pending' && $paymentRow['slip_image']): ?>
                <div class="alert alert-warning"><?php _e('pay_page.pending_approval'); ?></div>
                <img src="<?= e(upload_url($paymentRow['slip_image'])) ?>" alt="Payment slip" class="slip-preview">
            <?php elseif ($paymentRow && $paymentRow['payment_status'] === 'rejected'): ?>
                <div class="alert alert-error"><?php _e('pay_page.payment_rejected'); ?></div>
            <?php endif; ?>

            <?php if (!$paymentRow || $paymentRow['payment_status'] !== 'approved'): ?>
                <h3><?php _e('pay_page.upload_slip'); ?></h3>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endforeach; ?>
                <form method="post" enctype="multipart/form-data" data-loading>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <div class="form-group">
                        <label for="slip_image"><?php _e('pay_page.slip_label'); ?></label>
                        <input type="file" id="slip_image" name="slip_image" class="input" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?php _e('pay_page.upload_btn'); ?></button>
                </form>
            <?php endif; ?>

            <a href="<?= base_url('event.php?id=' . $eventId) ?>" class="btn btn-outline btn-block" style="margin-top:1rem;"><?php _e('common.back_to_event'); ?></a>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

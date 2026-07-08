<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$userId = (int) ($_SESSION['participant_user_id'] ?? 0);
$event = get_event_by_id($eventId);

if (!$event || !$userId) {
    set_flash('error', 'Please register for the event first.');
    redirect(base_url('events.php'));
}

$participant = db()->prepare('SELECT * FROM event_participants WHERE event_id = ? AND user_id = ?');
$participant->execute([$eventId, $userId]);
$ep = $participant->fetch();

if (!$ep) {
    set_flash('error', 'Registration not found.');
    redirect(base_url('event.php?id=' . $eventId));
}

if ($ep['payment_status'] === 'approved') {
    redirect(base_url('ticket.php?event_id=' . $eventId));
}

$pageTitle = 'Payment — ' . $event['title'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['slip_image'])) {
        $errors[] = 'Please upload your payment slip.';
    } else {
        $path = save_upload($_FILES['slip_image'], 'slips', 'slip');

        if (!$path) {
            $errors[] = 'Invalid file. Please upload JPG, PNG, or WEBP under 5MB.';
        } else {
            $update = db()->prepare(
                'UPDATE payments SET slip_image = ?, payment_status = ? WHERE event_id = ? AND user_id = ? AND payment_status = ?'
            );
            $update->execute([$path, 'pending', $eventId, $userId, 'pending']);

            if ($update->rowCount() === 0) {
                $insert = db()->prepare(
                    'INSERT INTO payments (event_id, user_id, amount, payment_method, payment_status, slip_image)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([$eventId, $userId, $event['ticket_price'], 'promptpay', 'pending', $path]);
            }

            set_flash('success', 'Payment slip uploaded! Awaiting organizer approval.');
            redirect(base_url('pay.php?event_id=' . $eventId));
        }
    }
}

$payment = db()->prepare('SELECT * FROM payments WHERE event_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
$payment->execute([$eventId, $userId]);
$paymentRow = $payment->fetch();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <h1>Complete <span class="gradient-text">Payment</span></h1>
        <p>Transfer via PromptPay and upload your payment slip.</p>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container two-col">
        <div class="card">
            <h3 style="margin-bottom:1rem;">Payment Instructions</h3>
            <div style="display:grid;gap:1rem;">
                <div>
                    <span style="color:var(--text-soft);font-size:0.85rem;">Amount</span>
                    <div class="gradient-text" style="font-size:2rem;font-weight:800;"><?= e(format_currency((float) $event['ticket_price'])) ?></div>
                </div>
                <?php if ($event['promptpay_number']): ?>
                    <div>
                        <span style="color:var(--text-soft);font-size:0.85rem;">PromptPay Number</span>
                        <div style="font-size:1.25rem;font-weight:600;"><?= e($event['promptpay_number']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($event['bank_name']): ?>
                    <div class="card card-glass" style="padding:1rem;">
                        <strong>Bank Transfer</strong>
                        <p style="margin-top:0.5rem;font-size:0.9rem;">
                            <?= e($event['bank_name']) ?><br>
                            <?= e($event['bank_account_name']) ?><br>
                            <?= e($event['bank_account_number']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <?php if ($paymentRow && $paymentRow['payment_status'] === 'pending' && $paymentRow['slip_image']): ?>
                <div class="alert alert-warning">Your payment is pending approval.</div>
                <img src="<?= e(upload_url($paymentRow['slip_image'])) ?>" alt="Payment slip" class="slip-preview" style="margin-bottom:1rem;">
            <?php elseif ($paymentRow && $paymentRow['payment_status'] === 'rejected'): ?>
                <div class="alert alert-error">Your payment was rejected. Please upload a new slip.</div>
            <?php endif; ?>

            <?php if (!$paymentRow || $paymentRow['payment_status'] !== 'approved'): ?>
                <h3 style="margin-bottom:1rem;">Upload Payment Slip</h3>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endforeach; ?>

                <form method="post" enctype="multipart/form-data" data-loading>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <div class="form-group">
                        <label for="slip_image">Payment Slip (JPG, PNG, WEBP — max 5MB)</label>
                        <input type="file" id="slip_image" name="slip_image" class="input" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Upload Slip</button>
                </form>
            <?php endif; ?>

            <a href="<?= base_url('event.php?id=' . $eventId) ?>" class="btn btn-outline btn-block" style="margin-top:1rem;">← Back to Event</a>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$event = get_event_by_id($eventId);

if (!$event || !in_array($event['status'], ['published', 'live'], true)) {
    set_flash('error', 'Event not found or registration is closed.');
    redirect(base_url('events.php'));
}

$pageTitle = 'Register — ' . $event['title'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $lineId = trim($_POST['line_id'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if ($errors === []) {
        $user = create_or_get_participant_user($fullName, $email, $phone ?: null, $lineId ?: null);

        $countStmt = db()->prepare('SELECT COUNT(*) FROM event_participants WHERE event_id = ?');
        $countStmt->execute([$eventId]);
        $count = (int) $countStmt->fetchColumn();

        if ($count >= (int) $event['max_participants']) {
            $errors[] = 'This event is sold out.';
        } else {
            $registered = register_participant_for_event($eventId, (int) $user['id'], (float) $event['ticket_price']);

            if ($registered) {
                $_SESSION['participant_user_id'] = (int) $user['id'];
                $_SESSION['participant_event_id'] = $eventId;
                set_flash('success', 'Registration successful! Please complete your payment.');
                redirect(base_url('pay.php?event_id=' . $eventId));
            }

            $existing = db()->prepare('SELECT payment_status FROM event_participants WHERE event_id = ? AND user_id = ?');
            $existing->execute([$eventId, $user['id']]);
            $ep = $existing->fetch();

            if ($ep) {
                $_SESSION['participant_user_id'] = (int) $user['id'];
                $_SESSION['participant_event_id'] = $eventId;

                if ($ep['payment_status'] === 'approved') {
                    redirect(base_url('ticket.php?event_id=' . $eventId));
                }

                redirect(base_url('pay.php?event_id=' . $eventId));
            }

            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="auth-page">
    <div class="auth-card card">
        <span class="badge badge-purple"><?= e($event['title']) ?></span>
        <h1 style="margin-top:1rem;">Register for Event</h1>
        <p>Fill in your details to secure your spot.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" data-loading>
            <input type="hidden" name="event_id" value="<?= $eventId ?>">

            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="input" required value="<?= e($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="input" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="input" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="line_id">LINE ID</label>
                    <input type="text" id="line_id" name="line_id" class="input" value="<?= e($_POST['line_id'] ?? '') ?>">
                </div>
            </div>

            <div class="card card-glass" style="margin-bottom:1.5rem;padding:1rem;">
                <strong>Ticket Price:</strong> <?= e(format_currency((float) $event['ticket_price'])) ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Continue to Payment</button>
        </form>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

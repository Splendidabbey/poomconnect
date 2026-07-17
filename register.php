<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$inviteToken = trim($_GET['invite'] ?? '');
$event = get_event_by_id($eventId);

if (!$event || !in_array($event['status'], ['published', 'live'], true)) {
    set_flash('error', __('flash.registration_closed'));
    redirect(base_url('events.php'));
}

$invitation = $inviteToken !== '' ? get_invitation_by_token($inviteToken) : null;
$invitedBy = $invitation ? (int) $invitation['inviter_id'] : null;
$loggedInUser = is_logged_in() ? current_user() : null;
$spotsLeft = event_spots_available($eventId);
$soldOut = $spotsLeft <= 0;

$pageTitle = __('register_page.register_for', ['title' => $event['title']]);
$errors = [];
$couponDiscount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? ($loggedInUser['full_name'] ?? ''));
    $email = trim($_POST['email'] ?? ($loggedInUser['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? ($loggedInUser['phone'] ?? ''));
    $lineId = trim($_POST['line_id'] ?? ($loggedInUser['line_id'] ?? ''));
    $couponCode = trim($_POST['coupon_code'] ?? '');
    $inviteEmail = trim($_POST['invite_email'] ?? '');
    $action = $_POST['action'] ?? 'register';

    if ($action === 'invite' && $loggedInUser && !empty($event['invite_enabled'])) {
        if (!filter_var($inviteEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('validation.email_required');
        } else {
            $link = create_event_invitation($eventId, (int) $loggedInUser['id'], $inviteEmail);
            set_flash('success', __('register_page.invite_sent'));
            redirect(base_url('register.php?event_id=' . $eventId));
        }
    }

    if ($fullName === '') {
        $errors[] = __('validation.full_name_required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('validation.email_required');
    }

    if ($errors === []) {
        if ($loggedInUser && $loggedInUser['role'] === 'participant') {
            $user = $loggedInUser;
        } else {
            $user = create_or_get_participant_user($fullName, $email, $phone ?: null, $lineId ?: null);
        }

        $couponId = null;
        if ($couponCode !== '') {
            $coupon = get_coupon_by_code($couponCode, (int) $event['organization_id']);
            if ($coupon && coupon_valid($coupon, $eventId)) {
                $couponId = (int) $coupon['id'];
            } else {
                $errors[] = __('validation.invalid_coupon');
            }
        }

        if ($errors === []) {
            $result = join_event($eventId, (int) $user['id'], (float) $event['ticket_price'], $couponId, $invitedBy);

            if ($result['ok']) {
                set_participant_session((int) $user['id'], $eventId);
                if (!$loggedInUser || $loggedInUser['role'] === 'participant') {
                    if (!$loggedInUser) {
                        $_SESSION['user_id'] = (int) $user['id'];
                        $_SESSION['user_role'] = 'participant';
                    }
                }

                if (!empty($result['waitlist'])) {
                    set_flash('success', __('register_page.waitlist_success'));
                    redirect(base_url('event.php?id=' . $eventId));
                }
                if (!empty($result['free'])) {
                    set_flash('success', __('flash.registration_success'));
                    redirect(base_url('ticket.php?event_id=' . $eventId));
                }
                set_flash('success', __('flash.registration_success'));
                redirect(base_url('pay.php?event_id=' . $eventId));
            }

            $existing = get_user_event_registration($eventId, (int) $user['id']);
            if ($existing && $existing['registration_status'] !== 'cancelled') {
                set_participant_session((int) $user['id'], $eventId);
                if ($existing['payment_status'] === 'approved') {
                    redirect(base_url('ticket.php?event_id=' . $eventId));
                }
                if ($existing['registration_status'] === 'waitlist') {
                    set_flash('info', __('register_page.already_waitlist'));
                    redirect(base_url('event.php?id=' . $eventId));
                }
                redirect(base_url('pay.php?event_id=' . $eventId));
            }

            $errors[] = $result['error'] ?? __('validation.registration_failed');
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="auth-page">
    <div class="auth-card card auth-card-wide">
        <span class="badge badge-purple"><?= e($event['title']) ?></span>
        <h1 style="margin-top:1rem;"><?php _e('register_page.title'); ?></h1>
        <p><?php _e('register_page.subtitle'); ?></p>

        <?php if ($soldOut && !empty($event['waitlist_enabled'])): ?>
            <div class="alert alert-warning"><?php _e('register_page.waitlist_notice'); ?></div>
        <?php elseif ($soldOut): ?>
            <div class="alert alert-error"><?php _e('validation.sold_out'); ?></div>
        <?php else: ?>
            <div class="alert alert-success"><?= e(__('register_page.spots_left', ['count' => $spotsLeft])) ?></div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" data-loading>
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label for="full_name"><?php _e('register_page.full_name'); ?></label>
                <input type="text" id="full_name" name="full_name" class="input" required value="<?= e($_POST['full_name'] ?? ($loggedInUser['full_name'] ?? '')) ?>" <?= $loggedInUser ? 'readonly' : '' ?>>
            </div>

            <div class="form-group">
                <label for="email"><?= e(__('auth.email')) ?> *</label>
                <input type="email" id="email" name="email" class="input" required value="<?= e($_POST['email'] ?? ($loggedInUser['email'] ?? '')) ?>" <?= $loggedInUser ? 'readonly' : '' ?>>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone"><?php _e('register_page.phone'); ?></label>
                    <input type="tel" id="phone" name="phone" class="input" value="<?= e($_POST['phone'] ?? ($loggedInUser['phone'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label for="line_id"><?php _e('register_page.line_id'); ?></label>
                    <input type="text" id="line_id" name="line_id" class="input" value="<?= e($_POST['line_id'] ?? ($loggedInUser['line_id'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="coupon_code"><?php _e('register_page.coupon'); ?></label>
                <input type="text" id="coupon_code" name="coupon_code" class="input" value="<?= e($_POST['coupon_code'] ?? '') ?>" placeholder="POOM10">
            </div>

            <div class="card card-glass" style="margin-bottom:1.5rem;padding:1rem;">
                <strong><?php _e('register_page.ticket_price'); ?></strong> <?= e(format_currency((float) $event['ticket_price'])) ?>
            </div>

            <?php if (!$soldOut || !empty($event['waitlist_enabled'])): ?>
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <?= $soldOut ? e(__('register_page.join_waitlist')) : e(__('register_page.continue_payment')) ?>
                </button>
            <?php endif; ?>
        </form>

        <?php if ($loggedInUser && !empty($event['invite_enabled'])): ?>
        <hr style="margin:2rem 0;border-color:rgba(255,255,255,0.1);">
        <h3><?php _e('register_page.invite_friends'); ?></h3>
        <form method="post">
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <input type="hidden" name="action" value="invite">
            <div class="form-group">
                <label><?php _e('register_page.friend_email'); ?></label>
                <input type="email" name="invite_email" class="input" required>
            </div>
            <button type="submit" class="btn btn-outline"><?php _e('register_page.send_invite'); ?></button>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

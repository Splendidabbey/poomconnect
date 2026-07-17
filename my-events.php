<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_login(['participant']);

$userId = (int) current_user()['id'];
$pageTitle = __('my_events.title');
$registrations = get_user_registrations($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_event_id'])) {
    $eventId = (int) $_POST['cancel_event_id'];
    if (cancel_event_registration($eventId, $userId)) {
        set_flash('success', __('my_events.cancelled'));
    } else {
        set_flash('error', __('my_events.cancel_failed'));
    }
    redirect(base_url('my-events.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <h1><?php _e('my_events.title'); ?></h1>
        <p><?php _e('my_events.subtitle'); ?></p>
    </div>
</section>

<section class="section content-section">
    <div class="container">
        <div class="profile-quick-links">
            <a href="<?= base_url('profile.php') ?>" class="btn btn-outline btn-sm"><?php _e('nav.profile'); ?></a>
            <a href="<?= base_url('events.php') ?>" class="btn btn-primary btn-sm"><?php _e('my_events.browse'); ?></a>
        </div>

        <?php if ($registrations): ?>
            <div class="registrations-list">
                <?php foreach ($registrations as $reg): ?>
                    <article class="card registration-card">
                        <div class="registration-main">
                            <h3><a href="<?= e(event_url(['id' => $reg['event_id'], 'slug' => $reg['slug'] ?? ''])) ?>"><?= e($reg['title']) ?></a></h3>
                            <p><?= e(format_date($reg['event_date'])) ?> · <?= e(format_time($reg['start_time'])) ?> · <?= e($reg['city'] ?: ($reg['location'] ?? '')) ?></p>
                            <div class="registration-badges">
                                <span class="badge badge-purple"><?= e(status_label($reg['registration_status'])) ?></span>
                                <span class="badge badge-<?= $reg['payment_status'] === 'approved' ? 'success' : 'warning' ?>"><?= e(status_label($reg['payment_status'])) ?></span>
                            </div>
                        </div>
                        <div class="registration-actions">
                            <?php if ($reg['registration_status'] === 'registered' && $reg['payment_status'] === 'approved'): ?>
                                <a href="<?= base_url('ticket.php?event_id=' . (int) $reg['event_id']) ?>" class="btn btn-primary btn-sm"><?php _e('my_events.view_ticket'); ?></a>
                                <a href="<?= base_url('participant/live.php?event_id=' . (int) $reg['event_id']) ?>" class="btn btn-outline btn-sm"><?php _e('my_events.live'); ?></a>
                            <?php elseif ($reg['registration_status'] === 'registered' && in_array($reg['payment_status'], ['pending', 'rejected'], true)): ?>
                                <a href="<?= base_url('pay.php?event_id=' . (int) $reg['event_id']) ?>" class="btn btn-primary btn-sm"><?php _e('my_events.pay'); ?></a>
                            <?php elseif ($reg['registration_status'] === 'waitlist'): ?>
                                <span class="badge badge-outline"><?php _e('my_events.on_waitlist'); ?></span>
                            <?php endif; ?>
                            <?php if ($reg['registration_status'] !== 'cancelled'): ?>
                                <form method="post" onsubmit="return confirm(<?= json_encode(__('my_events.cancel_confirm')) ?>);">
                                    <input type="hidden" name="cancel_event_id" value="<?= (int) $reg['event_id'] ?>">
                                    <button type="submit" class="btn btn-outline btn-sm"><?php _e('my_events.cancel'); ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state card">
                <h3><?php _e('my_events.empty'); ?></h3>
                <a href="<?= base_url('events.php') ?>" class="btn btn-primary"><?php _e('my_events.browse'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

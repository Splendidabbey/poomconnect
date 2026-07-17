<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$eventId = (int) ($_GET['event_id'] ?? 0);
$pageTitle = __('organizer.waitlist_title');
$bodyClass = 'dashboard-page';

$eventsList = db()->prepare('SELECT id, title FROM events WHERE organization_id = ? ORDER BY event_date DESC');
$eventsList->execute([(int) $org['id']]);
$eventsList = $eventsList->fetchAll();

$waitlist = $eventId ? get_event_waitlist($eventId) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_event_id'])) {
    promote_waitlist((int) $_POST['promote_event_id']);
    set_flash('success', __('organizer.waitlist_promoted'));
    redirect(base_url('organizer/waitlist.php?event_id=' . (int) $_POST['promote_event_id']));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('organizer.waitlist_title'); ?></h1></div>
        <div class="card" style="margin-bottom:1.5rem;">
            <form method="get">
                <select name="event_id" class="select" onchange="this.form.submit()">
                    <option value=""><?php _e('organizer.select_event'); ?></option>
                    <?php foreach ($eventsList as $ev): ?>
                        <option value="<?= (int) $ev['id'] ?>" <?= $eventId === (int) $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php if ($eventId): ?>
        <div class="card">
            <?php if ($waitlist): ?>
                <table class="table">
                    <thead><tr><th><?php _e('organizer.name'); ?></th><th><?php _e('organizer.email'); ?></th><th><?php _e('common.date'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($waitlist as $w): ?>
                            <tr><td><?= e($w['full_name']) ?></td><td><?= e($w['email']) ?></td><td><?= e(format_date($w['created_at'])) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" style="margin-top:1rem;"><input type="hidden" name="promote_event_id" value="<?= $eventId ?>"><button class="btn btn-primary btn-sm"><?php _e('organizer.promote_next'); ?></button></form>
            <?php else: ?>
                <p><?php _e('organizer.no_waitlist'); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

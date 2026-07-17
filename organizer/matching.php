<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$eventId = (int) ($_GET['event_id'] ?? 0);
$org = get_organization_for_user((int) $user['id']);

$pageTitle = __('organizer.matching_title');
$bodyClass = 'dashboard-page';

$eventsList = [];
if ($org) {
    $stmt = db()->prepare('SELECT id, title FROM events WHERE organization_id = ? ORDER BY event_date DESC');
    $stmt->execute([(int) $org['id']]);
    $eventsList = $stmt->fetchAll();
}

$matches = $eventId ? get_event_matches($eventId) : [];

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('organizer.matching_title'); ?></h1><p><?php _e('organizer.matching_sub'); ?></p></div>
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
            <?php if ($matches): ?>
                <table class="table">
                    <thead><tr><th><?php _e('organizer.match_pair'); ?></th><th><?php _e('common.date'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($matches as $m): ?>
                            <tr><td><?= e($m['user_a_name']) ?> ↔ <?= e($m['user_b_name']) ?></td><td><?= e(format_date($m['created_at'])) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('organizer.no_matches'); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

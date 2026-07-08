<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
$orgId = $org ? (int) $org['id'] : 0;

$pageTitle = 'Events';
$bodyClass = 'dashboard-page';

$events = [];
if ($orgId) {
    $stmt = db()->prepare('SELECT * FROM events WHERE organization_id = ? ORDER BY event_date DESC');
    $stmt->execute([$orgId]);
    $events = $stmt->fetchAll();
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Events</h1>
                <p>Manage all your events</p>
            </div>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary">+ Create Event</a>
        </div>

        <div class="card">
            <?php if ($events): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                                <tr>
                                    <td><strong><?= e($ev['title']) ?></strong></td>
                                    <td><?= e(format_date($ev['event_date'])) ?></td>
                                    <td><?= e(format_currency((float) $ev['ticket_price'])) ?></td>
                                    <td><span class="badge badge-purple"><?= e(ucfirst($ev['status'])) ?></span></td>
                                    <td class="table-actions">
                                        <a href="<?= base_url('organizer/edit-event.php?id=' . (int) $ev['id']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                                        <a href="<?= base_url('organizer/participants.php?event_id=' . (int) $ev['id']) ?>" class="btn btn-ghost btn-sm">Participants</a>
                                        <a href="<?= base_url('organizer/payments.php?event_id=' . (int) $ev['id']) ?>" class="btn btn-ghost btn-sm">Payments</a>
                                        <a href="<?= base_url('organizer/live.php?event_id=' . (int) $ev['id']) ?>" class="btn btn-primary btn-sm">Live</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No events yet</h3>
                    <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary" style="margin-top:1rem;">Create Event</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

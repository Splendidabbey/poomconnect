<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = 'Events';
$bodyClass = 'dashboard-page';

$events = db()->query(
    'SELECT e.*, o.name AS organization_name FROM events e JOIN organizations o ON o.id = e.organization_id ORDER BY e.event_date DESC'
)->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1>All Events</h1></div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Event</th><th>Organization</th><th>Date</th><th>Status</th><th>Price</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td><?= e($ev['title']) ?></td>
                                <td><?= e($ev['organization_name']) ?></td>
                                <td><?= e(format_date($ev['event_date'])) ?></td>
                                <td><span class="badge badge-purple"><?= e($ev['status']) ?></span></td>
                                <td><?= e(format_currency((float) $ev['ticket_price'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

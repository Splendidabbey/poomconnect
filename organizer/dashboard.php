<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);

if (!$org && !is_admin()) {
    set_flash('error', 'No organization found for your account.');
    redirect(base_url('login.php'));
}

$orgId = $org ? (int) $org['id'] : 0;
$stats = $orgId ? organizer_stats($orgId) : [
    'total_events' => 0,
    'total_participants' => 0,
    'pending_payments' => 0,
    'total_revenue' => 0,
    'matches_made' => 0,
];

$pageTitle = 'Organizer Dashboard';
$bodyClass = 'dashboard-page';
$hideNav = false;

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, <?= e($user['full_name']) ?></p>
            </div>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary">+ Create Event</a>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card card">
                <div class="stat-card-label">Total Events</div>
                <div class="stat-card-value"><?= $stats['total_events'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Total Participants</div>
                <div class="stat-card-value"><?= $stats['total_participants'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Pending Payments</div>
                <div class="stat-card-value gradient-text"><?= $stats['pending_payments'] ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Total Revenue</div>
                <div class="stat-card-value" style="font-size:1.5rem;"><?= e(format_currency($stats['total_revenue'])) ?></div>
            </div>
            <div class="stat-card card">
                <div class="stat-card-label">Matches Made</div>
                <div class="stat-card-value"><?= $stats['matches_made'] ?></div>
            </div>
        </div>

        <?php if ($orgId): ?>
            <?php
            $recentEvents = db()->prepare(
                'SELECT * FROM events WHERE organization_id = ? ORDER BY event_date DESC LIMIT 5'
            );
            $recentEvents->execute([$orgId]);
            $events = $recentEvents->fetchAll();
            ?>
            <div class="card">
                <div class="dashboard-header" style="margin-bottom:1rem;">
                    <h3>Recent Events</h3>
                    <a href="<?= base_url('organizer/events.php') ?>" class="btn btn-outline btn-sm">View All</a>
                </div>
                <?php if ($events): ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $ev): ?>
                                    <tr>
                                        <td><?= e($ev['title']) ?></td>
                                        <td><?= e(format_date($ev['event_date'])) ?></td>
                                        <td><span class="badge badge-purple"><?= e(ucfirst($ev['status'])) ?></span></td>
                                        <td class="table-actions">
                                            <a href="<?= base_url('organizer/participants.php?event_id=' . (int) $ev['id']) ?>" class="btn btn-ghost btn-sm">Participants</a>
                                            <a href="<?= base_url('organizer/live.php?event_id=' . (int) $ev['id']) ?>" class="btn btn-primary btn-sm">Live</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No events yet. Create your first event!</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

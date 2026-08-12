<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);

if (!$org && !is_admin()) {
    set_flash('error', __('flash.no_organization'));
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

$pageTitle = __('organizer.dashboard');
$bodyClass = 'dashboard-page';
$hideNav = false;

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout has-navbar">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="admin-main">
        <section class="admin-hero">
            <div>
                <h1><?php _e('sidebar.dashboard'); ?></h1>
                <p><?= e(__('organizer.welcome_back', ['name' => $user['full_name']])) ?></p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary btn-sm"><?php _e('organizer.create_event'); ?></a>
            </div>
        </section>

        <div class="admin-kpi-grid">
            <article class="admin-kpi-card">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('organizer.total_events'); ?></div>
                        <div class="admin-kpi-value"><?= number_format($stats['total_events']) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                </div>
            </article>
            <article class="admin-kpi-card">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('organizer.total_participants'); ?></div>
                        <div class="admin-kpi-value"><?= number_format($stats['total_participants']) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="8" r="4"/><path d="M4 20v-1a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v1"/></svg>
                    </div>
                </div>
            </article>
            <article class="admin-kpi-card is-warning">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('organizer.pending_payments'); ?></div>
                        <div class="admin-kpi-value"><?= number_format($stats['pending_payments']) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    </div>
                </div>
            </article>
            <article class="admin-kpi-card is-revenue">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('organizer.total_revenue'); ?></div>
                        <div class="admin-kpi-value is-currency"><?= e(format_currency($stats['total_revenue'])) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                </div>
            </article>
            <article class="admin-kpi-card is-success">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('organizer.matches_made'); ?></div>
                        <div class="admin-kpi-value"><?= number_format($stats['matches_made']) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 21s-7-4.35-9.5-8.5C.5 8.5 3 5 6.5 5A5 5 0 0 1 12 8a5 5 0 0 1 5.5-3C21 5 23.5 8.5 21.5 12.5 19 16.65 12 21 12 21z"/></svg>
                    </div>
                </div>
            </article>
        </div>

        <?php if ($orgId): ?>
            <?php
            $recentEvents = db()->prepare(
                'SELECT * FROM events WHERE organization_id = ? ORDER BY event_date DESC LIMIT 5'
            );
            $recentEvents->execute([$orgId]);
            $events = $recentEvents->fetchAll();
            ?>
            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('organizer.recent_events'); ?></h3>
                    <a href="<?= base_url('organizer/events.php') ?>"><?php _e('common.view_all'); ?></a>
                </div>
                <?php if ($events): ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php _e('organizer.event'); ?></th>
                                    <th><?php _e('common.date'); ?></th>
                                    <th><?php _e('common.status'); ?></th>
                                    <th><?php _e('common.actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $ev): ?>
                                    <tr>
                                        <td><?= e($ev['title']) ?></td>
                                        <td><?= e(format_date($ev['event_date'])) ?></td>
                                        <td><span class="badge badge-purple"><?= e(status_label($ev['status'])) ?></span></td>
                                        <td class="table-actions">
                                            <a href="<?= base_url('organizer/participants.php?event_id=' . (int) $ev['id']) ?>" class="btn btn-ghost btn-sm"><?php _e('sidebar.participants'); ?></a>
                                            <a href="<?= base_url('organizer/live.php?event_id=' . (int) $ev['id']) ?>" class="btn btn-primary btn-sm"><?php _e('organizer.live'); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="admin-empty"><?php _e('organizer.no_events'); ?></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

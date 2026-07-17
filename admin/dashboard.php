<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$user = current_user();
$data = admin_dashboard_data();
$stats = $data['stats'];

$pageTitle = __('admin.dashboard');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;

$revenueChart = admin_chart_points(array_values($data['revenue_by_day']));
$registrationChart = admin_chart_points(array_values($data['registrations_by_day']));
$chartLabels = array_map(static fn(string $date): string => date('D', strtotime($date)), array_keys($data['revenue_by_day']));

$totalEventStatus = array_sum($data['event_statuses']) ?: 1;
$statusOrder = ['live', 'published', 'draft', 'paused', 'completed', 'cancelled'];

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>

    <div class="admin-main">
        <section class="admin-hero">
            <div>
                <h1><?php _e('admin.welcome', ['name' => $user['full_name']]); ?></h1>
                <p><?php _e('admin.platform_overview'); ?> · <?= e(format_date(date('Y-m-d'))) ?></p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?= base_url('admin/payments.php') ?>" class="btn btn-primary btn-sm"><?php _e('admin.review_payments'); ?></a>
                <a href="<?= base_url('admin/events.php') ?>" class="btn btn-outline btn-sm"><?php _e('admin.view_all_events'); ?></a>
            </div>
        </section>

        <div class="admin-kpi-grid">
            <article class="admin-kpi-card">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('admin.total_users'); ?></div>
                        <div class="admin-kpi-value"><?= number_format($stats['users']) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="8" r="4"/><path d="M4 20v-1a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v1"/></svg>
                    </div>
                </div>
                <div class="admin-kpi-meta"><?= number_format($stats['participants']) ?> <?php _e('admin.participants'); ?></div>
            </article>

            <article class="admin-kpi-card">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('admin.total_organizations'); ?></div>
                        <div class="admin-kpi-value"><?= number_format($stats['organizations']) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                    </div>
                </div>
                <div class="admin-kpi-meta"><?= number_format($stats['organizers']) ?> <?php _e('admin.organizers'); ?></div>
            </article>

            <article class="admin-kpi-card is-success">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('admin.total_events'); ?></div>
                        <div class="admin-kpi-value"><?= number_format($stats['events']) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                </div>
                <div class="admin-kpi-meta"><?= number_format($stats['live_events']) ?> <?php _e('admin.live_now'); ?></div>
            </article>

            <article class="admin-kpi-card is-revenue">
                <div class="admin-kpi-top">
                    <div>
                        <div class="admin-kpi-label"><?php _e('admin.total_revenue'); ?></div>
                        <div class="admin-kpi-value is-currency"><?= e(format_currency($stats['revenue'])) ?></div>
                    </div>
                    <div class="admin-kpi-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                </div>
                <div class="admin-kpi-meta"><?php _e('admin.this_week'); ?>: <?= e(format_currency($stats['revenue_week'])) ?></div>
            </article>
        </div>

        <div class="admin-secondary-grid">
            <div class="admin-mini-stat">
                <span><?php _e('admin.registrations'); ?></span>
                <strong><?= number_format($stats['registrations']) ?></strong>
            </div>
            <div class="admin-mini-stat">
                <span><?php _e('organizer.matches_made'); ?></span>
                <strong><?= number_format($stats['matches']) ?></strong>
            </div>
            <div class="admin-mini-stat">
                <span><?php _e('admin.tickets_issued'); ?></span>
                <strong><?= number_format($stats['tickets_issued']) ?></strong>
            </div>
            <div class="admin-mini-stat">
                <span><?php _e('admin.checkins'); ?></span>
                <strong><?= number_format($stats['checkins']) ?></strong>
            </div>
        </div>

        <div class="admin-charts-row">
            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('admin.revenue_chart'); ?></h3>
                    <span class="admin-kpi-pill"><?php _e('admin.last_7_days'); ?></span>
                </div>
                <div class="admin-chart-wrap">
                    <svg viewBox="0 0 320 130" preserveAspectRatio="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="adminRevenueFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6C35FF" stop-opacity="0.35"/>
                                <stop offset="100%" stop-color="#6C35FF" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path d="<?= e($revenueChart['area']) ?>" fill="url(#adminRevenueFill)"/>
                        <path d="<?= e($revenueChart['path']) ?>" fill="none" stroke="#FF2D8D" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="admin-chart-labels">
                    <?php foreach ($chartLabels as $label): ?>
                        <span><?= e($label) ?></span>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('admin.registrations_chart'); ?></h3>
                    <span class="admin-kpi-pill"><?php _e('admin.last_7_days'); ?></span>
                </div>
                <div class="admin-chart-wrap">
                    <svg viewBox="0 0 320 130" preserveAspectRatio="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="adminRegFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#FF2D8D" stop-opacity="0.28"/>
                                <stop offset="100%" stop-color="#FF2D8D" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path d="<?= e($registrationChart['area']) ?>" fill="url(#adminRegFill)"/>
                        <path d="<?= e($registrationChart['path']) ?>" fill="none" stroke="#6C35FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="admin-chart-labels">
                    <?php foreach ($chartLabels as $label): ?>
                        <span><?= e($label) ?></span>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('admin.event_status_breakdown'); ?></h3>
                </div>
                <div class="admin-status-list">
                    <?php foreach ($statusOrder as $status): ?>
                        <?php if (!isset($data['event_statuses'][$status])) continue; ?>
                        <?php $count = $data['event_statuses'][$status]; ?>
                        <?php $pct = round(($count / $totalEventStatus) * 100); ?>
                        <div class="admin-status-item">
                            <label><?= e(status_label($status)) ?></label>
                            <strong><?= $count ?></strong>
                            <div class="admin-status-bar"><span style="width: <?= $pct ?>%"></span></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($data['event_statuses'])): ?>
                        <div class="admin-empty"><?php _e('admin.no_events_yet'); ?></div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="admin-columns">
            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('admin.pending_payments_queue'); ?></h3>
                    <a href="<?= base_url('admin/payments.php') ?>"><?php _e('common.view_all'); ?></a>
                </div>
                <?php if ($data['pending_payments']): ?>
                    <div class="admin-queue-list">
                        <?php foreach ($data['pending_payments'] as $payment): ?>
                            <div class="admin-queue-item">
                                <div>
                                    <strong><?= e($payment['full_name']) ?></strong>
                                    <small><?= e($payment['event_title']) ?> · <?= e(format_date($payment['created_at'])) ?></small>
                                </div>
                                <span class="admin-queue-amount"><?= e(format_currency((float) $payment['amount'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="admin-empty"><?php _e('admin.no_pending_payments'); ?></div>
                <?php endif; ?>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('admin.top_organizations'); ?></h3>
                    <a href="<?= base_url('admin/organizations.php') ?>"><?php _e('common.view_all'); ?></a>
                </div>
                <?php if ($data['top_organizations']): ?>
                    <div class="admin-org-list">
                        <?php foreach ($data['top_organizations'] as $org): ?>
                            <div class="admin-org-item">
                                <div>
                                    <strong><?= e($org['name']) ?></strong>
                                    <span><?= (int) $org['event_count'] ?> <?php _e('admin.events_count'); ?></span>
                                </div>
                                <span class="admin-org-revenue"><?= e(format_currency((float) $org['revenue'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="admin-empty"><?php _e('admin.no_organizations'); ?></div>
                <?php endif; ?>
            </section>
        </div>

        <div class="admin-columns">
            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('admin.recent_events'); ?></h3>
                    <a href="<?= base_url('admin/events.php') ?>"><?php _e('admin.view_all_events'); ?></a>
                </div>
                <?php if ($data['recent_events']): ?>
                    <div class="admin-events-list">
                        <?php foreach ($data['recent_events'] as $event): ?>
                            <div class="admin-event-item">
                                <div>
                                    <strong><?= e($event['title']) ?></strong>
                                    <span><?= e($event['org_name']) ?> · <?= e(format_date($event['event_date'])) ?></span>
                                </div>
                                <div class="admin-event-meta">
                                    <span class="badge badge-purple"><?= e(status_label($event['status'])) ?></span>
                                    <span class="badge badge-muted"><?= (int) $event['participant_count'] ?> <?php _e('admin.attendees'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="admin-empty"><?php _e('admin.no_events_yet'); ?></div>
                <?php endif; ?>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3><?php _e('admin.recent_activity'); ?></h3>
                </div>
                <?php if ($data['recent_logs']): ?>
                    <div class="admin-activity-feed">
                        <?php foreach ($data['recent_logs'] as $log): ?>
                            <div class="admin-activity-item">
                                <span class="admin-activity-dot" aria-hidden="true"></span>
                                <div class="admin-activity-body">
                                    <strong><?= e($log['full_name']) ?> · <?= e(admin_action_label($log['action'])) ?></strong>
                                    <?php if (!empty($log['details'])): ?>
                                        <span><?= e($log['details']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <time class="admin-activity-time"><?= e(format_date($log['created_at'])) ?></time>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="admin-empty"><?php _e('admin.no_activity'); ?></div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

$orgCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($orgCurrent === 'index.php') {
    $orgCurrent = 'dashboard.php';
}
$orgUser = current_user();
?>
<div class="admin-mobile-bar">
    <a href="<?= base_url('organizer/dashboard.php') ?>" class="admin-mobile-brand">
        <img src="<?= brand_logo('sm') ?>" alt="<?= e(app_name()) ?>">
        <span><?php _e('sidebar.organizer'); ?></span>
    </a>
    <button type="button" class="admin-nav-toggle"
            aria-label="<?= e(__('nav.toggle_menu')) ?>"
            aria-expanded="false"
            aria-controls="admin-sidebar"
            data-admin-nav-toggle>
        <span></span><span></span><span></span>
    </button>
</div>
<div class="admin-sidebar-overlay" data-admin-nav-overlay hidden></div>
<aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar>
    <div class="admin-sidebar-top">
        <a href="<?= base_url('organizer/dashboard.php') ?>" class="admin-sidebar-brand">
            <img src="<?= brand_logo('sm') ?>" alt="<?= e(app_name()) ?>" class="admin-sidebar-logo">
            <span class="admin-sidebar-badge"><?php _e('sidebar.organizer'); ?></span>
        </a>
        <nav class="admin-sidebar-nav" aria-label="<?= e(__('nav.main_navigation')) ?>">
            <span class="admin-nav-group-label"><?php _e('sidebar.group_overview'); ?></span>
            <a href="<?= base_url('organizer/dashboard.php') ?>" class="admin-nav-link<?= $orgCurrent === 'dashboard.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                <?php _e('sidebar.dashboard'); ?>
            </a>
            <a href="<?= base_url('organizer/analytics.php') ?>" class="admin-nav-link<?= $orgCurrent === 'analytics.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
                <?php _e('sidebar.analytics'); ?>
            </a>

            <span class="admin-nav-group-label"><?php _e('sidebar.group_events'); ?></span>
            <a href="<?= base_url('organizer/events.php') ?>" class="admin-nav-link<?= in_array($orgCurrent, ['events.php', 'create-event.php', 'edit-event.php'], true) ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php _e('sidebar.events'); ?>
            </a>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="admin-nav-link<?= $orgCurrent === 'create-event.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                <?php _e('sidebar.create_event'); ?>
            </a>
            <a href="<?= base_url('organizer/participants.php') ?>" class="admin-nav-link<?= $orgCurrent === 'participants.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20v-1a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v1"/></svg>
                <?php _e('sidebar.participants'); ?>
            </a>
            <a href="<?= base_url('organizer/waitlist.php') ?>" class="admin-nav-link<?= $orgCurrent === 'waitlist.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?php _e('sidebar.waitlist'); ?>
            </a>
            <a href="<?= base_url('organizer/live.php') ?>" class="admin-nav-link<?= $orgCurrent === 'live.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><polygon points="13 2 3 14 11 14 10 22 21 9 13 9 13 2"/></svg>
                <?php _e('sidebar.live_event'); ?>
            </a>
            <a href="<?= base_url('organizer/templates.php') ?>" class="admin-nav-link<?= $orgCurrent === 'templates.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="9" x2="9" y2="21"/></svg>
                <?php _e('sidebar.templates'); ?>
            </a>

            <span class="admin-nav-group-label"><?php _e('sidebar.group_payments'); ?></span>
            <a href="<?= base_url('organizer/payments.php') ?>" class="admin-nav-link<?= $orgCurrent === 'payments.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <?php _e('sidebar.payments'); ?>
            </a>
            <a href="<?= base_url('organizer/coupons.php') ?>" class="admin-nav-link<?= $orgCurrent === 'coupons.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M20.59 13.41 12 22l-9-9V4a1 1 0 0 1 1-1h9z"/><circle cx="8" cy="8" r="1.5"/></svg>
                <?php _e('sidebar.coupons'); ?>
            </a>
            <a href="<?= base_url('organizer/subscription.php') ?>" class="admin-nav-link<?= $orgCurrent === 'subscription.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <?php _e('sidebar.subscription'); ?>
            </a>

            <span class="admin-nav-group-label"><?php _e('sidebar.group_marketing'); ?></span>
            <a href="<?= base_url('organizer/marketing.php') ?>" class="admin-nav-link<?= in_array($orgCurrent, ['marketing.php', 'email-campaigns.php'], true) ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 11v2a1 1 0 0 0 1 1h3l5 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M16 8a5 5 0 0 1 0 8"/></svg>
                <?php _e('sidebar.marketing'); ?>
            </a>
            <a href="<?= base_url('organizer/referrals.php') ?>" class="admin-nav-link<?= $orgCurrent === 'referrals.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                <?php _e('sidebar.referrals'); ?>
            </a>
            <a href="<?= base_url('organizer/branding.php') ?>" class="admin-nav-link<?= $orgCurrent === 'branding.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                <?php _e('sidebar.branding'); ?>
            </a>
            <a href="<?= base_url('organizer/matching.php') ?>" class="admin-nav-link<?= $orgCurrent === 'matching.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 21s-7-4.35-9.5-8.5C.5 8.5 3 5 6.5 5A5 5 0 0 1 12 8a5 5 0 0 1 5.5-3C21 5 23.5 8.5 21.5 12.5 19 16.65 12 21 12 21z"/></svg>
                <?php _e('sidebar.matching'); ?>
            </a>
            <a href="<?= base_url('organizer/community.php') ?>" class="admin-nav-link<?= $orgCurrent === 'community.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="8" cy="9" r="3"/><circle cx="17" cy="9" r="3"/><path d="M2 20v-1a5 5 0 0 1 5-5h2a5 5 0 0 1 5 5v1"/><path d="M14 14h1a5 5 0 0 1 5 5v1"/></svg>
                <?php _e('sidebar.community'); ?>
            </a>
            <a href="<?= base_url('organizer/safety.php') ?>" class="admin-nav-link<?= $orgCurrent === 'safety.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?php _e('sidebar.safety'); ?>
            </a>
            <a href="<?= base_url('organizer/marketplace-apply.php') ?>" class="admin-nav-link<?= $orgCurrent === 'marketplace-apply.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <?php _e('sidebar.marketplace'); ?>
            </a>

            <span class="admin-nav-group-label"><?php _e('sidebar.group_settings'); ?></span>
            <a href="<?= base_url('organizer/settings.php') ?>" class="admin-nav-link<?= $orgCurrent === 'settings.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                <?php _e('sidebar.settings'); ?>
            </a>
        </nav>
    </div>
    <div class="admin-sidebar-bottom">
        <a href="<?= base_url('index.php') ?>" class="admin-nav-link admin-nav-link-muted">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>
            <?php _e('sidebar.view_site'); ?>
        </a>
        <div class="admin-user-card">
            <img src="<?= default_avatar($orgUser['full_name'] ?? 'Organizer') ?>" alt="" class="admin-user-avatar">
            <div class="admin-user-meta">
                <strong><?= e($orgUser['full_name'] ?? '') ?></strong>
                <span><?php _e('sidebar.organizer'); ?></span>
            </div>
        </div>
        <a href="<?= base_url('logout.php') ?>" class="admin-nav-link admin-nav-link-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <?php _e('nav.logout'); ?>
        </a>
    </div>
</aside>

<?php

declare(strict_types=1);

$adminCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
$adminUser = current_user();
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-top">
        <a href="<?= base_url('admin/dashboard.php') ?>" class="admin-sidebar-brand">
            <img src="<?= brand_logo('sm') ?>" alt="<?= e(app_name()) ?>" class="admin-sidebar-logo">
            <span class="admin-sidebar-badge"><?php _e('sidebar.admin'); ?></span>
        </a>
        <nav class="admin-sidebar-nav">
            <a href="<?= base_url('admin/dashboard.php') ?>" class="admin-nav-link<?= $adminCurrent === 'dashboard.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                <?php _e('sidebar.dashboard'); ?>
            </a>
            <a href="<?= base_url('admin/users.php') ?>" class="admin-nav-link<?= $adminCurrent === 'users.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20v-1a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v1"/></svg>
                <?php _e('sidebar.users'); ?>
            </a>
            <a href="<?= base_url('admin/organizations.php') ?>" class="admin-nav-link<?= $adminCurrent === 'organizations.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/></svg>
                <?php _e('sidebar.organizations'); ?>
            </a>
            <a href="<?= base_url('admin/events.php') ?>" class="admin-nav-link<?= $adminCurrent === 'events.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php _e('sidebar.events'); ?>
            </a>
            <a href="<?= base_url('admin/blog.php') ?>" class="admin-nav-link<?= in_array($adminCurrent, ['blog.php', 'blog-edit.php'], true) ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                <?php _e('sidebar.blog'); ?>
            </a>
            <a href="<?= base_url('admin/categories.php') ?>" class="admin-nav-link<?= $adminCurrent === 'categories.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h14"/></svg>
                <?php _e('sidebar.categories'); ?>
            </a>
            <a href="<?= base_url('admin/payments.php') ?>" class="admin-nav-link<?= $adminCurrent === 'payments.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <?php _e('sidebar.payments'); ?>
            </a>
            <a href="<?= base_url('admin/subscriptions.php') ?>" class="admin-nav-link<?= $adminCurrent === 'subscriptions.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <?php _e('admin.subscriptions'); ?>
            </a>
            <a href="<?= base_url('admin/moderation.php') ?>" class="admin-nav-link<?= $adminCurrent === 'moderation.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?php _e('admin.moderation'); ?>
            </a>
            <a href="<?= base_url('admin/marketplace.php') ?>" class="admin-nav-link<?= $adminCurrent === 'marketplace.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <?php _e('admin.marketplace'); ?>
            </a>
            <a href="<?= base_url('admin/reports.php') ?>" class="admin-nav-link<?= $adminCurrent === 'reports.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?php _e('admin.reports'); ?>
            </a>
            <a href="<?= base_url('admin/support-tickets.php') ?>" class="admin-nav-link<?= $adminCurrent === 'support-tickets.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <?php _e('admin.support_tickets'); ?>
            </a>
            <a href="<?= base_url('admin/ai-usage.php') ?>" class="admin-nav-link<?= $adminCurrent === 'ai-usage.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 2a4 4 0 0 1 4 4v1h2a2 2 0 0 1 2 2v3H4V9a2 2 0 0 1 2-2h2V6a4 4 0 0 1 4-4z"/></svg>
                <?php _e('admin.ai_usage'); ?>
            </a>
            <a href="<?= base_url('admin/system-logs.php') ?>" class="admin-nav-link<?= $adminCurrent === 'system-logs.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
                <?php _e('admin.system_logs'); ?>
            </a>
            <a href="<?= base_url('admin/countries.php') ?>" class="admin-nav-link<?= $adminCurrent === 'countries.php' ? ' is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <?php _e('admin.countries'); ?>
            </a>
            <a href="<?= base_url('admin/settings.php') ?>" class="admin-nav-link<?= $adminCurrent === 'settings.php' ? ' is-active' : '' ?>">
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
            <img src="<?= default_avatar($adminUser['full_name'] ?? 'Admin') ?>" alt="" class="admin-user-avatar">
            <div class="admin-user-meta">
                <strong><?= e($adminUser['full_name'] ?? '') ?></strong>
                <span><?php _e('sidebar.admin'); ?></span>
            </div>
        </div>
        <a href="<?= base_url('logout.php') ?>" class="admin-nav-link admin-nav-link-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <?php _e('nav.logout'); ?>
        </a>
    </div>
</aside>

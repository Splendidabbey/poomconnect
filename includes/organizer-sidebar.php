<?php

declare(strict_types=1);

$orgCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<aside class="sidebar">
    <a href="<?= base_url('organizer/dashboard.php') ?>" class="logo sidebar-logo" style="margin-bottom:1.5rem;">
        <img src="<?= brand_app_icon() ?>" alt="<?= e(app_name()) ?>" class="logo-app-icon">
        <span class="sidebar-logo-label"><?php _e('sidebar.organizer'); ?></span>
    </a>
    <nav class="sidebar-nav">
        <a href="<?= base_url('organizer/dashboard.php') ?>" class="<?= $orgCurrent === 'dashboard.php' ? 'active' : '' ?>">📊 <?php _e('sidebar.dashboard'); ?></a>
        <a href="<?= base_url('organizer/events.php') ?>" class="<?= in_array($orgCurrent, ['events.php', 'create-event.php', 'edit-event.php'], true) ? 'active' : '' ?>">📅 <?php _e('sidebar.events'); ?></a>
        <a href="<?= base_url('organizer/create-event.php') ?>" class="<?= $orgCurrent === 'create-event.php' ? 'active' : '' ?>">➕ <?php _e('sidebar.create_event'); ?></a>
        <a href="<?= base_url('organizer/blog.php') ?>" class="<?= in_array($orgCurrent, ['blog.php', 'blog-create.php', 'blog-edit.php'], true) ? 'active' : '' ?>">📝 <?php _e('sidebar.blog'); ?></a>
        <a href="<?= base_url('organizer/participants.php') ?>" class="<?= $orgCurrent === 'participants.php' ? 'active' : '' ?>">👥 <?php _e('sidebar.participants'); ?></a>
        <a href="<?= base_url('organizer/payments.php') ?>" class="<?= $orgCurrent === 'payments.php' ? 'active' : '' ?>">💳 <?php _e('sidebar.payments'); ?></a>
        <a href="<?= base_url('organizer/waitlist.php') ?>" class="<?= $orgCurrent === 'waitlist.php' ? 'active' : '' ?>">⏳ <?php _e('sidebar.waitlist'); ?></a>
        <a href="<?= base_url('organizer/coupons.php') ?>" class="<?= $orgCurrent === 'coupons.php' ? 'active' : '' ?>">🏷️ <?php _e('sidebar.coupons'); ?></a>
        <a href="<?= base_url('organizer/analytics.php') ?>" class="<?= $orgCurrent === 'analytics.php' ? 'active' : '' ?>">📈 <?php _e('sidebar.analytics'); ?></a>
        <a href="<?= base_url('organizer/matching.php') ?>" class="<?= $orgCurrent === 'matching.php' ? 'active' : '' ?>">✨ <?php _e('sidebar.matching'); ?></a>
        <a href="<?= base_url('organizer/referrals.php') ?>" class="<?= $orgCurrent === 'referrals.php' ? 'active' : '' ?>">🔗 <?php _e('sidebar.referrals'); ?></a>
        <a href="<?= base_url('organizer/marketing.php') ?>" class="<?= in_array($orgCurrent, ['marketing.php', 'email-campaigns.php'], true) ? 'active' : '' ?>">📣 <?php _e('sidebar.marketing'); ?></a>
        <a href="<?= base_url('organizer/branding.php') ?>" class="<?= $orgCurrent === 'branding.php' ? 'active' : '' ?>">🎨 <?php _e('sidebar.branding'); ?></a>
        <a href="<?= base_url('organizer/subscription.php') ?>" class="<?= $orgCurrent === 'subscription.php' ? 'active' : '' ?>">💎 <?php _e('sidebar.subscription'); ?></a>
        <a href="<?= base_url('organizer/templates.php') ?>" class="<?= $orgCurrent === 'templates.php' ? 'active' : '' ?>">📋 <?php _e('sidebar.templates'); ?></a>
        <a href="<?= base_url('organizer/marketplace-apply.php') ?>" class="<?= $orgCurrent === 'marketplace-apply.php' ? 'active' : '' ?>">🏪 <?php _e('sidebar.marketplace'); ?></a>
        <a href="<?= base_url('organizer/community.php') ?>" class="<?= $orgCurrent === 'community.php' ? 'active' : '' ?>">👥 <?php _e('sidebar.community'); ?></a>
        <a href="<?= base_url('organizer/safety.php') ?>" class="<?= $orgCurrent === 'safety.php' ? 'active' : '' ?>">🛡️ <?php _e('sidebar.safety'); ?></a>
        <a href="<?= base_url('organizer/live.php') ?>" class="<?= $orgCurrent === 'live.php' ? 'active' : '' ?>">⚡ <?php _e('sidebar.live_event'); ?></a>
        <a href="<?= base_url('organizer/settings.php') ?>" class="<?= $orgCurrent === 'settings.php' ? 'active' : '' ?>">⚙️ <?php _e('sidebar.settings'); ?></a>
        <a href="<?= base_url('logout.php') ?>">🚪 <?php _e('nav.logout'); ?></a>
    </nav>
</aside>

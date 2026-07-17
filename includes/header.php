<?php

declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = app_name();
}

if (!isset($bodyClass)) {
    $bodyClass = '';
}

if (!isset($hideNav)) {
    $hideNav = false;
}

if (!isset($pageMeta)) {
    $pageMeta = page_meta(['title' => $pageTitle]);
}

$isLanding = str_contains($bodyClass, 'page-landing');
$isAdminPage = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
$currentPath = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$currentLocale = current_locale();
$contentPages = ['events.php', 'event.php', 'blog.php', 'article.php', 'profile.php', 'my-events.php', 'signup.php', 'register.php', 'pay.php', 'ticket.php'];
$platformPages = array_merge($contentPages, ['login.php', 'signup.php', 'chat.php', 'chat-thread.php', 'notifications.php', 'loyalty.php']);
$loadContentCss = in_array($currentPath, $contentPages, true);
$loadPlatformCss = in_array($currentPath, $platformPages, true) || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/participant/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/organizer/');
$navUnreadCount = is_logged_in() ? unread_notification_count((int) current_user()['id']) : 0;
$tenantOrg = current_tenant();
$brandName = $tenantOrg ? org_brand_name($tenantOrg) : app_name();
$brandLogoMd = $tenantOrg && !empty($tenantOrg['logo']) ? org_logo_url($tenantOrg) : brand_logo('md');
$brandLogoSm = $tenantOrg && !empty($tenantOrg['logo']) ? org_logo_url($tenantOrg) : brand_logo('sm');
$brandLogoNav = $tenantOrg && !empty($tenantOrg['logo']) ? org_logo_url($tenantOrg) : brand_logo('nav');
?>
<!DOCTYPE html>
<html lang="<?= e($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageMeta['description'] ?? __('app.meta_description')) ?>">
    <?php if (!empty($pageMeta['url'])): ?>
    <link rel="canonical" href="<?= e($pageMeta['url']) ?>">
    <?php endif; ?>
    <meta property="og:title" content="<?= e(($pageMeta['title'] ?? $pageTitle) . ' | ' . app_name()) ?>">
    <meta property="og:description" content="<?= e($pageMeta['description'] ?? __('app.meta_description')) ?>">
    <meta property="og:type" content="<?= e($pageMeta['type'] ?? 'website') ?>">
    <?php if (!empty($pageMeta['url'])): ?>
    <meta property="og:url" content="<?= e($pageMeta['url']) ?>">
    <?php endif; ?>
    <?php if (!empty($pageMeta['image'])): ?>
    <meta property="og:image" content="<?= e($pageMeta['image']) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <title><?= e($pageMeta['title'] ?? $pageTitle) ?> | <?= e($brandName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php foreach (locale_google_fonts() as $fontUrl): ?>
    <link href="<?= e($fontUrl) ?>" rel="stylesheet">
    <?php endforeach; ?>
    <link rel="icon" type="image/png" sizes="48x48" href="<?= brand_favicon() ?>">
    <link rel="apple-touch-icon" href="<?= brand_app_icon('512') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <?php if ($isLanding): ?>
    <link rel="stylesheet" href="<?= asset_url('css/landing.css') ?>">
    <?php endif; ?>
    <?php if ($isAdminPage): ?>
    <link rel="stylesheet" href="<?= asset_url('css/admin.css') ?>">
    <?php endif; ?>
    <?php if ($loadContentCss): ?>
    <link rel="stylesheet" href="<?= asset_url('css/content.css') ?>">
    <?php endif; ?>
    <?php if ($loadPlatformCss): ?>
    <link rel="stylesheet" href="<?= asset_url('css/platform.css') ?>">
    <?php endif; ?>
    <?php if ($tenantOrg): ?>
    <style><?= org_theme_css($tenantOrg) ?></style>
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?><?= locale_font_class() ? ' ' . e(locale_font_class()) : '' ?><?= $tenantOrg ? ' tenant-branded' : '' ?>">
<?php if (!$hideNav): ?>
<header class="navbar<?= $isLanding ? ' navbar-landing' : '' ?>">
    <div class="container navbar-layout<?= $isLanding ? ' navbar-layout-landing' : '' ?>">
        <?php if ($isLanding): ?>
        <a href="<?= $tenantOrg ? e(org_public_url($tenantOrg)) : base_url('index.php') ?>" class="logo nav-brand">
            <img src="<?= e($brandLogoNav) ?>" alt="<?= e($brandName) ?>" class="logo-image logo-nav">
        </a>

        <nav class="nav-links nav-links-center nav-landing-desktop" aria-label="<?= e(__('nav.events')) ?>">
            <?php require APP_ROOT . '/includes/nav-links-landing.php'; ?>
        </nav>

        <div class="nav-end nav-landing-desktop">
            <?php $navUtilitiesPlacement = 'bar'; require APP_ROOT . '/includes/nav-utilities.php'; ?>
            <div class="nav-auth">
                <?php require APP_ROOT . '/includes/nav-auth-landing.php'; ?>
            </div>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary btn-sm nav-cta"><?php _e('nav.host_event'); ?></a>
        </div>

        <div class="navbar-shell navbar-landing-mobile">
            <?php $navUtilitiesPlacement = 'shell'; require APP_ROOT . '/includes/nav-utilities.php'; ?>
            <button class="nav-toggle" type="button"
                    aria-label="<?= e(__('nav.toggle_menu')) ?>"
                    aria-expanded="false"
                    aria-controls="nav-mobile-drawer"
                    data-nav-toggle>
                <span></span><span></span><span></span>
            </button>
        </div>

        <div class="nav-mobile-drawer nav-landing-drawer" id="nav-mobile-drawer" data-nav-drawer>
            <nav class="nav-links nav-links-center" data-nav-menu>
                <?php require APP_ROOT . '/includes/nav-links-landing.php'; ?>
            </nav>
            <div class="nav-actions" data-nav-actions>
                <div class="nav-auth nav-auth-stack">
                    <?php require APP_ROOT . '/includes/nav-auth-landing.php'; ?>
                </div>
                <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary nav-cta nav-cta-block"><?php _e('nav.host_event'); ?></a>
            </div>
        </div>
        <?php else: ?>
        <div class="navbar-shell">
            <a href="<?= $tenantOrg ? e(org_public_url($tenantOrg)) : base_url('index.php') ?>" class="logo">
                <img src="<?= e($brandLogoMd) ?>" alt="<?= e($brandName) ?>" class="logo-image logo-image-md">
                <img src="<?= e($brandLogoSm) ?>" alt="<?= e($brandName) ?>" class="logo-image logo-image-sm">
            </a>

            <?php $navUtilitiesPlacement = 'shell'; require APP_ROOT . '/includes/nav-utilities.php'; ?>

            <button class="nav-toggle" type="button"
                    aria-label="<?= e(__('nav.toggle_menu')) ?>"
                    aria-expanded="false"
                    aria-controls="nav-mobile-drawer"
                    data-nav-toggle>
                <span></span><span></span><span></span>
            </button>
        </div>

        <div class="nav-mobile-drawer" id="nav-mobile-drawer" data-nav-drawer>
            <nav class="nav-links" data-nav-menu>
                <a href="<?= base_url('events.php') ?>" class="<?= $currentPath === 'events.php' ? 'active' : '' ?>"><?php _e('nav.events'); ?></a>
                <a href="<?= base_url('blog.php') ?>" class="<?= in_array($currentPath, ['blog.php', 'article.php'], true) ? 'active' : '' ?>"><?php _e('nav.blog'); ?></a>
                <a href="<?= base_url('index.php#how-it-works') ?>"><?php _e('nav.how_it_works'); ?></a>
                <a href="<?= base_url('index.php#organizers') ?>"><?php _e('nav.for_organizers'); ?></a>
                <a href="<?= base_url('index.php#pricing') ?>"><?php _e('nav.pricing'); ?></a>
                <?php if (is_logged_in()): ?>
                    <?php if (is_admin()): ?>
                        <a href="<?= base_url('admin/dashboard.php') ?>"><?php _e('nav.admin'); ?></a>
                    <?php elseif (is_organizer()): ?>
                        <a href="<?= base_url('organizer/dashboard.php') ?>"><?php _e('nav.dashboard'); ?></a>
                    <?php else: ?>
                        <a href="<?= base_url('my-events.php') ?>"><?php _e('nav.my_events'); ?></a>
                        <a href="<?= base_url('chat.php') ?>"><?php _e('nav.chat'); ?></a>
                        <a href="<?= base_url('community/groups.php') ?>"><?php _e('sidebar.community'); ?></a>
                        <a href="<?= base_url('notifications.php') ?>" class="nav-link-badge">
                            <?php _e('nav.notifications'); ?>
                            <?php if ($navUnreadCount > 0): ?>
                                <span class="nav-badge" aria-label="<?= e(__('notify.unread_count', ['count' => $navUnreadCount])) ?>"><?= $navUnreadCount > 99 ? '99+' : $navUnreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= base_url('loyalty.php') ?>"><?php _e('nav.loyalty'); ?></a>
                        <a href="<?= base_url('profile.php') ?>"><?php _e('nav.profile'); ?></a>
                    <?php endif; ?>
                    <a href="<?= base_url('logout.php') ?>"><?php _e('nav.logout'); ?></a>
                <?php else: ?>
                    <a href="<?= base_url('login.php') ?>"><?php _e('nav.login'); ?></a>
                    <a href="<?= base_url('signup.php') ?>"><?php _e('nav.signup'); ?></a>
                <?php endif; ?>
            </nav>
            <div class="nav-actions nav-actions-inner" data-nav-actions>
                <?php $navUtilitiesPlacement = 'drawer'; require APP_ROOT . '/includes/nav-utilities.php'; ?>
                <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary btn-sm nav-cta"><?php _e('nav.host_event'); ?></a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="nav-overlay" data-nav-overlay hidden></div>
</header>
<?php endif; ?>
<main class="main-content">

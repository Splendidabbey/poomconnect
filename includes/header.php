<?php

declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}

if (!isset($bodyClass)) {
    $bodyClass = '';
}

if (!isset($hideNav)) {
    $hideNav = false;
}

$isLanding = str_contains($bodyClass, 'page-landing');
$currentPath = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Poom Connect — AI powered real time matching events for dating, networking, and communities. Meet. Connect. Belong.">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= brand_favicon() ?>">
    <link rel="apple-touch-icon" href="<?= brand_app_icon('512') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <?php if ($isLanding): ?>
    <link rel="stylesheet" href="<?= asset_url('css/landing.css') ?>">
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<?php if (!$hideNav): ?>
<header class="navbar<?= $isLanding ? ' navbar-landing' : '' ?>">
    <div class="container navbar-inner<?= $isLanding ? ' navbar-inner-landing' : '' ?>">
        <a href="<?= base_url('index.php') ?>" class="logo">
            <?php if ($isLanding): ?>
            <img src="<?= brand_logo('nav') ?>" alt="Poom Connect" class="logo-image logo-nav">
            <?php else: ?>
            <img src="<?= brand_logo('md') ?>" alt="Poom Connect" class="logo-image logo-image-md">
            <img src="<?= brand_logo('sm') ?>" alt="Poom Connect" class="logo-image logo-image-sm">
            <?php endif; ?>
        </a>

        <button class="nav-toggle" aria-label="Toggle menu" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <?php if ($isLanding): ?>
        <nav class="nav-links nav-links-center" data-nav-menu>
            <a href="<?= base_url('events.php') ?>">Events</a>
            <a href="<?= base_url('index.php#how-it-works') ?>">How It Works</a>
            <a href="<?= base_url('index.php#organizers') ?>">For Organizers</a>
            <a href="<?= base_url('index.php#pricing') ?>">Pricing</a>
            <a href="#" class="nav-dropdown">Resources <span class="nav-chevron">▾</span></a>
        </nav>
        <div class="nav-actions" data-nav-actions>
            <?php if (is_logged_in()): ?>
                <a href="<?= is_admin() ? base_url('admin/dashboard.php') : base_url('organizer/dashboard.php') ?>" class="nav-login">Dashboard</a>
                <a href="<?= base_url('logout.php') ?>" class="nav-login">Logout</a>
            <?php else: ?>
                <a href="<?= base_url('login.php') ?>" class="nav-login">Log in</a>
            <?php endif; ?>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary btn-sm">Host Your Event</a>
        </div>
        <?php else: ?>
        <nav class="nav-links" data-nav-menu>
            <a href="<?= base_url('events.php') ?>" class="<?= $currentPath === 'events.php' ? 'active' : '' ?>">Events</a>
            <a href="<?= base_url('index.php#how-it-works') ?>">How It Works</a>
            <a href="<?= base_url('index.php#organizers') ?>">For Organizers</a>
            <a href="<?= base_url('index.php#pricing') ?>">Pricing</a>
            <?php if (is_logged_in()): ?>
                <?php if (is_admin()): ?>
                    <a href="<?= base_url('admin/dashboard.php') ?>">Admin</a>
                <?php else: ?>
                    <a href="<?= base_url('organizer/dashboard.php') ?>">Dashboard</a>
                <?php endif; ?>
                <a href="<?= base_url('logout.php') ?>">Logout</a>
            <?php else: ?>
                <a href="<?= base_url('login.php') ?>">Login</a>
            <?php endif; ?>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary btn-sm nav-cta">Host Your Event</a>
        </nav>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>
<main class="main-content">

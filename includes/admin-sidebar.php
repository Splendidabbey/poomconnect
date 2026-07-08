<?php

declare(strict_types=1);

$adminCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<aside class="sidebar">
    <a href="<?= base_url('admin/dashboard.php') ?>" class="logo sidebar-logo" style="margin-bottom:1.5rem;">
        <img src="<?= brand_app_icon() ?>" alt="Poom Connect" class="logo-app-icon">
        <span class="sidebar-logo-label">Admin</span>
    </a>
    <nav class="sidebar-nav">
        <a href="<?= base_url('admin/dashboard.php') ?>" class="<?= $adminCurrent === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="<?= base_url('admin/users.php') ?>" class="<?= $adminCurrent === 'users.php' ? 'active' : '' ?>">👤 Users</a>
        <a href="<?= base_url('admin/organizations.php') ?>" class="<?= $adminCurrent === 'organizations.php' ? 'active' : '' ?>">🏢 Organizations</a>
        <a href="<?= base_url('admin/events.php') ?>" class="<?= $adminCurrent === 'events.php' ? 'active' : '' ?>">📅 Events</a>
        <a href="<?= base_url('admin/payments.php') ?>" class="<?= $adminCurrent === 'payments.php' ? 'active' : '' ?>">💳 Payments</a>
        <a href="<?= base_url('admin/settings.php') ?>" class="<?= $adminCurrent === 'settings.php' ? 'active' : '' ?>">⚙️ Settings</a>
        <a href="<?= base_url('logout.php') ?>">🚪 Logout</a>
    </nav>
</aside>

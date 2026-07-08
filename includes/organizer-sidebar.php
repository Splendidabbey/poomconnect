<?php

declare(strict_types=1);

$orgCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<aside class="sidebar">
    <a href="<?= base_url('organizer/dashboard.php') ?>" class="logo sidebar-logo" style="margin-bottom:1.5rem;">
        <img src="<?= brand_app_icon() ?>" alt="Poom Connect" class="logo-app-icon">
        <span class="sidebar-logo-label">Organizer</span>
    </a>
    <nav class="sidebar-nav">
        <a href="<?= base_url('organizer/dashboard.php') ?>" class="<?= $orgCurrent === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="<?= base_url('organizer/events.php') ?>" class="<?= in_array($orgCurrent, ['events.php', 'create-event.php', 'edit-event.php'], true) ? 'active' : '' ?>">📅 Events</a>
        <a href="<?= base_url('organizer/create-event.php') ?>" class="<?= $orgCurrent === 'create-event.php' ? 'active' : '' ?>">➕ Create Event</a>
        <a href="<?= base_url('organizer/participants.php') ?>" class="<?= $orgCurrent === 'participants.php' ? 'active' : '' ?>">👥 Participants</a>
        <a href="<?= base_url('organizer/payments.php') ?>" class="<?= $orgCurrent === 'payments.php' ? 'active' : '' ?>">💳 Payments</a>
        <a href="<?= base_url('organizer/live.php') ?>" class="<?= $orgCurrent === 'live.php' ? 'active' : '' ?>">⚡ Live Event</a>
        <a href="<?= base_url('organizer/settings.php') ?>" class="<?= $orgCurrent === 'settings.php' ? 'active' : '' ?>">⚙️ Settings</a>
        <a href="<?= base_url('logout.php') ?>">🚪 Logout</a>
    </nav>
</aside>

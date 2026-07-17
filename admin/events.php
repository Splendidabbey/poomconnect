<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.events_title');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;

$events = db()->query(
    'SELECT e.*, o.name AS organization_name FROM events e JOIN organizations o ON o.id = e.organization_id ORDER BY e.event_date DESC'
)->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header">
            <h1><?php _e('admin.all_events'); ?></h1>
            <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-primary btn-sm"><?php _e('sidebar.create_event'); ?></a>
        </div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php _e('organizer.event'); ?></th>
                            <th><?php _e('admin.organization'); ?></th>
                            <th><?php _e('event_form.city'); ?></th>
                            <th><?php _e('common.date'); ?></th>
                            <th><?php _e('common.status'); ?></th>
                            <th><?php _e('common.amount'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td><?= e($ev['title']) ?></td>
                                <td><?= e($ev['organization_name']) ?></td>
                                <td><?= e($ev['city'] ?? '—') ?></td>
                                <td><?= e(format_date($ev['event_date'])) ?></td>
                                <td><span class="badge badge-purple"><?= e(status_label($ev['status'])) ?></span></td>
                                <td><?= e(format_currency((float) $ev['ticket_price'])) ?></td>
                                <td class="table-actions">
                                    <a href="<?= base_url('organizer/edit-event.php?id=' . (int) $ev['id']) ?>" class="btn btn-outline btn-sm"><?php _e('common.edit'); ?></a>
                                    <a href="<?= e(event_url($ev)) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener"><?php _e('events_page.view_event'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

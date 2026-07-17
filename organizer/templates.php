<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$org = get_organization_for_user((int) current_user()['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('templates.title');
$templates = get_event_templates((int) $org['id']);

require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('templates.title'); ?></h1><p><?php _e('templates.sub'); ?></p></div>
        <div class="templates-grid">
            <?php foreach ($templates as $tpl): ?>
                <div class="card template-card">
                    <h3><?= e($tpl['name']) ?></h3>
                    <p><?= e($tpl['description'] ?? '') ?></p>
                    <span class="badge badge-purple"><?= e(event_type_label($tpl['event_type'])) ?></span>
                    <a href="<?= base_url('organizer/create-event.php?template_id=' . (int) $tpl['id']) ?>" class="btn btn-primary btn-sm"><?php _e('templates.use'); ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

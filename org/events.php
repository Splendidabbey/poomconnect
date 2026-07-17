<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

$slug = trim($_GET['org'] ?? '');
$org = $slug ? (get_organization_by_slug($slug) ?: null) : current_tenant();
if (!$org) {
    redirect(base_url('index.php'));
}
set_tenant_context((int) $org['id']);

$pageTitle = __('tenant.events') . ' — ' . $org['name'];
$events = org_events((int) $org['id'], 50);

require_once APP_ROOT . '/includes/header.php';
?>

<section class="page-header"><div class="container"><h1><?= e($org['name']) ?></h1><p><?php _e('tenant.all_events'); ?></p></div></section>
<section class="section content-section">
    <div class="container">
        <?php if ($events): ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <a href="<?= base_url('event.php?slug=' . urlencode($event['slug'] ?? '')) ?>" class="card event-card">
                        <h3><?= e($event['title']) ?></h3>
                        <p><?= e(format_date($event['event_date'])) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state card"><p><?php _e('tenant.no_events'); ?></p></div>
        <?php endif; ?>
        <a href="<?= base_url('org/index.php?org=' . urlencode($org['slug'])) ?>" class="btn btn-outline" style="margin-top:1rem;">← <?= e(__('tenant.back_home')) ?></a>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

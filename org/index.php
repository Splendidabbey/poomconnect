<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

$slug = trim($_GET['org'] ?? '');
$org = $slug ? get_organization_by_slug($slug) : current_tenant();

if (!$org && $slug) {
    $stmt = db()->prepare('SELECT * FROM organizations WHERE subdomain = ? AND status = ? LIMIT 1');
    $stmt->execute([$slug, 'active']);
    $org = $stmt->fetch() ?: null;
}

if (!$org || ($org['status'] ?? '') !== 'active') {
    set_flash('error', __('tenant.not_found'));
    redirect(base_url('index.php'));
}

set_tenant_context((int) $org['id']);

$pageTitle = $org['landing_headline'] ?: $org['name'];
$bodyClass = 'page-tenant-landing';
$events = org_events((int) $org['id'], 6);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="tenant-hero section">
    <div class="container tenant-hero-inner">
        <?php if (!empty($org['logo'])): ?>
            <img src="<?= e(org_logo_url($org)) ?>" alt="<?= e($org['name']) ?>" class="tenant-hero-logo">
        <?php endif; ?>
        <h1><?= e($org['landing_headline'] ?: $org['name']) ?></h1>
        <?php if (!empty($org['landing_body'])): ?>
            <p class="tenant-hero-body"><?= nl2br(e($org['landing_body'])) ?></p>
        <?php endif; ?>
        <?php if (org_has_safe_badge((int) $org['id'])): ?>
            <span class="badge badge-success">🛡️ <?= e(__('safety.safe_badge')) ?></span>
        <?php endif; ?>
        <div class="tenant-hero-actions">
            <a href="<?= base_url('org/events.php?org=' . urlencode($org['slug'])) ?>" class="btn btn-primary btn-lg">
                <?= e($org['landing_cta'] ?: __('tenant.view_events')) ?>
            </a>
            <a href="<?= base_url('org/profile.php?org=' . urlencode($org['slug'])) ?>" class="btn btn-outline btn-lg"><?php _e('tenant.organizer_profile'); ?></a>
        </div>
    </div>
</section>

<?php if ($events): ?>
<section class="section content-section">
    <div class="container">
        <h2><?php _e('tenant.upcoming_events'); ?></h2>
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
                <a href="<?= base_url('event.php?slug=' . urlencode($event['slug'] ?? '')) ?>" class="card event-card">
                    <h3><?= e($event['title']) ?></h3>
                    <p><?= e(format_date($event['event_date'])) ?> · <?= e(format_currency((float) $event['ticket_price'])) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

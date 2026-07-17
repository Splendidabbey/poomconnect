<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

$slug = trim($_GET['org'] ?? '');
$row = $slug ? get_organization_by_slug($slug) : current_tenant();
if (!$row) {
    $stmt = db()->prepare('SELECT * FROM organizations WHERE subdomain = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch() ?: null;
}
$org = $row ? public_organizer_profile((int) $row['id']) : null;
if (!$org) {
    $org = ($row && !empty($row['profile_public'])) ? $row : null;
}

if (!$org || empty($org['profile_public'])) {
    set_flash('error', __('tenant.profile_private'));
    redirect(base_url('index.php'));
}

set_tenant_context((int) $org['id']);
$pageTitle = $org['name'];
$events = org_events((int) $org['id'], 12);
$ratings = org_ratings((int) $org['id'], 5);
$userId = current_user_id();
$following = $userId ? is_following_organizer((int) $org['id'], $userId) : false;

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="section content-section">
    <div class="container org-profile-page">
        <div class="org-profile-header card">
            <?php if (!empty($org['logo'])): ?>
                <img src="<?= e(org_logo_url($org)) ?>" alt="" class="org-profile-logo">
            <?php endif; ?>
            <div>
                <h1><?= e($org['name']) ?></h1>
                <p><?php _e('tenant.hosted_by', ['name' => $org['owner_name'] ?? '']); ?></p>
                <?php if ((float) ($org['rating_avg'] ?? 0) > 0): ?>
                    <p>⭐ <?= e(number_format((float) $org['rating_avg'], 1)) ?> (<?= (int) $org['rating_count'] ?>)</p>
                <?php endif; ?>
                <p><?= org_follower_count((int) $org['id']) ?> <?php _e('community.followers'); ?></p>
                <?php if (org_has_safe_badge((int) $org['id'])): ?>
                    <span class="badge badge-success">🛡️ <?= e(__('safety.safe_badge')) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($userId && is_participant()): ?>
                <form method="post" action="<?= base_url('api/follow-organizer.php') ?>">
                    <input type="hidden" name="org_id" value="<?= (int) $org['id'] ?>">
                    <input type="hidden" name="action" value="<?= $following ? 'unfollow' : 'follow' ?>">
                    <button type="submit" class="btn btn-primary"><?= $following ? e(__('community.unfollow')) : e(__('community.follow')) ?></button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($org['profile_bio'])): ?>
            <div class="card"><p><?= nl2br(e($org['profile_bio'])) ?></p></div>
        <?php endif; ?>

        <?php if ($events): ?>
            <h2><?php _e('tenant.upcoming_events'); ?></h2>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <a href="<?= base_url('event.php?slug=' . urlencode($event['slug'] ?? '')) ?>" class="card"><?= e($event['title']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($ratings): ?>
            <h2><?php _e('marketplace.reviews'); ?></h2>
            <?php foreach ($ratings as $r): ?>
                <div class="card"><strong><?= e($r['full_name']) ?></strong> — <?= (int) $r['rating'] ?>/5<p><?= e($r['review'] ?? '') ?></p></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

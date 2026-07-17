<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_login(['participant', 'organizer']);

$userId = (int) current_user()['id'];
$pageTitle = __('marketplace.apply_title');
$existing = user_host_application($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $types = $_POST['event_types'] ?? [];
    if (!is_array($types)) {
        $types = [];
    }
    submit_host_application(
        $userId,
        trim($_POST['organization_name'] ?? ''),
        $types,
        trim($_POST['experience'] ?? ''),
        trim($_POST['website'] ?? '') ?: null
    );
    set_flash('success', __('marketplace.apply_sent'));
    redirect(base_url('organizer/marketplace-apply.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header"><div class="container"><h1><?php _e('marketplace.apply_title'); ?></h1></div></section>
<section class="section content-section">
    <div class="container" style="max-width:640px;">
        <?php if ($existing): ?>
            <div class="card">
                <p><?php _e('marketplace.application_status'); ?>: <strong><?= e(status_label($existing['status'])) ?></strong></p>
                <p><?= e($existing['organization_name']) ?></p>
            </div>
        <?php else: ?>
            <form method="post" class="card">
                <div class="form-group"><label><?php _e('marketplace.org_name'); ?></label><input class="input" name="organization_name" required></div>
                <div class="form-group"><label><?php _e('marketplace.event_types'); ?></label>
                    <?php foreach (platform_event_types() as $type): ?>
                        <label class="checkbox-label"><input type="checkbox" name="event_types[]" value="<?= e($type) ?>"> <?= e(event_type_label($type)) ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group"><label><?php _e('marketplace.experience'); ?></label><textarea class="textarea" name="experience" rows="4"></textarea></div>
                <div class="form-group"><label><?php _e('marketplace.website'); ?></label><input class="input" name="website" type="url"></div>
                <button type="submit" class="btn btn-primary"><?php _e('marketplace.submit'); ?></button>
            </form>
        <?php endif; ?>

        <h2 style="margin-top:2rem;"><?php _e('marketplace.featured'); ?></h2>
        <div class="marketplace-grid">
            <?php foreach (featured_organizers(6) as $fo): ?>
                <a href="<?= base_url('org/profile.php?org=' . urlencode($fo['slug'])) ?>" class="card">
                    <strong><?= e($fo['name']) ?></strong>
                    <?php if ((float) $fo['rating_avg'] > 0): ?><span>⭐ <?= e(number_format((float) $fo['rating_avg'], 1)) ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

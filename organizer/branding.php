<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
if (!$org) {
    redirect(base_url('organizer/settings.php'));
}

$pageTitle = __('tenant.branding_title');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'primary_color' => trim($_POST['primary_color'] ?? '#6C35FF'),
        'secondary_color' => trim($_POST['secondary_color'] ?? '#FF2D8D'),
        'custom_domain' => strtolower(trim($_POST['custom_domain'] ?? '')),
        'subdomain' => strtolower(trim($_POST['subdomain'] ?? '')),
        'landing_headline' => trim($_POST['landing_headline'] ?? ''),
        'landing_body' => trim($_POST['landing_body'] ?? ''),
        'landing_cta' => trim($_POST['landing_cta'] ?? ''),
        'profile_bio' => trim($_POST['profile_bio'] ?? ''),
        'profile_public' => !empty($_POST['profile_public']),
        'country' => trim($_POST['country'] ?? ''),
        'logo' => $org['logo'] ?? null,
    ];

    if ($data['name'] === '') {
        $errors[] = __('validation.organization_name_required');
    }

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newLogo = save_upload($_FILES['logo'], 'logos', 'logo');
        if ($newLogo) {
            $data['logo'] = $newLogo;
        }
    }

    if ($errors === []) {
        save_org_branding((int) $org['id'], $data);
        set_flash('success', __('flash.settings_saved'));
        redirect(base_url('organizer/branding.php'));
    }
}

$org = get_organization_by_id((int) $org['id']);
$countries = platform_countries();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('tenant.branding_title'); ?></h1><p><?php _e('tenant.branding_sub'); ?></p></div>
        <div class="card" style="max-width:720px;">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group"><label><?php _e('organizer.org_name'); ?></label><input class="input" name="name" required value="<?= e($org['name']) ?>"></div>
                <div class="form-group"><label>Logo</label>
                    <?php if (!empty($org['logo'])): ?><img src="<?= e(upload_url($org['logo'])) ?>" style="max-width:80px;display:block;margin-bottom:0.5rem;"><?php endif; ?>
                    <input type="file" name="logo" class="input" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="form-row">
                    <div class="form-group"><label><?php _e('tenant.primary_color'); ?></label><input type="color" name="primary_color" value="<?= e($org['primary_color'] ?? '#6C35FF') ?>"></div>
                    <div class="form-group"><label><?php _e('tenant.secondary_color'); ?></label><input type="color" name="secondary_color" value="<?= e($org['secondary_color'] ?? '#FF2D8D') ?>"></div>
                </div>
                <div class="form-group"><label><?php _e('tenant.custom_domain'); ?></label><input class="input" name="custom_domain" placeholder="events.lesla.com" value="<?= e($org['custom_domain'] ?? '') ?>"></div>
                <div class="form-group"><label><?php _e('tenant.subdomain'); ?></label><input class="input" name="subdomain" placeholder="lesla" value="<?= e($org['subdomain'] ?? '') ?>"></div>
                <div class="form-group"><label><?php _e('tenant.landing_headline'); ?></label><input class="input" name="landing_headline" value="<?= e($org['landing_headline'] ?? '') ?>"></div>
                <div class="form-group"><label><?php _e('tenant.landing_body'); ?></label><textarea class="textarea" name="landing_body" rows="4"><?= e($org['landing_body'] ?? '') ?></textarea></div>
                <div class="form-group"><label><?php _e('tenant.landing_cta'); ?></label><input class="input" name="landing_cta" value="<?= e($org['landing_cta'] ?? '') ?>"></div>
                <div class="form-group"><label><?php _e('tenant.profile_bio'); ?></label><textarea class="textarea" name="profile_bio" rows="3"><?= e($org['profile_bio'] ?? '') ?></textarea></div>
                <div class="form-group"><label><?php _e('tenant.country'); ?></label>
                    <select name="country" class="select"><option value="">—</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['code']) ?>" <?= ($org['country'] ?? '') === $c['code'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label class="checkbox-label"><input type="checkbox" name="profile_public" value="1" <?= !empty($org['profile_public']) ? 'checked' : '' ?>> <?php _e('tenant.profile_public'); ?></label>
                <p class="form-help"><?php _e('tenant.landing_url'); ?>: <a href="<?= e(org_public_url($org)) ?>" target="_blank"><?= e(org_public_url($org)) ?></a></p>
                <button type="submit" class="btn btn-primary"><?php _e('organizer.save_settings'); ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

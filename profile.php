<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_login(['participant', 'organizer', 'admin', 'super_admin']);

$userId = (int) current_user()['id'];
$profile = get_user_profile($userId);
$tab = $_GET['tab'] ?? 'profile';
$pageTitle = __('profile.title');
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['form'] ?? '') === 'safety') {
        save_emergency_contact($userId, trim($_POST['emergency_contact_name'] ?? ''), trim($_POST['emergency_contact_phone'] ?? ''));
        set_flash('success', __('safety.emergency_saved'));
        redirect(base_url('profile.php?tab=safety'));
    }

    if (($_POST['form'] ?? '') === 'compatibility') {
        save_compatibility_profile($userId, [
            'interests' => preg_split('/\s*,\s*/', $_POST['comp_interests'] ?? '', -1, PREG_SPLIT_NO_EMPTY),
            'personality_type' => $_POST['personality_type'] ?? '',
            'communication_style' => $_POST['communication_style'] ?? '',
            'relationship_goal' => $_POST['relationship_goal'] ?? '',
            'networking_goal' => $_POST['networking_goal'] ?? '',
            'icebreaker_preferences' => preg_split('/\s*,\s*/', $_POST['icebreaker_preferences'] ?? '', -1, PREG_SPLIT_NO_EMPTY),
        ]);
        set_flash('success', __('profile.compatibility_saved'));
        redirect(base_url('profile.php?tab=compatibility'));
    }

    $avatarPath = null;
    $coverPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $avatarPath = save_upload($_FILES['avatar'], 'profiles', 'avatar');
    }
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $coverPath = save_upload($_FILES['cover_image'], 'profiles', 'cover');
    }

    save_user_profile($userId, [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'line_id' => trim($_POST['line_id'] ?? ''),
        'bio' => trim($_POST['bio'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'interests' => preg_split('/\s*,\s*/', $_POST['interests'] ?? '', -1, PREG_SPLIT_NO_EMPTY),
        'personality' => trim($_POST['personality'] ?? ''),
        'languages' => preg_split('/\s*,\s*/', $_POST['languages'] ?? '', -1, PREG_SPLIT_NO_EMPTY),
        'city' => trim($_POST['city'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'instagram' => trim($_POST['instagram'] ?? ''),
        'facebook' => trim($_POST['facebook'] ?? ''),
        'privacy_settings' => [
            'show_email' => isset($_POST['show_email']),
            'show_phone' => isset($_POST['show_phone']),
            'show_social' => isset($_POST['show_social']),
            'show_bio' => isset($_POST['show_bio']),
            'show_compatibility' => isset($_POST['show_compatibility']),
        ],
    ], $avatarPath, $coverPath);

    set_flash('success', __('profile.saved'));
    redirect(base_url('profile.php'));
}

$profile = get_user_profile($userId);
$comp = $profile['compatibility'] ?? [];

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header profile-header">
    <div class="container">
        <div class="profile-cover" style="<?= !empty($profile['cover_image']) ? 'background-image:url(' . e(upload_url($profile['cover_image'])) . ')' : '' ?>">
            <img src="<?= e($profile['avatar'] ? upload_url($profile['avatar']) : default_avatar($profile['full_name'])) ?>" alt="" class="profile-avatar-lg">
        </div>
        <h1><?= e($profile['full_name']) ?>
            <?php if (user_is_verified($profile)): ?><span class="badge badge-success" title="<?= e(__('safety.verified')) ?>">✓</span><?php endif; ?>
        </h1>
        <?php if (!empty($profile['city'])): ?><p><?= e($profile['city']) ?><?= !empty($profile['occupation']) ? ' · ' . e($profile['occupation']) : '' ?></p><?php endif; ?>
    </div>
</section>

<section class="section content-section">
    <div class="container profile-layout">
        <div class="profile-tabs">
            <a href="<?= base_url('profile.php') ?>" class="profile-tab<?= $tab === 'profile' ? ' is-active' : '' ?>"><?php _e('profile.tab_profile'); ?></a>
            <a href="<?= base_url('profile.php?tab=compatibility') ?>" class="profile-tab<?= $tab === 'compatibility' ? ' is-active' : '' ?>"><?php _e('profile.tab_compatibility'); ?></a>
            <a href="<?= base_url('profile.php?tab=privacy') ?>" class="profile-tab<?= $tab === 'privacy' ? ' is-active' : '' ?>"><?php _e('profile.tab_privacy'); ?></a>
            <a href="<?= base_url('profile.php?tab=safety') ?>" class="profile-tab<?= $tab === 'safety' ? ' is-active' : '' ?>"><?php _e('profile.tab_safety'); ?></a>
        </div>

        <?php if ($tab === 'safety'): ?>
        <div class="card form-card-wide">
            <h2><?php _e('safety.emergency_contact'); ?></h2>
            <form method="post">
                <input type="hidden" name="form" value="safety">
                <div class="form-group"><label><?php _e('safety.contact_name'); ?></label><input class="input" name="emergency_contact_name" value="<?= e($profile['emergency_contact_name'] ?? '') ?>"></div>
                <div class="form-group"><label><?php _e('safety.contact_phone'); ?></label><input class="input" name="emergency_contact_phone" value="<?= e($profile['emergency_contact_phone'] ?? '') ?>"></div>
                <button type="submit" class="btn btn-primary"><?php _e('common.save'); ?></button>
            </form>
            <p class="form-help" style="margin-top:1rem;"><a href="<?= base_url('safety/report.php') ?>"><?php _e('safety.report_someone'); ?></a></p>
        </div>
        <?php elseif ($tab === 'compatibility'): ?>
        <div class="card form-card-wide">
            <h2><?php _e('profile.compatibility_title'); ?></h2>
            <p class="form-help"><?php _e('profile.compatibility_sub'); ?></p>
            <p class="form-help ai-policy-note"><?php _e('ai_policy.compatibility_note'); ?></p>
            <form method="post">
                <input type="hidden" name="form" value="compatibility">
                <div class="form-group">
                    <label><?php _e('profile.interests'); ?></label>
                    <input type="text" name="comp_interests" class="input" value="<?= e(implode(', ', $comp['interests'] ?? [])) ?>" placeholder="music, travel, startups">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php _e('profile.personality_type'); ?></label>
                        <select name="personality_type" class="select">
                            <option value=""><?php _e('profile.select'); ?></option>
                            <?php foreach (personality_types() as $t): ?>
                                <option value="<?= e($t) ?>" <?= ($comp['personality_type'] ?? '') === $t ? 'selected' : '' ?>><?= e(__('personality.' . $t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php _e('profile.communication_style'); ?></label>
                        <select name="communication_style" class="select">
                            <option value=""><?php _e('profile.select'); ?></option>
                            <?php foreach (communication_styles() as $t): ?>
                                <option value="<?= e($t) ?>" <?= ($comp['communication_style'] ?? '') === $t ? 'selected' : '' ?>><?= e(__('communication.' . $t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php _e('profile.relationship_goal'); ?></label>
                        <select name="relationship_goal" class="select">
                            <option value=""><?php _e('profile.select'); ?></option>
                            <?php foreach (relationship_goals() as $t): ?>
                                <option value="<?= e($t) ?>" <?= ($comp['relationship_goal'] ?? '') === $t ? 'selected' : '' ?>><?= e(__('relationship.' . $t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php _e('profile.networking_goal'); ?></label>
                        <select name="networking_goal" class="select">
                            <option value=""><?php _e('profile.select'); ?></option>
                            <?php foreach (networking_goals() as $t): ?>
                                <option value="<?= e($t) ?>" <?= ($comp['networking_goal'] ?? '') === $t ? 'selected' : '' ?>><?= e(__('networking.' . $t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php _e('profile.icebreakers'); ?></label>
                    <input type="text" name="icebreaker_preferences" class="input" value="<?= e(implode(', ', $comp['icebreaker_preferences'] ?? [])) ?>" placeholder="travel stories, food, hobbies">
                </div>
                <button type="submit" class="btn btn-primary"><?php _e('common.save'); ?></button>
            </form>
        </div>
        <?php else: ?>
        <form method="post" enctype="multipart/form-data" class="card form-card-wide">
            <div class="form-row">
                <div class="form-group">
                    <label><?php _e('profile.avatar'); ?></label>
                    <input type="file" name="avatar" class="input" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="form-group">
                    <label><?php _e('profile.cover_image'); ?></label>
                    <input type="file" name="cover_image" class="input" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>
            <div class="form-group">
                <label><?php _e('register_page.full_name'); ?></label>
                <input type="text" name="full_name" class="input" required value="<?= e($profile['full_name']) ?>">
            </div>
            <div class="form-group">
                <label><?php _e('profile.bio'); ?></label>
                <textarea name="bio" class="textarea" rows="4"><?= e($profile['bio'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php _e('profile.gender'); ?></label>
                    <select name="gender" class="select">
                        <option value=""><?php _e('profile.select'); ?></option>
                        <?php foreach (gender_options() as $g): ?>
                            <option value="<?= e($g) ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected' : '' ?>><?= e(__('gender.' . $g)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php _e('profile.date_of_birth'); ?></label>
                    <input type="date" name="date_of_birth" class="input" value="<?= e($profile['date_of_birth'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label><?php _e('profile.interests'); ?></label>
                <input type="text" name="interests" class="input" value="<?= e(implode(', ', $profile['interests'] ?? [])) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php _e('profile.personality'); ?></label>
                    <input type="text" name="personality" class="input" value="<?= e($profile['personality'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?php _e('profile.languages'); ?></label>
                    <input type="text" name="languages" class="input" value="<?= e(implode(', ', $profile['languages'] ?? [])) ?>" placeholder="English, Thai">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php _e('event_form.city'); ?></label>
                    <input type="text" name="city" class="input" value="<?= e($profile['city'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?php _e('profile.occupation'); ?></label>
                    <input type="text" name="occupation" class="input" value="<?= e($profile['occupation'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Instagram</label>
                    <input type="text" name="instagram" class="input" value="<?= e($profile['instagram'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Facebook</label>
                    <input type="text" name="facebook" class="input" value="<?= e($profile['facebook'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php _e('register_page.phone'); ?></label>
                    <input type="tel" name="phone" class="input" value="<?= e($profile['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?php _e('register_page.line_id'); ?></label>
                    <input type="text" name="line_id" class="input" value="<?= e($profile['line_id'] ?? '') ?>">
                </div>
            </div>

            <?php if ($tab === 'privacy' || true): ?>
            <div class="form-section">
                <h3 class="form-section-title"><?php _e('profile.tab_privacy'); ?></h3>
                <?php $privacy = $profile['privacy_settings'] ?? default_privacy_settings(); ?>
                <label class="checkbox-label"><input type="checkbox" name="show_bio" <?= !empty($privacy['show_bio']) ? 'checked' : '' ?>> <?php _e('profile.show_bio'); ?></label>
                <label class="checkbox-label"><input type="checkbox" name="show_phone" <?= !empty($privacy['show_phone']) ? 'checked' : '' ?>> <?php _e('profile.show_phone'); ?></label>
                <label class="checkbox-label"><input type="checkbox" name="show_social" <?= !empty($privacy['show_social']) ? 'checked' : '' ?>> <?php _e('profile.show_social'); ?></label>
                <label class="checkbox-label"><input type="checkbox" name="show_compatibility" <?= !empty($privacy['show_compatibility']) ? 'checked' : '' ?>> <?php _e('profile.show_compatibility'); ?></label>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-lg"><?php _e('common.save'); ?></button>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

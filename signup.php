<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    redirect(member_home_url());
}

$pageTitle = __('signup.title');
$pageMeta = page_meta([
    'title' => __('seo.signup_title'),
    'description' => __('seo.signup_description'),
    'url' => base_url('signup.php'),
    'image' => seo_share_image(['type' => 'signup']),
]);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_redirect();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (rate_limit_exceeded('signup', client_ip(), 10, 300)) {
        $errors[] = __('validation.too_many_attempts');
    }
    if ($fullName === '') {
        $errors[] = __('validation.full_name_required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('validation.email_required');
    }
    if (strlen($password) < 6) {
        $errors[] = __('validation.password_min');
    }
    if ($password !== $confirm) {
        $errors[] = __('validation.password_mismatch');
    }

    if ($errors === []) {
        rate_limit_hit('signup', client_ip());
        $result = register_account($fullName, $email, $password, 'participant');
        if ($result['ok']) {
            login_user($email, $password, true);
            $refCode = trim($_POST['ref'] ?? $_GET['ref'] ?? '');
            if ($refCode !== '') {
                record_referral_use($refCode, (int) current_user()['id']);
            }
            set_flash('success', __('signup.success'));
            redirect(base_url('events.php'));
        }
        $errors[] = $result['error'] ?? __('validation.registration_failed');
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="auth-page">
    <div class="auth-card card auth-card-wide">
        <h1><?php _e('signup.title'); ?></h1>
        <p><?php _e('signup.subtitle'); ?></p>
        <p class="form-help"><?php _e('signup.member_note'); ?></p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" data-loading>
            <?= csrf_field() ?>
            <?php if (!empty($_GET['ref'])): ?><input type="hidden" name="ref" value="<?= e($_GET['ref']) ?>"><?php endif; ?>

            <div class="form-group">
                <label for="full_name"><?php _e('register_page.full_name'); ?></label>
                <input type="text" id="full_name" name="full_name" class="input" required value="<?= e($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email"><?php _e('auth.email'); ?> *</label>
                <input type="email" id="email" name="email" class="input" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password"><?php _e('auth.password'); ?> *</label>
                    <input type="password" id="password" name="password" class="input" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="password_confirm"><?php _e('signup.confirm_password'); ?> *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="input" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg"><?php _e('signup.create_account'); ?></button>
        </form>

        <p class="form-help auth-switch">
            <?php _e('signup.have_account'); ?> <a href="<?= base_url('login.php') ?>"><?php _e('auth.sign_in'); ?></a>
        </p>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    $role = current_user_role();
    if (in_array($role, ['admin', 'super_admin'], true)) {
        redirect(base_url('admin/dashboard.php'));
    }
    if ($role === 'organizer') {
        redirect(base_url('organizer/dashboard.php'));
    }
    redirect(base_url('my-events.php'));
}

$pageTitle = __('signup.title');
$errors = [];
$role = $_GET['role'] ?? 'participant';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'participant';
    $orgName = trim($_POST['organization_name'] ?? '');

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
    if ($role === 'organizer' && $orgName === '') {
        $errors[] = __('validation.organization_name_required');
    }

    if ($errors === []) {
        $result = register_account($fullName, $email, $password, $role, $orgName ?: null);
        if ($result['ok']) {
            login_user($email, $password, true);
            $refCode = trim($_POST['ref'] ?? $_GET['ref'] ?? '');
            if ($refCode !== '') {
                record_referral_use($refCode, (int) current_user()['id']);
            }
            set_flash('success', __('signup.success'));
            if ($role === 'organizer') {
                redirect(base_url('organizer/dashboard.php'));
            }
            redirect(base_url('profile.php'));
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

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div class="role-toggle">
            <a href="<?= base_url('signup.php?role=participant') ?>" class="btn <?= $role === 'participant' ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?php _e('signup.participant'); ?></a>
            <a href="<?= base_url('signup.php?role=organizer') ?>" class="btn <?= $role === 'organizer' ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?php _e('signup.organizer'); ?></a>
        </div>

        <form method="post" data-loading>
            <input type="hidden" name="role" value="<?= e($role) ?>">
            <?php if (!empty($_GET['ref'])): ?><input type="hidden" name="ref" value="<?= e($_GET['ref']) ?>"><?php endif; ?>

            <div class="form-group">
                <label for="full_name"><?php _e('register_page.full_name'); ?></label>
                <input type="text" id="full_name" name="full_name" class="input" required value="<?= e($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email"><?php _e('auth.email'); ?> *</label>
                <input type="email" id="email" name="email" class="input" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <?php if ($role === 'organizer'): ?>
            <div class="form-group">
                <label for="organization_name"><?php _e('signup.organization_name'); ?> *</label>
                <input type="text" id="organization_name" name="organization_name" class="input" required value="<?= e($_POST['organization_name'] ?? '') ?>">
            </div>
            <?php endif; ?>

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
            <?php _e('signup.have_account'); ?> <a href="<?= base_url('login.php?role=' . e($role)) ?>"><?php _e('auth.sign_in'); ?></a>
        </p>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

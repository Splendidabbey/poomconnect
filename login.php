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

$pageTitle = __('auth.welcome_title');
$errors = [];
$role = $_GET['role'] ?? 'participant';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'participant';

    if ($email === '' || $password === '') {
        $errors[] = __('auth.email_password_required');
    } elseif ($role === 'participant' ? login_participant($email, $password) : login_user($email, $password)) {
        $userRole = current_user_role();

        if ($role === 'admin' && !in_array($userRole, ['admin', 'super_admin'], true)) {
            logout_user();
            $errors[] = __('auth.invalid_admin_credentials');
        } else {
            set_flash('success', __('auth.welcome_back'));
            if (in_array($userRole, ['admin', 'super_admin'], true)) {
                redirect(base_url('admin/dashboard.php'));
            }
            if ($userRole === 'organizer') {
                redirect(base_url('organizer/dashboard.php'));
            }
            redirect(base_url('my-events.php'));
        }
    } else {
        $stmt = db()->prepare('SELECT account_status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $status = $stmt->fetchColumn();
        if ($status === 'inactive') {
            $errors[] = __('admin_users.account_inactive');
        } else {
            $errors[] = __('auth.invalid_credentials');
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="auth-page">
    <div class="auth-card card">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <img src="<?= brand_app_icon() ?>" alt="<?= e(__('app.name')) ?>" class="logo-app-icon logo-app-icon-lg">
            <h1><?php _e('auth.welcome_title'); ?></h1>
            <p><?php _e('auth.sign_in_subtitle'); ?></p>
        </div>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div class="role-toggle">
            <a href="<?= base_url('login.php?role=participant') ?>" class="btn <?= $role === 'participant' ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?php _e('signup.participant'); ?></a>
            <a href="<?= base_url('login.php?role=organizer') ?>" class="btn <?= $role === 'organizer' ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?php _e('auth.organizer'); ?></a>
            <a href="<?= base_url('login.php?role=admin') ?>" class="btn <?= $role === 'admin' ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?php _e('auth.admin_role'); ?></a>
        </div>

        <form method="post" data-loading>
            <input type="hidden" name="role" value="<?= e($role) ?>">

            <div class="form-group">
                <label for="email"><?php _e('auth.email'); ?></label>
                <input type="email" id="email" name="email" class="input" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password"><?php _e('auth.password'); ?></label>
                <input type="password" id="password" name="password" class="input" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg"><?php _e('auth.sign_in'); ?></button>
        </form>

        <p class="form-help auth-switch">
            <?php _e('signup.no_account'); ?> <a href="<?= base_url('signup.php?role=' . e($role === 'admin' ? 'organizer' : $role)) ?>"><?php _e('signup.create_account'); ?></a>
        </p>

        <?php if ($role !== 'participant'): ?>
        <p class="form-help" style="text-align:center;margin-top:1rem;"><?php _e('auth.demo_credentials'); ?></p>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

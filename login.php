<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    if (is_admin()) {
        redirect(base_url('admin/dashboard.php'));
    }
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = 'Login';
$errors = [];
$role = $_GET['role'] ?? 'organizer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'organizer';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } elseif (login_user($email, $password)) {
        $userRole = current_user_role();

        if ($role === 'admin' && !in_array($userRole, ['admin', 'super_admin'], true)) {
            logout_user();
            $errors[] = 'Invalid admin credentials.';
        } else {
            set_flash('success', 'Welcome back!');
            if (in_array($userRole, ['admin', 'super_admin'], true)) {
                redirect(base_url('admin/dashboard.php'));
            }
            redirect(base_url('organizer/dashboard.php'));
        }
    } else {
        $errors[] = 'Invalid email or password.';
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="auth-page">
    <div class="auth-card card">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <img src="<?= brand_app_icon() ?>" alt="Poom Connect" class="logo-app-icon logo-app-icon-lg">
            <h1>Welcome Back</h1>
            <p>Sign in to manage your events</p>
        </div>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;">
            <a href="<?= base_url('login.php?role=organizer') ?>" class="btn <?= $role !== 'admin' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="flex:1;">Organizer</a>
            <a href="<?= base_url('login.php?role=admin') ?>" class="btn <?= $role === 'admin' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="flex:1;">Admin</a>
        </div>

        <form method="post" data-loading>
            <input type="hidden" name="role" value="<?= e($role) ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="input" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="<?= $role === 'admin' ? 'admin@poomconnect.com' : 'organizer@poomconnect.com' ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="input" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
        </form>

        <p class="form-help" style="text-align:center;margin-top:1.5rem;">
            Demo: organizer@poomconnect.com / organizer123
        </p>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

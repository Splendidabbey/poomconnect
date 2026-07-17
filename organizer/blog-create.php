<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);

if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('organizer.new_article');
$bodyClass = 'dashboard-page';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = parse_blog_form_data($_POST);

    if ($data['title'] === '') {
        $errors[] = __('validation.title_required');
    }
    if ($data['content'] === '') {
        $errors[] = __('validation.content_required');
    }

    $coverPath = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $coverPath = save_upload($_FILES['cover_image'], 'blog', 'post');
        if (!$coverPath) {
            $errors[] = __('validation.invalid_file');
        }
    }

    if ($errors === []) {
        save_blog_post(null, (int) $user['id'], (int) $org['id'], $data, $coverPath);
        set_flash('success', __('flash.article_saved'));
        redirect(base_url('organizer/blog.php'));
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <h1><?php _e('organizer.new_article'); ?></h1>
        </div>
        <div class="card form-card-wide">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>
            <form method="post" enctype="multipart/form-data" data-loading>
                <?php require APP_ROOT . '/includes/blog-form-fields.php'; ?>
                <button type="submit" class="btn btn-primary btn-lg"><?php _e('common.save'); ?></button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

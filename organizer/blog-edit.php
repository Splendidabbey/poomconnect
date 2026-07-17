<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$postId = (int) ($_GET['id'] ?? 0);
$post = $postId ? get_blog_post_by_id($postId) : null;

if ($postId && (!$post || !user_can_manage_blog_post($post))) {
    set_flash('error', __('blog_page.article_not_found'));
    redirect(base_url('organizer/blog.php'));
}

$pageTitle = __('organizer.edit_article');
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

    $coverPath = $post['cover_image'] ?? null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newCover = save_upload($_FILES['cover_image'], 'blog', 'post');
        if ($newCover) {
            $coverPath = $newCover;
        } else {
            $errors[] = __('validation.invalid_file');
        }
    }

    if ($errors === []) {
        $org = get_organization_for_user((int) $user['id']);
        $orgId = (int) ($post['organization_id'] ?? ($org['id'] ?? 0));
        save_blog_post($postId, (int) $user['id'], $orgId ?: null, $data, $coverPath);
        set_flash('success', __('flash.article_saved'));
        redirect(base_url('organizer/blog.php'));
    }

    $post = array_merge($post, $data);
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <h1><?php _e('organizer.edit_article'); ?></h1>
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

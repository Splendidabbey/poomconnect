<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.categories_title');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = in_array($_POST['type'] ?? '', ['event', 'blog', 'both'], true) ? $_POST['type'] : 'both';

    if ($name === '') {
        $errors[] = __('validation.name_required');
    } else {
        $slug = slugify($name);
        $stmt = db()->prepare('INSERT INTO categories (name, slug, type) VALUES (?, ?, ?)');
        try {
            $stmt->execute([$name, $slug, $type]);
            set_flash('success', __('flash.category_created'));
            redirect(base_url('admin/categories.php'));
        } catch (PDOException) {
            $errors[] = __('validation.category_exists');
        }
    }
}

$categories = db()->query('SELECT * FROM categories ORDER BY type, name')->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header"><h1><?php _e('admin.categories_title'); ?></h1></div>

        <div class="card form-card-wide" style="margin-bottom:1.5rem;">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>
            <form method="post" class="form-inline-row">
                <div class="form-group">
                    <label for="name"><?php _e('admin.category_name'); ?></label>
                    <input type="text" id="name" name="name" class="input" required>
                </div>
                <div class="form-group">
                    <label for="type"><?php _e('admin.category_type'); ?></label>
                    <select id="type" name="type" class="select">
                        <option value="event"><?php _e('admin.type_event'); ?></option>
                        <option value="blog"><?php _e('admin.type_blog'); ?></option>
                        <option value="both" selected><?php _e('admin.type_both'); ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?php _e('admin.add_category'); ?></button>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th><?php _e('admin.category_name'); ?></th><th>Slug</th><th><?php _e('admin.category_type'); ?></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= e($cat['name']) ?></td>
                                <td><?= e($cat['slug']) ?></td>
                                <td><?= e($cat['type']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

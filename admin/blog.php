<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin.blog_title');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;

$posts = get_blog_posts_for_admin();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header">
            <h1><?php _e('admin.blog_title'); ?></h1>
            <a href="<?= base_url('admin/blog-edit.php') ?>" class="btn btn-primary btn-sm"><?php _e('admin.new_article'); ?></a>
        </div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php _e('blog_form.title'); ?></th>
                            <th><?php _e('blog_form.category'); ?></th>
                            <th><?php _e('admin.organization'); ?></th>
                            <th><?php _e('common.status'); ?></th>
                            <th><?php _e('common.date'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= e($post['title']) ?></td>
                                <td><?= e($post['category_name'] ?? '—') ?></td>
                                <td><?= e($post['organization_name'] ?? __('admin.platform')) ?></td>
                                <td><span class="badge badge-purple"><?= e(status_label($post['status'])) ?></span></td>
                                <td><?= e(format_date($post['published_at'] ?? $post['created_at'])) ?></td>
                                <td class="table-actions">
                                    <a href="<?= base_url('admin/blog-edit.php?id=' . (int) $post['id']) ?>" class="btn btn-outline btn-sm"><?php _e('common.edit'); ?></a>
                                    <?php if ($post['status'] === 'published'): ?>
                                        <a href="<?= e(blog_url($post)) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener"><?php _e('blog_page.view'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

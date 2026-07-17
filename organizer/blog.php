<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);

if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('organizer.blog_title');
$bodyClass = 'dashboard-page';
$posts = get_blog_posts_for_org((int) $org['id']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1><?php _e('organizer.blog_title'); ?></h1>
                <p><?php _e('organizer.blog_subtitle'); ?></p>
            </div>
            <a href="<?= base_url('organizer/blog-create.php') ?>" class="btn btn-primary btn-sm"><?php _e('organizer.new_article'); ?></a>
        </div>
        <div class="card">
            <?php if ($posts): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php _e('blog_form.title'); ?></th>
                                <th><?php _e('blog_form.category'); ?></th>
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
                                    <td><span class="badge badge-purple"><?= e(status_label($post['status'])) ?></span></td>
                                    <td><?= e(format_date($post['published_at'] ?? $post['created_at'])) ?></td>
                                    <td class="table-actions">
                                        <a href="<?= base_url('organizer/blog-edit.php?id=' . (int) $post['id']) ?>" class="btn btn-outline btn-sm"><?php _e('common.edit'); ?></a>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <a href="<?= e(blog_url($post)) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener"><?php _e('blog_page.view'); ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p><?php _e('organizer.no_articles'); ?></p>
                    <a href="<?= base_url('organizer/blog-create.php') ?>" class="btn btn-primary"><?php _e('organizer.new_article'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

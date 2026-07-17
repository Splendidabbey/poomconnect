<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = trim($_GET['slug'] ?? '');
$post = $slug !== '' ? get_blog_post_by_slug($slug) : null;

if (!$post) {
    set_flash('error', __('blog_page.article_not_found'));
    redirect(base_url('blog.php'));
}

$pageTitle = $post['title'];
$pageMeta = blog_page_meta($post);
$cover = $post['cover_image'] ? upload_url($post['cover_image']) : default_event_image();
$shareUrl = blog_url($post);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<article class="article-page">
    <section class="page-header article-header">
        <div class="container article-header-inner">
            <?php if (!empty($post['category_name'])): ?>
                <a href="<?= base_url('blog.php?category_id=' . (int) $post['category_id']) ?>" class="badge badge-purple"><?= e($post['category_name']) ?></a>
            <?php endif; ?>
            <h1><?= e($post['title']) ?></h1>
            <div class="article-meta">
                <span><?= e($post['author_name']) ?></span>
                <?php if (!empty($post['organization_name'])): ?>
                    <span>· <?= e($post['organization_name']) ?></span>
                <?php endif; ?>
                <span>· <?= e(format_date($post['published_at'] ?? $post['created_at'])) ?></span>
            </div>
        </div>
    </section>

    <section class="section content-section">
        <div class="container article-layout">
            <img src="<?= e($cover) ?>" alt="<?= e($post['title']) ?>" class="article-cover">
            <?php if (!empty($post['excerpt'])): ?>
                <p class="article-excerpt"><?= e($post['excerpt']) ?></p>
            <?php endif; ?>
            <div class="article-content card">
                <?= nl2br(e($post['content'])) ?>
            </div>
            <div class="article-actions">
                <?= render_social_share([
                    'url' => $shareUrl,
                    'title' => $post['title'],
                    'entity_type' => 'blog',
                    'entity_id' => (int) $post['id'],
                ]) ?>
                <a href="<?= base_url('blog.php') ?>" class="btn btn-primary"><?php _e('blog_page.back_to_blog'); ?></a>
            </div>
        </div>
    </section>
</article>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

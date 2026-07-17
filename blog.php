<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = __('blog_page.title');

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'category_id' => (int) ($_GET['category_id'] ?? 0) ?: null,
];

$posts = search_blog_posts($filters, 24);
$categories = get_categories('blog');
$pageMeta = page_meta([
    'title' => __('blog_page.title'),
    'description' => __('blog_page.subtitle'),
    'url' => base_url('blog.php'),
]);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <span class="section-label"><?php _e('blog_page.label'); ?></span>
        <h1><?php _e('blog_page.heading'); ?> <span class="gradient-text"><?php _e('blog_page.heading_highlight'); ?></span></h1>
        <p><?php _e('blog_page.subtitle'); ?></p>
    </div>
</section>

<section class="section content-section" style="padding-top:0;">
    <div class="container">
        <form method="get" class="filter-panel card blog-filter" action="<?= base_url('blog.php') ?>">
            <div class="filter-grid filter-grid-compact">
                <div class="form-group">
                    <label for="q"><?php _e('blog_page.search'); ?></label>
                    <input type="search" id="q" name="q" class="input" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('blog_page.search_placeholder')) ?>">
                </div>
                <div class="form-group">
                    <label for="category_id"><?php _e('blog_page.category'); ?></label>
                    <select id="category_id" name="category_id" class="select">
                        <option value=""><?php _e('blog_page.all_categories'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><?php _e('blog_page.apply_filters'); ?></button>
                <a href="<?= base_url('blog.php') ?>" class="btn btn-outline"><?php _e('blog_page.clear_filters'); ?></a>
            </div>
        </form>

        <div class="blog-categories">
            <a href="<?= base_url('blog.php') ?>" class="blog-cat-pill<?= empty($filters['category_id']) ? ' is-active' : '' ?>"><?php _e('blog_page.all_articles'); ?></a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= base_url('blog.php?category_id=' . (int) $cat['id']) ?>" class="blog-cat-pill<?= (int) ($filters['category_id'] ?? 0) === (int) $cat['id'] ? ' is-active' : '' ?>"><?= e($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($posts): ?>
            <div class="blog-grid">
                <?php foreach ($posts as $post): ?>
                    <?php $cover = $post['cover_image'] ? upload_url($post['cover_image']) : default_event_image(); ?>
                    <article class="blog-card card">
                        <a href="<?= e(blog_url($post)) ?>">
                            <img src="<?= e($cover) ?>" alt="<?= e($post['title']) ?>" class="blog-card-image">
                            <div class="blog-card-body">
                                <?php if (!empty($post['category_name'])): ?>
                                    <span class="badge badge-purple"><?= e($post['category_name']) ?></span>
                                <?php endif; ?>
                                <h3><?= e($post['title']) ?></h3>
                                <p><?= e(mb_substr(strip_tags((string) ($post['excerpt'] ?? '')), 0, 140)) ?></p>
                                <div class="blog-card-meta">
                                    <span><?= e($post['author_name']) ?></span>
                                    <span><?= e(format_date($post['published_at'] ?? $post['created_at'])) ?></span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state card">
                <h3><?php _e('blog_page.no_articles'); ?></h3>
                <p><?php _e('blog_page.no_articles_sub'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

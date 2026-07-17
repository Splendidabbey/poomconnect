<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $post */
$post = $post ?? null;
$values = $post ?? $_POST;
$blogCategories = get_categories('blog');
$isEdit = (bool) $post;
?>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('blog_form.content'); ?></h3>

    <div class="form-group">
        <label for="title"><?php _e('blog_form.title'); ?> *</label>
        <input type="text" id="title" name="title" class="input" required value="<?= e($values['title'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="slug"><?php _e('blog_form.slug'); ?></label>
        <input type="text" id="slug" name="slug" class="input" value="<?= e($values['slug'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="excerpt"><?php _e('blog_form.excerpt'); ?></label>
        <textarea id="excerpt" name="excerpt" class="textarea" rows="3"><?= e($values['excerpt'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="content"><?php _e('blog_form.body'); ?> *</label>
        <textarea id="content" name="content" class="textarea" rows="12" required><?= e($values['content'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="cover_image"><?php _e('blog_form.cover'); ?></label>
        <?php if (!empty($values['cover_image'])): ?>
            <img src="<?= e(upload_url($values['cover_image'])) ?>" alt="" class="form-preview-image">
        <?php endif; ?>
        <input type="file" id="cover_image" name="cover_image" class="input" accept=".jpg,.jpeg,.png,.webp">
    </div>
</div>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('blog_form.publish'); ?></h3>

    <div class="form-row">
        <div class="form-group">
            <label for="category_id"><?php _e('blog_form.category'); ?></label>
            <select id="category_id" name="category_id" class="select">
                <option value=""><?php _e('blog_page.all_categories'); ?></option>
                <?php foreach ($blogCategories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (int) ($values['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="status"><?php _e('common.status'); ?></label>
            <select id="status" name="status" class="select">
                <option value="draft" <?= ($values['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>><?php _e('status.draft'); ?></option>
                <option value="published" <?= ($values['status'] ?? '') === 'published' ? 'selected' : '' ?>><?php _e('status.published'); ?></option>
            </select>
        </div>
    </div>
</div>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('event_form.seo'); ?></h3>

    <div class="form-group">
        <label for="meta_title"><?php _e('event_form.meta_title'); ?></label>
        <input type="text" id="meta_title" name="meta_title" class="input" value="<?= e($values['meta_title'] ?? '') ?>" maxlength="200">
    </div>

    <div class="form-group">
        <label for="meta_description"><?php _e('event_form.meta_description'); ?></label>
        <textarea id="meta_description" name="meta_description" class="textarea" rows="3" maxlength="320"><?= e($values['meta_description'] ?? '') ?></textarea>
    </div>
</div>

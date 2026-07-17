<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $event */
$event = $event ?? null;
$values = $event ?? $_POST;
$eventCategories = get_categories('event');
$gallery = $event ? get_event_images((int) $event['id']) : [];
$isEdit = (bool) $event;
?>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('event_form.basic'); ?></h3>

    <div class="form-group">
        <label for="title"><?php _e('organizer.event_title'); ?> *</label>
        <input type="text" id="title" name="title" class="input" required value="<?= e($values['title'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="slug"><?php _e('event_form.slug'); ?></label>
        <input type="text" id="slug" name="slug" class="input" value="<?= e($values['slug'] ?? '') ?>" placeholder="<?= e(__('event_form.slug_hint')) ?>">
    </div>

    <div class="form-group">
        <label for="description"><?php _e('organizer.description'); ?></label>
        <textarea id="description" name="description" class="textarea" rows="5"><?= e($values['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="cover_image"><?php _e('organizer.cover_image'); ?></label>
        <?php if (!empty($values['cover_image'])): ?>
            <img src="<?= e(upload_url($values['cover_image'])) ?>" alt="" class="form-preview-image">
        <?php endif; ?>
        <input type="file" id="cover_image" name="cover_image" class="input" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <?php if ($isEdit): ?>
    <div class="form-group">
        <label><?php _e('event_form.gallery'); ?></label>
        <?php if ($gallery): ?>
            <div class="gallery-admin-grid">
                <?php foreach ($gallery as $img): ?>
                    <div class="gallery-admin-item">
                        <img src="<?= e(upload_url($img['image_path'])) ?>" alt="">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <input type="file" id="gallery_images" name="gallery_images[]" class="input" accept=".jpg,.jpeg,.png,.webp" multiple>
        <p class="form-help"><?php _e('event_form.gallery_help'); ?></p>
    </div>
    <?php else: ?>
    <div class="form-group">
        <label for="gallery_images"><?php _e('event_form.gallery'); ?></label>
        <input type="file" id="gallery_images" name="gallery_images[]" class="input" accept=".jpg,.jpeg,.png,.webp" multiple>
        <p class="form-help"><?php _e('event_form.gallery_help'); ?></p>
    </div>
    <?php endif; ?>
</div>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('event_form.classification'); ?></h3>

    <div class="form-row">
        <div class="form-group">
            <label for="category_id"><?php _e('event_form.category'); ?></label>
            <select id="category_id" name="category_id" class="select">
                <option value=""><?php _e('event_form.all_categories'); ?></option>
                <?php foreach ($eventCategories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (int) ($values['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="event_type"><?php _e('event_form.event_type'); ?></label>
            <select id="event_type" name="event_type" class="select">
                <?php foreach (event_types() as $type): ?>
                    <option value="<?= e($type) ?>" <?= ($values['event_type'] ?? 'social') === $type ? 'selected' : '' ?>><?= e(event_type_label($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('event_form.location_section'); ?></h3>

    <div class="form-row">
        <div class="form-group">
            <label for="location"><?php _e('common.location'); ?></label>
            <input type="text" id="location" name="location" class="input" value="<?= e($values['location'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="city"><?php _e('event_form.city'); ?></label>
            <input type="text" id="city" name="city" class="input" value="<?= e($values['city'] ?? '') ?>" placeholder="Bangkok">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="latitude"><?php _e('event_form.latitude'); ?></label>
            <input type="text" id="latitude" name="latitude" class="input" value="<?= e((string) ($values['latitude'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="longitude"><?php _e('event_form.longitude'); ?></label>
            <input type="text" id="longitude" name="longitude" class="input" value="<?= e((string) ($values['longitude'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="map_url"><?php _e('event_form.map_embed'); ?></label>
        <input type="url" id="map_url" name="map_url" class="input" value="<?= e($values['map_url'] ?? '') ?>" placeholder="https://maps.google.com/...">
        <p class="form-help"><?php _e('event_form.map_help'); ?></p>
    </div>
</div>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('event_form.schedule'); ?></h3>

    <div class="form-row">
        <div class="form-group">
            <label for="event_date"><?php _e('common.date'); ?> *</label>
            <input type="date" id="event_date" name="event_date" class="input" required value="<?= e($values['event_date'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="status"><?php _e('common.status'); ?></label>
            <select id="status" name="status" class="select">
                <?php
                $statuses = $isEdit
                    ? ['draft', 'published', 'live', 'paused', 'completed', 'cancelled']
                    : ['draft', 'published'];
                foreach ($statuses as $s):
                ?>
                    <option value="<?= $s ?>" <?= ($values['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="start_time"><?php _e('organizer.start_time'); ?></label>
            <input type="time" id="start_time" name="start_time" class="input" value="<?= e(substr((string) ($values['start_time'] ?? '18:00'), 0, 5)) ?>">
        </div>
        <div class="form-group">
            <label for="end_time"><?php _e('organizer.end_time'); ?></label>
            <input type="time" id="end_time" name="end_time" class="input" value="<?= e(substr((string) ($values['end_time'] ?? '22:00'), 0, 5)) ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="max_participants"><?php _e('organizer.max_participants'); ?></label>
            <input type="number" id="max_participants" name="max_participants" class="input" min="2" value="<?= e((string) ($values['max_participants'] ?? '50')) ?>">
        </div>
        <div class="form-group">
            <label for="ticket_price"><?php _e('organizer.ticket_price'); ?></label>
            <input type="number" id="ticket_price" name="ticket_price" class="input" min="0" step="1" value="<?= e((string) ($values['ticket_price'] ?? '990')) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="round_duration"><?php _e('event_form.round_duration'); ?></label>
        <input type="number" id="round_duration" name="round_duration" class="input" min="60" value="<?= e((string) ($values['round_duration'] ?? '300')) ?>">
        <p class="form-help"><?php _e('event_form.round_help'); ?></p>
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

    <div class="form-group">
        <label for="og_image"><?php _e('event_form.og_image'); ?></label>
        <?php if (!empty($values['og_image'])): ?>
            <img src="<?= e(upload_url($values['og_image'])) ?>" alt="" class="form-preview-image">
        <?php endif; ?>
        <input type="file" id="og_image" name="og_image" class="input" accept=".jpg,.jpeg,.png,.webp">
    </div>
</div>

<div class="form-section">
    <h3 class="form-section-title"><?php _e('event_form.builder'); ?></h3>

    <div class="form-group">
        <label for="banner_image"><?php _e('event_form.banner'); ?></label>
        <?php if (!empty($values['banner_image'])): ?>
            <img src="<?= e(upload_url($values['banner_image'])) ?>" alt="" class="form-preview-image">
        <?php endif; ?>
        <input type="file" id="banner_image" name="banner_image" class="input" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="dress_code"><?php _e('event_form.dress_code'); ?></label>
            <input type="text" id="dress_code" name="dress_code" class="input" value="<?= e($values['dress_code'] ?? '') ?>" placeholder="Smart casual">
        </div>
        <div class="form-group">
            <label for="round_count"><?php _e('event_form.round_count'); ?></label>
            <input type="number" id="round_count" name="round_count" class="input" min="1" value="<?= e((string) ($values['round_count'] ?? '5')) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="rules"><?php _e('event_form.rules'); ?></label>
        <textarea id="rules" name="rules" class="textarea" rows="4"><?= e($values['rules'] ?? '') ?></textarea>
    </div>

    <div class="form-row checkbox-row">
        <label class="checkbox-label"><input type="checkbox" name="waitlist_enabled" value="1" <?= !isset($values['waitlist_enabled']) || !empty($values['waitlist_enabled']) ? 'checked' : '' ?>> <?php _e('event_form.waitlist_enabled'); ?></label>
        <label class="checkbox-label"><input type="checkbox" name="invite_enabled" value="1" <?= !isset($values['invite_enabled']) || !empty($values['invite_enabled']) ? 'checked' : '' ?>> <?php _e('event_form.invite_enabled'); ?></label>
    </div>
</div>

<?php

declare(strict_types=1);

/**
 * Accessible image/file dropzone used across profile, events, payments, and chat.
 *
 * @param array{
 *   name: string,
 *   id?: string,
 *   label?: string,
 *   help?: string,
 *   accept?: string,
 *   multiple?: bool,
 *   required?: bool,
 *   preview?: ?string,
 *   variant?: 'image'|'avatar'|'cover'|'banner'|'slip'|'logo'|'attach',
 * } $opts
 */
function render_file_drop(array $opts): void
{
    $name = (string) ($opts['name'] ?? 'file');
    $id = (string) ($opts['id'] ?? preg_replace('/[^a-zA-Z0-9_-]/', '-', $name));
    $label = (string) ($opts['label'] ?? '');
    $help = (string) ($opts['help'] ?? '');
    $accept = (string) ($opts['accept'] ?? '.jpg,.jpeg,.png,.webp');
    $multiple = !empty($opts['multiple']);
    $required = !empty($opts['required']);
    $preview = $opts['preview'] ?? null;
    $variant = (string) ($opts['variant'] ?? 'image');
    $hasPreview = is_string($preview) && $preview !== '';
    ?>
    <div class="file-drop file-drop--<?= e($variant) ?><?= $hasPreview ? ' has-preview' : '' ?>" data-file-drop data-variant="<?= e($variant) ?>">
        <?php if ($label !== ''): ?>
            <label class="file-drop-label" for="<?= e($id) ?>"><?= e($label) ?><?= $required ? ' *' : '' ?></label>
        <?php endif; ?>
        <div class="file-drop-surface">
            <input
                type="file"
                id="<?= e($id) ?>"
                name="<?= e($name) ?>"
                class="file-drop-input"
                accept="<?= e($accept) ?>"
                <?= $multiple ? 'multiple' : '' ?>
                <?= $required ? 'required' : '' ?>
                aria-label="<?= e($label !== '' ? $label : __('upload.browse')) ?>"
            >
            <div class="file-drop-preview"<?= $hasPreview ? '' : ' hidden' ?>>
                <?php if ($hasPreview): ?>
                    <img src="<?= e($preview) ?>" alt="">
                <?php endif; ?>
            </div>
            <div class="file-drop-copy">
                <span class="file-drop-icon" aria-hidden="true">
                    <?php if ($variant === 'attach'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 16V4"/><path d="M8 8l4-4 4 4"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg>
                    <?php endif; ?>
                </span>
                <strong class="file-drop-title"><?= e($hasPreview ? __('upload.replace') : ($variant === 'attach' ? __('upload.add_photo') : __('upload.drop'))) ?></strong>
                <span class="file-drop-hint"><?= e(__('upload.types')) ?></span>
                <span class="file-drop-filename" hidden></span>
            </div>
        </div>
        <?php if ($help !== ''): ?>
            <p class="form-help"><?= e($help) ?></p>
        <?php endif; ?>
    </div>
    <?php
}

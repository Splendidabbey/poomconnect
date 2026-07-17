<?php

declare(strict_types=1);

$currentLocale = current_locale();
$locales = supported_locales();
?>
<details class="lang-switch lang-switch--header lang-switch--compact">
    <summary class="lang-switch-trigger nav-lang-trigger" aria-label="<?= e(__('nav.language')) ?>">
        <?= e(strtoupper($currentLocale === 'fil' ? 'FIL' : $currentLocale)) ?>
        <span class="lang-switch-chevron" aria-hidden="true">▾</span>
    </summary>
    <div class="lang-switch-menu">
        <?php foreach ($locales as $locale): ?>
            <a href="<?= e(locale_switch_url($locale)) ?>"
               class="lang-switch-option<?= $currentLocale === $locale ? ' is-active' : '' ?>"
               lang="<?= e($locale) ?>">
                <?= e(locale_label($locale)) ?>
                <?php if ($currentLocale === $locale): ?><span class="lang-switch-mark" aria-hidden="true">✓</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</details>

<?php require APP_ROOT . '/includes/currency-toggle-header.php'; ?>

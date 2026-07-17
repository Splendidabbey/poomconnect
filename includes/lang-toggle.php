<?php

declare(strict_types=1);

$currentLocale = current_locale();
$locales = supported_locales();
?>
<details class="lang-switch lang-switch--dropdown">
    <summary class="lang-switch-trigger" aria-label="<?= e(__('nav.language')) ?>">
        <svg class="lang-switch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
        </svg>
        <span><?= e(locale_label()) ?></span>
        <span class="lang-switch-chevron" aria-hidden="true">▾</span>
    </summary>
    <div class="lang-switch-menu">
        <?php foreach ($locales as $locale): ?>
            <a href="<?= e(locale_switch_url($locale)) ?>"
               class="lang-switch-option<?= $currentLocale === $locale ? ' is-active' : '' ?>"
               lang="<?= e($locale) ?>">
                <?= e(__('nav.lang_' . ($locale === 'fil' ? 'fil' : $locale))) ?>
                <?php if ($currentLocale === $locale): ?><span class="lang-switch-mark" aria-hidden="true">✓</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</details>

<details class="currency-switch currency-switch--dropdown">
    <summary class="currency-switch-trigger" aria-label="<?= e(__('nav.currency')) ?>">
        <span><?= e(currency_symbol(current_currency())) ?> <?= e(current_currency()) ?></span>
        <span class="lang-switch-chevron" aria-hidden="true">▾</span>
    </summary>
    <div class="lang-switch-menu currency-switch-menu">
        <?php foreach (supported_currencies() as $currency): ?>
            <a href="<?= e(currency_switch_url($currency)) ?>"
               class="lang-switch-option<?= current_currency() === $currency ? ' is-active' : '' ?>">
                <?= e(currency_symbol($currency)) ?> <?= e(currency_label($currency)) ?>
                <?php if (current_currency() === $currency): ?><span class="lang-switch-mark" aria-hidden="true">✓</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</details>

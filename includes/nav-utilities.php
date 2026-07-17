<?php

declare(strict_types=1);

/**
 * Language + currency switchers for the navbar.
 * @var string $navUtilitiesPlacement 'bar' | 'shell' | 'drawer'
 */
$navUtilitiesPlacement ??= 'bar';
$currentLocale = current_locale();
$locales = supported_locales();
$currentCurrency = current_currency();
?>
<div class="nav-utilities nav-utilities--<?= e($navUtilitiesPlacement) ?>" data-nav-utilities>
    <details class="lang-switch lang-switch--nav">
        <summary class="nav-pref-trigger" aria-label="<?= e(__('nav.language')) ?>">
            <svg class="nav-pref-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <span class="nav-pref-label"><?= e(strtoupper($currentLocale === 'fil' ? 'FIL' : $currentLocale)) ?></span>
            <span class="nav-pref-chevron" aria-hidden="true">▾</span>
        </summary>
        <div class="nav-pref-menu">
            <?php foreach ($locales as $locale): ?>
                <a href="<?= e(locale_switch_url($locale)) ?>"
                   class="nav-pref-option<?= $currentLocale === $locale ? ' is-active' : '' ?>"
                   lang="<?= e($locale) ?>">
                    <?= e(locale_label($locale)) ?>
                    <?php if ($currentLocale === $locale): ?><span class="nav-pref-check" aria-hidden="true">✓</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </details>

    <details class="currency-switch currency-switch--nav">
        <summary class="nav-pref-trigger" aria-label="<?= e(__('nav.currency')) ?>">
            <span class="nav-pref-icon nav-pref-icon--text" aria-hidden="true"><?= e(currency_symbol($currentCurrency)) ?></span>
            <span class="nav-pref-label"><?= e($currentCurrency) ?></span>
            <span class="nav-pref-chevron" aria-hidden="true">▾</span>
        </summary>
        <div class="nav-pref-menu">
            <?php foreach (supported_currencies() as $currency): ?>
                <a href="<?= e(currency_switch_url($currency)) ?>"
                   class="nav-pref-option<?= $currentCurrency === $currency ? ' is-active' : '' ?>">
                    <?= e(currency_symbol($currency)) ?> <?= e(currency_label($currency)) ?>
                    <?php if ($currentCurrency === $currency): ?><span class="nav-pref-check" aria-hidden="true">✓</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </details>
</div>

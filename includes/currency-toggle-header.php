<?php

declare(strict_types=1);

$currentCurrency = current_currency();
?>
<details class="currency-switch currency-switch--header">
    <summary class="currency-switch-trigger" aria-label="<?= e(__('nav.currency')) ?>">
        <?= e($currentCurrency) ?>
        <span class="lang-switch-chevron" aria-hidden="true">▾</span>
    </summary>
    <div class="lang-switch-menu currency-switch-menu">
        <?php foreach (supported_currencies() as $currency): ?>
            <a href="<?= e(currency_switch_url($currency)) ?>"
               class="lang-switch-option<?= $currentCurrency === $currency ? ' is-active' : '' ?>">
                <?= e(currency_symbol($currency)) ?> <?= e(currency_label($currency)) ?>
                <?php if ($currentCurrency === $currency): ?><span class="lang-switch-mark" aria-hidden="true">✓</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</details>

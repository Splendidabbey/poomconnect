<?php

declare(strict_types=1);
?>
<details class="nav-account" data-nav-details>
    <summary class="nav-account-trigger" aria-label="<?= e(__('nav.account')) ?>">
        <img src="<?= e($navAvatarUrl) ?>" alt="" class="nav-avatar" width="32" height="32">
        <span class="nav-account-name"><?= e($navUserFirst) ?></span>
        <span class="nav-pref-chevron" aria-hidden="true">▾</span>
    </summary>
    <div class="nav-account-menu nav-pref-menu">
        <p class="nav-account-label"><?= e($navUserName) ?></p>
        <?php $navAccountMode = 'dropdown'; require APP_ROOT . '/includes/nav-account-links.php'; ?>
    </div>
</details>

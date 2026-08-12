<?php

declare(strict_types=1);

$paymentsNavCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="admin-payments-nav" aria-label="<?php _e('admin_payments.nav_label'); ?>">
    <a href="<?= base_url('admin/payment-settings.php') ?>" class="admin-payments-nav-link<?= $paymentsNavCurrent === 'payment-settings.php' ? ' is-active' : '' ?>">
        <?php _e('admin_payments.settings'); ?>
    </a>
    <a href="<?= base_url('admin/payments.php') ?>" class="admin-payments-nav-link<?= $paymentsNavCurrent === 'payments.php' ? ' is-active' : '' ?>">
        <?php _e('admin_payments.transactions'); ?>
    </a>
</nav>

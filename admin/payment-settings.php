<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_admin();

$pageTitle = __('admin_payments.settings_title');
$bodyClass = 'dashboard-page admin-page';
$hideNav = true;

$stats = payment_settings_stats();
$gateways = get_payment_gateways();
$settings = get_all_payment_settings();
$definitions = payment_gateway_definitions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_redirect();

    $section = (string) ($_POST['section'] ?? '');

    try {
        if ($section === 'platform') {
            save_payment_platform_settings($_POST);
        } elseif ($section === 'gateways') {
            save_payment_gateway_settings($_POST);
        }

        log_admin_action((int) current_user()['id'], 'payment_settings_update', $section);
        set_flash('success', __('admin_payments.saved'));
    } catch (Throwable $e) {
        error_log('Payment settings save failed: ' . $e->getMessage());
        set_flash('error', __('admin_payments.save_failed'));
    }

    redirect(base_url('admin/payment-settings.php'));
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="admin-layout">
    <?php require APP_ROOT . '/includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="dashboard-header admin-page-header">
            <div>
                <h1><?php _e('admin_payments.settings_title'); ?></h1>
                <p class="text-muted"><?php _e('admin_payments.settings_subtitle'); ?></p>
            </div>
            <a href="<?= base_url('admin/payments.php') ?>" class="btn btn-outline btn-sm"><?php _e('admin_payments.view_transactions'); ?></a>
        </div>

        <?php require APP_ROOT . '/includes/admin-payments-nav.php'; ?>

        <div class="admin-kpi-grid admin-user-kpi">
            <article class="admin-kpi-card is-warning">
                <div class="admin-kpi-label"><?php _e('admin_payments.pending'); ?></div>
                <div class="admin-kpi-value"><?= number_format($stats['pending']) ?></div>
            </article>
            <article class="admin-kpi-card is-success">
                <div class="admin-kpi-label"><?php _e('admin_payments.approved'); ?></div>
                <div class="admin-kpi-value"><?= number_format($stats['approved']) ?></div>
            </article>
            <article class="admin-kpi-card is-revenue">
                <div class="admin-kpi-label"><?php _e('admin_payments.total_volume'); ?></div>
                <div class="admin-kpi-value is-currency"><?= e(format_currency($stats['total_volume'])) ?></div>
            </article>
            <article class="admin-kpi-card">
                <div class="admin-kpi-label"><?php _e('admin_payments.enabled_methods'); ?></div>
                <div class="admin-kpi-value"><?= number_format($stats['enabled_methods']) ?></div>
            </article>
        </div>

        <form method="post" class="admin-payment-settings-form">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="gateways">

            <div class="card admin-payment-section">
                <div class="admin-payment-section-head">
                    <h2><?php _e('admin_payments.methods_title'); ?></h2>
                    <p class="text-muted"><?php _e('admin_payments.methods_help'); ?></p>
                </div>

                <div class="admin-gateway-grid">
                    <?php foreach ($gateways as $gateway):
                        $slug = (string) $gateway['slug'];
                        $def = $definitions[$slug] ?? null;
                        if (!$def) {
                            continue;
                        }
                        $config = decode_payment_gateway_config($gateway['config'] ?? null);
                        $status = payment_gateway_status_label($gateway);
                        ?>
                        <article class="admin-gateway-card<?= (bool) $gateway['is_enabled'] ? ' is-enabled' : '' ?>">
                            <div class="admin-gateway-card-head">
                                <div>
                                    <h3><?= e($def['name']) ?></h3>
                                    <span class="admin-gateway-type badge badge-outline">
                                        <?= e($def['type'] === 'manual' ? __('admin_payments.type_manual') : __('admin_payments.type_gateway')) ?>
                                    </span>
                                </div>
                                <span class="badge badge-<?= $status === 'ready' || $status === 'active' ? 'success' : ($status === 'needs_config' ? 'warning' : 'outline') ?>">
                                    <?= e(__('admin_payments.status_' . $status)) ?>
                                </span>
                            </div>

                            <p class="admin-gateway-desc"><?php _e('admin_payments.gateway_' . $slug . '_desc'); ?></p>

                            <label class="admin-gateway-toggle">
                                <input type="checkbox" name="enabled[<?= e($slug) ?>]" value="1"<?= (bool) $gateway['is_enabled'] ? ' checked' : '' ?>>
                                <span><?php _e('admin_payments.enable_method'); ?></span>
                            </label>

                            <label class="admin-gateway-default">
                                <input type="radio" name="default_method" value="<?= e($slug) ?>"<?= (bool) $gateway['is_default'] ? ' checked' : '' ?>>
                                <span><?php _e('admin_payments.default_method'); ?></span>
                            </label>

                            <?php if ($def['type'] === 'gateway'): ?>
                                <div class="form-group">
                                    <label><?php _e('admin_payments.mode'); ?></label>
                                    <select name="mode[<?= e($slug) ?>]" class="select">
                                        <option value="sandbox"<?= ($gateway['mode'] ?? 'sandbox') === 'sandbox' ? ' selected' : '' ?>><?php _e('admin_payments.mode_sandbox'); ?></option>
                                        <option value="live"<?= ($gateway['mode'] ?? '') === 'live' ? ' selected' : '' ?>><?php _e('admin_payments.mode_live'); ?></option>
                                    </select>
                                </div>

                                <?php foreach ($def['fields'] as $field):
                                    $fieldKey = $field['key'];
                                    $fieldValue = $config[$fieldKey] ?? '';
                                    ?>
                                    <div class="form-group">
                                        <label><?= e(__('admin_payments.field_' . $fieldKey)) ?></label>
                                        <input
                                            type="<?= $field['type'] === 'password' ? 'password' : 'text' ?>"
                                            name="config[<?= e($slug) ?>][<?= e($fieldKey) ?>]"
                                            class="input"
                                            value="<?= $field['type'] === 'password' ? '' : e((string) $fieldValue) ?>"
                                            placeholder="<?= $fieldValue !== '' && $field['type'] === 'password' ? e(__('admin_payments.field_saved')) : '' ?>"
                                            autocomplete="off"
                                        >
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="admin-payment-actions">
                    <button type="submit" class="btn btn-primary"><?php _e('admin_payments.save_methods'); ?></button>
                </div>
            </div>
        </form>

        <form method="post" class="admin-payment-settings-form">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="platform">

            <div class="card admin-payment-section">
                <div class="admin-payment-section-head">
                    <h2><?php _e('admin_payments.platform_title'); ?></h2>
                    <p class="text-muted"><?php _e('admin_payments.platform_help'); ?></p>
                </div>

                <div class="admin-platform-settings-grid">
                    <label class="admin-gateway-toggle">
                        <input type="checkbox" name="slip_upload_required" value="1"<?= ($settings['slip_upload_required'] ?? '1') === '1' ? ' checked' : '' ?>>
                        <span><?php _e('admin_payments.slip_upload_required'); ?></span>
                    </label>

                    <label class="admin-gateway-toggle">
                        <input type="checkbox" name="organizer_can_set_promptpay" value="1"<?= ($settings['organizer_can_set_promptpay'] ?? '1') === '1' ? ' checked' : '' ?>>
                        <span><?php _e('admin_payments.organizer_can_set_promptpay'); ?></span>
                    </label>

                    <div class="form-group">
                        <label for="platform_promptpay_number"><?php _e('admin_payments.platform_promptpay'); ?></label>
                        <input type="text" id="platform_promptpay_number" name="platform_promptpay_number" class="input" value="<?= e($settings['platform_promptpay_number'] ?? '') ?>" placeholder="0812345678">
                        <p class="form-help"><?php _e('admin_payments.platform_promptpay_help'); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="platform_bank_name"><?php _e('admin_payments.platform_bank_name'); ?></label>
                        <input type="text" id="platform_bank_name" name="platform_bank_name" class="input" value="<?= e($settings['platform_bank_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="platform_bank_account_name"><?php _e('admin_payments.platform_bank_account_name'); ?></label>
                        <input type="text" id="platform_bank_account_name" name="platform_bank_account_name" class="input" value="<?= e($settings['platform_bank_account_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="platform_bank_account_number"><?php _e('admin_payments.platform_bank_account_number'); ?></label>
                        <input type="text" id="platform_bank_account_number" name="platform_bank_account_number" class="input" value="<?= e($settings['platform_bank_account_number'] ?? '') ?>">
                    </div>

                    <div class="form-group admin-platform-settings-full">
                        <label for="payment_instructions"><?php _e('admin_payments.payment_instructions'); ?></label>
                        <textarea id="payment_instructions" name="payment_instructions" class="input" rows="4"><?= e($settings['payment_instructions'] ?? '') ?></textarea>
                        <p class="form-help"><?php _e('admin_payments.payment_instructions_help'); ?></p>
                    </div>
                </div>

                <div class="admin-payment-actions">
                    <button type="submit" class="btn btn-primary"><?php _e('admin_payments.save_platform'); ?></button>
                </div>
            </div>
        </form>

        <div class="card admin-payment-section">
            <div class="admin-payment-section-head">
                <h2><?php _e('admin_payments.future_title'); ?></h2>
                <p class="text-muted"><?php _e('admin_payments.future_help'); ?></p>
            </div>
            <ul class="admin-payment-future-list">
                <li><?php _e('admin_payments.future_checkout'); ?></li>
                <li><?php _e('admin_payments.future_webhooks'); ?></li>
                <li><?php _e('admin_payments.future_refunds'); ?></li>
            </ul>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>

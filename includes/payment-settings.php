<?php

declare(strict_types=1);

function ensure_payment_settings_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payment_gateways (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(40) NOT NULL UNIQUE,
            name VARCHAR(80) NOT NULL,
            gateway_type ENUM('manual','gateway') NOT NULL DEFAULT 'manual',
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            mode ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
            config JSON NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS platform_payment_settings (
            setting_key VARCHAR(80) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    seed_payment_gateways();
    seed_platform_payment_settings();
    $ready = true;
}

function payment_gateway_definitions(): array
{
    return [
        'promptpay' => [
            'name' => 'PromptPay',
            'type' => 'manual',
            'sort' => 1,
            'fields' => [],
        ],
        'bank_transfer' => [
            'name' => 'Bank Transfer',
            'type' => 'manual',
            'sort' => 2,
            'fields' => [],
        ],
        'stripe' => [
            'name' => 'Stripe',
            'type' => 'gateway',
            'sort' => 10,
            'fields' => [
                ['key' => 'public_key', 'type' => 'text'],
                ['key' => 'secret_key', 'type' => 'password'],
                ['key' => 'webhook_secret', 'type' => 'password'],
            ],
        ],
        'omise' => [
            'name' => 'Omise',
            'type' => 'gateway',
            'sort' => 11,
            'fields' => [
                ['key' => 'public_key', 'type' => 'text'],
                ['key' => 'secret_key', 'type' => 'password'],
            ],
        ],
        'paypal' => [
            'name' => 'PayPal',
            'type' => 'gateway',
            'sort' => 12,
            'fields' => [
                ['key' => 'client_id', 'type' => 'text'],
                ['key' => 'client_secret', 'type' => 'password'],
            ],
        ],
        '2c2p' => [
            'name' => '2C2P',
            'type' => 'gateway',
            'sort' => 13,
            'fields' => [
                ['key' => 'merchant_id', 'type' => 'text'],
                ['key' => 'secret_key', 'type' => 'password'],
            ],
        ],
    ];
}

function seed_payment_gateways(): void
{
    $definitions = payment_gateway_definitions();
    $stmt = db()->prepare(
        'INSERT IGNORE INTO payment_gateways (slug, name, gateway_type, is_enabled, is_default, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    foreach ($definitions as $slug => $def) {
        $enabled = in_array($slug, ['promptpay', 'bank_transfer'], true) ? 1 : 0;
        $default = $slug === 'promptpay' ? 1 : 0;
        $stmt->execute([$slug, $def['name'], $def['type'], $enabled, $default, $def['sort']]);
    }
}

function seed_platform_payment_settings(): void
{
    $defaults = [
        'slip_upload_required' => '1',
        'organizer_can_set_promptpay' => '1',
        'platform_promptpay_number' => '',
        'platform_bank_name' => '',
        'platform_bank_account_name' => '',
        'platform_bank_account_number' => '',
        'payment_instructions' => '',
    ];

    $stmt = db()->prepare('INSERT IGNORE INTO platform_payment_settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

function get_payment_gateways(bool $enabledOnly = false): array
{
    $sql = 'SELECT * FROM payment_gateways';
    if ($enabledOnly) {
        $sql .= ' WHERE is_enabled = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';

    return db()->query($sql)->fetchAll();
}

function get_payment_gateway(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM payment_gateways WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $row['config'] = decrypt_gateway_secret_fields($slug, decode_payment_gateway_config($row['config'] ?? null));

    return $row;
}

/** Decrypts the password-type fields (secret_key, webhook_secret, client_secret, ...) for a gateway's config. */
function decrypt_gateway_secret_fields(string $slug, array $config): array
{
    $definitions = payment_gateway_definitions();
    $fields = $definitions[$slug]['fields'] ?? [];

    foreach ($fields as $field) {
        if ($field['type'] !== 'password') {
            continue;
        }
        $key = $field['key'];
        if (!empty($config[$key]) && is_string($config[$key])) {
            try {
                $config[$key] = decrypt_secret($config[$key]);
            } catch (Throwable) {
                // Leave as-is (e.g. legacy plaintext saved before encryption was introduced).
            }
        }
    }

    return $config;
}

function decode_payment_gateway_config(mixed $config): array
{
    if (is_array($config)) {
        return $config;
    }

    if (!is_string($config) || $config === '') {
        return [];
    }

    $decoded = json_decode($config, true);

    return is_array($decoded) ? $decoded : [];
}

function get_default_payment_method_slug(): string
{
    $stmt = db()->query(
        'SELECT slug FROM payment_gateways WHERE is_enabled = 1 AND is_default = 1 ORDER BY sort_order ASC LIMIT 1'
    );
    $slug = $stmt->fetchColumn();

    if ($slug) {
        return (string) $slug;
    }

    $fallback = db()->query(
        'SELECT slug FROM payment_gateways WHERE is_enabled = 1 ORDER BY sort_order ASC LIMIT 1'
    )->fetchColumn();

    return $fallback ? (string) $fallback : 'promptpay';
}

function payment_method_is_enabled(string $slug): bool
{
    $stmt = db()->prepare('SELECT is_enabled FROM payment_gateways WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $enabled = $stmt->fetchColumn();

    return (bool) $enabled;
}

function get_payment_setting(string $key, mixed $default = null): mixed
{
    $stmt = db()->prepare('SELECT setting_value FROM platform_payment_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();

    return $value !== false ? $value : $default;
}

function get_all_payment_settings(): array
{
    $rows = db()->query('SELECT setting_key, setting_value FROM platform_payment_settings')->fetchAll();
    $settings = [];

    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

function set_payment_setting(string $key, ?string $value): void
{
    db()->prepare(
        'INSERT INTO platform_payment_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute([$key, $value]);
}

function payment_settings_stats(): array
{
    $pdo = db();

    return [
        'pending' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetchColumn(),
        'approved' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'approved'")->fetchColumn(),
        'rejected' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'rejected'")->fetchColumn(),
        'total_volume' => (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'approved'")->fetchColumn(),
        'enabled_methods' => (int) $pdo->query('SELECT COUNT(*) FROM payment_gateways WHERE is_enabled = 1')->fetchColumn(),
    ];
}

function save_payment_platform_settings(array $input): void
{
    set_payment_setting('slip_upload_required', !empty($input['slip_upload_required']) ? '1' : '0');
    set_payment_setting('organizer_can_set_promptpay', !empty($input['organizer_can_set_promptpay']) ? '1' : '0');
    set_payment_setting('platform_promptpay_number', trim((string) ($input['platform_promptpay_number'] ?? '')));
    set_payment_setting('platform_bank_name', trim((string) ($input['platform_bank_name'] ?? '')));
    set_payment_setting('platform_bank_account_name', trim((string) ($input['platform_bank_account_name'] ?? '')));
    set_payment_setting('platform_bank_account_number', trim((string) ($input['platform_bank_account_number'] ?? '')));
    set_payment_setting('payment_instructions', trim((string) ($input['payment_instructions'] ?? '')));
}

function save_payment_gateway_settings(array $input): void
{
    $definitions = payment_gateway_definitions();
    $pdo = db();
    $defaultSlug = trim((string) ($input['default_method'] ?? 'promptpay'));

    $pdo->beginTransaction();

    try {
        $pdo->exec('UPDATE payment_gateways SET is_default = 0');

        foreach ($definitions as $slug => $def) {
            $enabled = !empty($input['enabled'][$slug]) ? 1 : 0;
            $mode = ($input['mode'][$slug] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
            $isDefault = $slug === $defaultSlug && $enabled ? 1 : 0;

            $existing = get_payment_gateway($slug);
            $config = $existing['config'] ?? [];

            foreach ($def['fields'] as $field) {
                $key = $field['key'];
                $posted = trim((string) ($input['config'][$slug][$key] ?? ''));
                if ($posted !== '') {
                    $config[$key] = $field['type'] === 'password' ? encrypt_secret($posted) : $posted;
                }
            }

            $stmt = $pdo->prepare(
                'UPDATE payment_gateways
                 SET is_enabled = ?, is_default = ?, mode = ?, config = ?
                 WHERE slug = ?'
            );
            $stmt->execute([
                $enabled,
                $isDefault,
                $mode,
                $config === [] ? null : json_encode($config, JSON_UNESCAPED_UNICODE),
                $slug,
            ]);
        }

        if (!$pdo->query('SELECT id FROM payment_gateways WHERE is_default = 1 LIMIT 1')->fetchColumn()) {
            $fallback = $pdo->query(
                'SELECT slug FROM payment_gateways WHERE is_enabled = 1 ORDER BY sort_order ASC LIMIT 1'
            )->fetchColumn() ?: 'promptpay';
            $pdo->prepare('UPDATE payment_gateways SET is_default = 1 WHERE slug = ?')->execute([(string) $fallback]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function payment_gateway_status_label(array $gateway): string
{
    if (!(bool) $gateway['is_enabled']) {
        return 'disabled';
    }

    if ($gateway['gateway_type'] === 'manual') {
        return 'active';
    }

    $config = decode_payment_gateway_config($gateway['config'] ?? null);
    $definitions = payment_gateway_definitions();
    $fields = $definitions[$gateway['slug']]['fields'] ?? [];

    foreach ($fields as $field) {
        if (empty($config[$field['key']])) {
            return 'needs_config';
        }
    }

    return 'ready';
}

function payment_method_label(string $slug): string
{
    $definitions = payment_gateway_definitions();

    return $definitions[$slug]['name'] ?? ucfirst(str_replace('_', ' ', $slug));
}

function resolve_event_payment_details(array $event): array
{
    $settings = get_all_payment_settings();
    $allowOrg = ($settings['organizer_can_set_promptpay'] ?? '1') === '1';

    $orgPromptpay = trim((string) ($event['promptpay_number'] ?? ''));
    $platformPromptpay = trim((string) ($settings['platform_promptpay_number'] ?? ''));
    $promptpay = ($allowOrg && $orgPromptpay !== '') ? $orgPromptpay : ($platformPromptpay !== '' ? $platformPromptpay : $orgPromptpay);

    $bankName = trim((string) ($event['bank_name'] ?? ''));
    $bankAccountName = trim((string) ($event['bank_account_name'] ?? ''));
    $bankAccountNumber = trim((string) ($event['bank_account_number'] ?? ''));

    if ($bankName === '' && trim((string) ($settings['platform_bank_name'] ?? '')) !== '') {
        $bankName = trim((string) $settings['platform_bank_name']);
        $bankAccountName = trim((string) ($settings['platform_bank_account_name'] ?? ''));
        $bankAccountNumber = trim((string) ($settings['platform_bank_account_number'] ?? ''));
    }

    return [
        'promptpay_number' => $promptpay,
        'bank_name' => $bankName,
        'bank_account_name' => $bankAccountName,
        'bank_account_number' => $bankAccountNumber,
        'instructions' => trim((string) ($settings['payment_instructions'] ?? '')),
        'slip_upload_required' => ($settings['slip_upload_required'] ?? '1') === '1',
        'promptpay_enabled' => payment_method_is_enabled('promptpay'),
        'bank_transfer_enabled' => payment_method_is_enabled('bank_transfer'),
    ];
}

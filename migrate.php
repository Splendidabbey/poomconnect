#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/config/app.php';

/**
 * Schema used to be created/altered by 16 ensure_*_schema() functions running on
 * every single request (see git history of config/app.php). Those functions are
 * already idempotent (CREATE TABLE IF NOT EXISTS / guarded ALTER TABLE), so rather
 * than hand-transcribing their SQL into new files — risking a subtle mismatch —
 * this runner just calls them once each, tracked in schema_migrations so they never
 * run again after that. New tables going forward get a proper numbered .sql file
 * under migrations/, applied in filename order below.
 */
function run_migrations(): void
{
    $pdo = db();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(191) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $applied = array_flip($pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN));

    $legacyModules = [
        'legacy_content_schema' => 'ensure_content_schema',
        'legacy_platform_schema' => 'ensure_platform_schema',
        'legacy_realtime_schema' => 'ensure_realtime_schema',
        'legacy_roles_schema' => 'ensure_roles_schema',
        'legacy_tenant_schema' => 'ensure_tenant_schema',
        'legacy_subscription_schema' => 'ensure_subscription_schema',
        'legacy_safety_schema' => 'ensure_safety_schema',
        'legacy_community_schema' => 'ensure_community_schema',
        'legacy_marketplace_schema' => 'ensure_marketplace_schema',
        'legacy_templates_schema' => 'ensure_templates_schema',
        'legacy_ai_policy_schema' => 'ensure_ai_policy_schema',
        'legacy_admin_platform_schema' => 'ensure_admin_platform_schema',
        'legacy_admin_users_schema' => 'ensure_admin_users_schema',
        'legacy_payment_settings_schema' => 'ensure_payment_settings_schema',
        'legacy_mobile_api_schema' => 'ensure_mobile_api_schema',
        'legacy_localization_schema' => 'ensure_localization_schema',
        'legacy_marketing_schema' => 'ensure_marketing_schema',
    ];

    foreach ($legacyModules as $name => $function) {
        apply_migration($pdo, $applied, $name, static fn () => $function());
    }

    $dir = __DIR__ . '/migrations';
    $files = glob($dir . '/*.sql') ?: [];
    sort($files);

    foreach ($files as $file) {
        $name = basename($file);
        apply_migration($pdo, $applied, $name, static function () use ($pdo, $file): void {
            $pdo->exec((string) file_get_contents($file));
        });
    }

    echo "Migrations up to date.\n";
}

function apply_migration(PDO $pdo, array $applied, string $name, callable $run): void
{
    if (isset($applied[$name])) {
        echo "skip    {$name}\n";
        return;
    }

    try {
        $run();
        $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)')->execute([$name]);
        echo "applied {$name}\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "FAILED  {$name}: {$e->getMessage()}\n");
        exit(1);
    }
}

run_migrations();

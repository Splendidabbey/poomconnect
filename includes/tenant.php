<?php

declare(strict_types=1);

function ensure_tenant_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $orgCols = [
        'custom_domain' => 'VARCHAR(255) NULL AFTER slug',
        'subdomain' => 'VARCHAR(100) NULL AFTER custom_domain',
        'landing_headline' => 'VARCHAR(255) NULL AFTER bank_account_number',
        'landing_body' => 'TEXT NULL AFTER landing_headline',
        'landing_cta' => 'VARCHAR(120) NULL AFTER landing_body',
        'secondary_color' => "VARCHAR(20) NULL DEFAULT '#FF2D8D' AFTER primary_color",
        'subscription_plan_id' => 'INT UNSIGNED NULL AFTER secondary_color',
        'status' => "ENUM('active','suspended','pending') NOT NULL DEFAULT 'active' AFTER subscription_plan_id",
        'country' => 'CHAR(2) NULL AFTER status',
        'is_featured' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER country',
        'safe_event_badge' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured',
        'profile_bio' => 'TEXT NULL AFTER safe_event_badge',
        'profile_public' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER profile_bio',
        'rating_avg' => 'DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER profile_public',
        'rating_count' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating_avg',
    ];
    foreach ($orgCols as $col => $def) {
        if (!table_has_column('organizations', $col)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN {$col} {$def}");
        }
    }

    try {
        $pdo->exec('CREATE UNIQUE INDEX idx_org_subdomain ON organizations (subdomain)');
    } catch (PDOException) {
        // exists
    }
    try {
        $pdo->exec('CREATE UNIQUE INDEX idx_org_custom_domain ON organizations (custom_domain)');
    } catch (PDOException) {
        // exists
    }

    $ready = true;
}

function resolve_tenant_from_request(): ?array
{
    static $tenant = null;
    if ($tenant !== null) {
        return $tenant ?: null;
    }

    $slug = trim($_GET['org'] ?? $_GET['slug'] ?? '');
    if ($slug !== '') {
        $tenant = get_organization_by_slug($slug) ?: false;
        return $tenant ?: null;
    }

    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/:\d+$/', '', $host);

    if ($host !== '') {
        $byDomain = db()->prepare('SELECT * FROM organizations WHERE custom_domain = ? AND status = ? LIMIT 1');
        $byDomain->execute([$host, 'active']);
        $org = $byDomain->fetch();
        if ($org) {
            $tenant = $org;
            return $org;
        }

        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $sub = $parts[0];
            $bySub = db()->prepare(
                'SELECT * FROM organizations WHERE (subdomain = ? OR slug = ?) AND status = ? LIMIT 1'
            );
            $bySub->execute([$sub, $sub, 'active']);
            $org = $bySub->fetch();
            if ($org) {
                $tenant = $org;
                return $org;
            }
        }
    }

    if (!empty($_SESSION['tenant_org_id'])) {
        $stmt = db()->prepare('SELECT * FROM organizations WHERE id = ? AND status = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['tenant_org_id'], 'active']);
        $org = $stmt->fetch();
        if ($org) {
            $tenant = $org;
            return $org;
        }
    }

    $tenant = false;
    return null;
}

function current_tenant(): ?array
{
    return resolve_tenant_from_request();
}

function set_tenant_context(int $orgId): void
{
    $_SESSION['tenant_org_id'] = $orgId;
}

function clear_tenant_context(): void
{
    unset($_SESSION['tenant_org_id']);
}

function get_organization_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM organizations WHERE slug = ? LIMIT 1');
    $stmt->execute([trim($slug)]);

    return $stmt->fetch() ?: null;
}

function get_organization_by_id(int $orgId): ?array
{
    $stmt = db()->prepare('SELECT * FROM organizations WHERE id = ? LIMIT 1');
    $stmt->execute([$orgId]);

    return $stmt->fetch() ?: null;
}

function org_public_url(array $org): string
{
    if (!empty($org['custom_domain'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $org['custom_domain'] . '/';
    }
    if (!empty($org['subdomain'])) {
        return base_url('org/index.php?org=' . urlencode($org['subdomain']));
    }

    return base_url('org/index.php?org=' . urlencode($org['slug']));
}

function org_brand_name(?array $org = null): string
{
    $org ??= current_tenant();

    return $org['name'] ?? app_name();
}

function org_logo_url(?array $org = null): string
{
    $org ??= current_tenant();
    if (!empty($org['logo'])) {
        return upload_url($org['logo']);
    }

    return brand_logo('md');
}

function org_theme_vars(?array $org = null): array
{
    $org ??= current_tenant();
    $primary = $org['primary_color'] ?? '#6C35FF';
    $secondary = $org['secondary_color'] ?? '#FF2D8D';

    return [
        '--org-primary' => $primary,
        '--org-secondary' => $secondary,
        '--pink' => $secondary,
    ];
}

function org_theme_css(?array $org = null): string
{
    $vars = org_theme_vars($org);
    $lines = array_map(fn ($k, $v) => "{$k}: {$v};", array_keys($vars), $vars);

    return ':root { ' . implode(' ', $lines) . ' }';
}

function save_org_branding(int $orgId, array $data): bool
{
    $stmt = db()->prepare(
        'UPDATE organizations SET name=?, logo=?, primary_color=?, secondary_color=?, custom_domain=?, subdomain=?,
         landing_headline=?, landing_body=?, landing_cta=?, profile_bio=?, profile_public=?, country=?
         WHERE id=?'
    );

    return $stmt->execute([
        $data['name'],
        $data['logo'] ?? null,
        $data['primary_color'] ?? '#6C35FF',
        $data['secondary_color'] ?? '#FF2D8D',
        $data['custom_domain'] ?: null,
        $data['subdomain'] ?: null,
        $data['landing_headline'] ?: null,
        $data['landing_body'] ?: null,
        $data['landing_cta'] ?: null,
        $data['profile_bio'] ?: null,
        !empty($data['profile_public']) ? 1 : 0,
        $data['country'] ?: null,
        $orgId,
    ]);
}

function org_events(int $orgId, int $limit = 20): array
{
    $stmt = db()->prepare(
        "SELECT * FROM events WHERE organization_id = ? AND status IN ('published','live') ORDER BY event_date ASC LIMIT ?"
    );
    $stmt->bindValue(1, $orgId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function org_is_white_label(?array $org = null): bool
{
    $org ??= current_tenant();
    if (!$org) {
        return false;
    }

    return org_has_feature((int) $org['id'], 'white_label');
}

function tenant_scoped_org_id(): ?int
{
    $tenant = current_tenant();
    return $tenant ? (int) $tenant['id'] : null;
}

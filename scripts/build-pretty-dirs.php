<?php

declare(strict_types=1);

/**
 * Create login/index.php-style wrappers so nginx can serve /login without rewrite.
 * Safe to re-run; will not overwrite a real index.php (admin/index.php, etc.).
 */

$root = dirname(__DIR__);
$marker = 'PRETTY_DIR_WRAPPER';
$wrapper = <<<'PHP'
<?php

declare(strict_types=1);

// PRETTY_DIR_WRAPPER
$root = __DIR__;
while ($root !== dirname($root) && !is_file($root . '/includes/pretty-dir-dispatch.php')) {
    $root = dirname($root);
}
define('PRETTY_DIR_DISPATCH', true);
require $root . '/includes/pretty-dir-dispatch.php';
PHP;

$skipDirs = [
    'vendor', 'tests', 'includes', 'config', 'src', 'migrations', 'cron',
    'lang', 'scripts', 'deploy', 'api', 'uploads', 'assets', 'node_modules',
    '.git', 'poomconnect_images',
];
$skipFiles = [
    'index.php',
    'migrate.php',
    'seed.php',
    'sitemap.php',
    'robots.php',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$created = 0;
$skipped = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    $top = explode('/', $rel)[0];

    if (in_array($top, $skipDirs, true) || in_array($rel, $skipFiles, true)) {
        continue;
    }

    if (str_ends_with($rel, '/index.php') || str_starts_with(basename($rel), '_')) {
        continue;
    }

    $prettyDir = $root . '/' . substr($rel, 0, -4);
    $index = $prettyDir . '/index.php';

    if (is_file($prettyDir)) {
        fwrite(STDERR, "Skip file conflict: {$rel}\n");
        $skipped++;
        continue;
    }

    if (is_file($index) && !str_contains((string) file_get_contents($index), $marker)) {
        fwrite(STDERR, "Skip existing index: {$rel}\n");
        $skipped++;
        continue;
    }

    if (!is_dir($prettyDir) && !mkdir($prettyDir, 0755, true) && !is_dir($prettyDir)) {
        fwrite(STDERR, "Failed to create {$prettyDir}\n");
        exit(1);
    }

    if (file_put_contents($index, $wrapper) === false) {
        fwrite(STDERR, "Failed to write {$index}\n");
        exit(1);
    }

    $created++;
}

echo "Pretty URL wrappers written: {$created} (skipped {$skipped})\n";

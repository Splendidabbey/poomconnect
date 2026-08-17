<?php

declare(strict_types=1);

/**
 * Map a generated pretty-URL wrapper (.../login/index.php) to the real script
 * (.../login.php). Used so nginx can serve /login without rewrite rules.
 */
function pretty_dir_dispatch_target(string $scriptFilename, string $appRoot): ?string
{
    $scriptReal = realpath($scriptFilename);
    $appReal = realpath($appRoot);
    if ($scriptReal === false || $appReal === false) {
        return null;
    }

    $scriptNorm = str_replace('\\', '/', $scriptReal);
    $appNorm = rtrim(str_replace('\\', '/', $appReal), '/');

    if ($scriptNorm !== $appNorm && !str_starts_with($scriptNorm, $appNorm . '/')) {
        return null;
    }

    $relative = ltrim(substr($scriptNorm, strlen($appNorm)), '/');
    if (!str_ends_with($relative, '/index.php')) {
        return null;
    }

    $target = $appNorm . '/' . substr($relative, 0, -strlen('/index.php')) . '.php';

    return is_file($target) ? $target : null;
}

if (!defined('PRETTY_DIR_DISPATCH')) {
    return;
}

$appRoot = dirname(__DIR__);
$scriptFilename = (string) ($_SERVER['SCRIPT_FILENAME'] ?? '');
$target = pretty_dir_dispatch_target($scriptFilename, $appRoot);

if ($target === null) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

$appNorm = rtrim(str_replace('\\', '/', realpath($appRoot) ?: $appRoot), '/');
$scriptNorm = str_replace('\\', '/', realpath($scriptFilename) ?: $scriptFilename);
$wrapperRel = ltrim(substr($scriptNorm, strlen($appNorm)), '/');
$targetRel = ltrim(str_replace('\\', '/', substr(realpath($target) ?: $target, strlen($appNorm))), '/');

$originalScript = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$prefix = '';
if ($wrapperRel !== '' && str_ends_with($originalScript, '/' . $wrapperRel)) {
    $prefix = substr($originalScript, 0, -strlen($wrapperRel) - 1);
}

$_SERVER['SCRIPT_NAME'] = $prefix . '/' . $targetRel;
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$_SERVER['SCRIPT_FILENAME'] = $target;

require $target;

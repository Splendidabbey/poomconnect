<?php

declare(strict_types=1);

// PRETTY_DIR_WRAPPER
$root = __DIR__;
while ($root !== dirname($root) && !is_file($root . '/includes/pretty-dir-dispatch.php')) {
    $root = dirname($root);
}
define('PRETTY_DIR_DISPATCH', true);
require $root . '/includes/pretty-dir-dispatch.php';
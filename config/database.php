<?php

declare(strict_types=1);

$localConfig = __DIR__ . '/database.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'poomconnect');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: 'root');
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Unable to connect to the database. Please try again later.');
    }

    return $pdo;
}

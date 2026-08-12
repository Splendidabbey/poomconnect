<?php

declare(strict_types=1);

namespace PoomConnect\Tests;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for migrate.php against a real database. Requires the same
 * DB_* env vars as the app (see .env.example). Skips itself if no DB is reachable
 * (e.g. a CI environment without MySQL configured) rather than failing the suite.
 */
final class MigrateTest extends TestCase
{
    private static function connectOrSkip(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'poomconnect';
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'root';

        try {
            return new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name),
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            self::markTestSkipped('No reachable database for migration test: ' . $e->getMessage());
        }
    }

    public function testMigrateIsIdempotent(): void
    {
        self::connectOrSkip();

        $script = APP_ROOT . '/migrate.php';

        exec('php ' . escapeshellarg($script) . ' 2>&1', $firstRunOutput, $firstExit);
        $this->assertSame(0, $firstExit, implode("\n", $firstRunOutput));

        exec('php ' . escapeshellarg($script) . ' 2>&1', $secondRunOutput, $secondExit);
        $this->assertSame(0, $secondExit, implode("\n", $secondRunOutput));

        $secondRunText = implode("\n", $secondRunOutput);
        $this->assertStringNotContainsString('applied', $secondRunText, 'Second run should skip everything, not re-apply.');
        $this->assertStringContainsString('Migrations up to date.', $secondRunText);
    }

    public function testSchemaMigrationsTableTracksAppliedMigrations(): void
    {
        $pdo = self::connectOrSkip();

        exec('php ' . escapeshellarg(APP_ROOT . '/migrate.php') . ' 2>&1', $output, $exit);
        $this->assertSame(0, $exit);

        $count = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
        $this->assertGreaterThan(0, $count);
    }
}

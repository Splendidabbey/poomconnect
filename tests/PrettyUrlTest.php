<?php

declare(strict_types=1);

namespace PoomConnect\Tests;

use PHPUnit\Framework\TestCase;

require_once APP_ROOT . '/includes/functions.php';

final class PrettyUrlTest extends TestCase
{
    /**
     * @dataProvider pathProvider
     */
    public function testPrettyUrlPath(string $input, string $expected): void
    {
        $this->assertSame($expected, pretty_url_path($input));
    }

    public static function pathProvider(): array
    {
        return [
            'homepage' => ['index.php', ''],
            'homepage fragment' => ['index.php#pricing', '#pricing'],
            'login' => ['login.php', 'login'],
            'login query' => ['login.php?role=admin', 'login?role=admin'],
            'admin dashboard alias' => ['admin/dashboard.php', 'admin/'],
            'admin users' => ['admin/users.php', 'admin/users'],
            'admin user query' => ['admin/user.php?id=12', 'admin/user?id=12'],
            'organizer dashboard alias' => ['organizer/dashboard.php', 'organizer/'],
            'org index' => ['org/index.php?org=acme', 'org?org=acme'],
            'sitemap' => ['sitemap.php', 'sitemap.xml'],
            'robots' => ['robots.php', 'robots.txt'],
            'event slug' => ['event.php?slug=meetup', 'event?slug=meetup'],
            'api endpoint' => ['api/approve-payment.php', 'api/approve-payment'],
            'asset unchanged' => ['assets/css/style.css', 'assets/css/style.css'],
            'already pretty' => ['events', 'events'],
            'empty' => ['', ''],
        ];
    }

    public function testPrettyUrlsFollowExplicitEnvFlag(): void
    {
        $previousEnv = $_ENV['PRETTY_URLS'] ?? null;
        $previousServer = $_SERVER['SERVER_SOFTWARE'] ?? null;

        $_ENV['PRETTY_URLS'] = 'true';
        $_SERVER['SERVER_SOFTWARE'] = 'nginx';
        $this->assertTrue(pretty_urls_enabled());

        $_ENV['PRETTY_URLS'] = 'false';
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4';
        $this->assertFalse(pretty_urls_enabled());

        $this->restorePrettyUrlEnv($previousEnv, $previousServer);
    }

    public function testPrettyUrlsDefaultOffOnNginx(): void
    {
        $previousEnv = $_ENV['PRETTY_URLS'] ?? null;
        $previousServer = $_SERVER['SERVER_SOFTWARE'] ?? null;

        unset($_ENV['PRETTY_URLS']);
        putenv('PRETTY_URLS');
        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.24.0';
        $this->assertFalse(pretty_urls_enabled());
        $this->assertSame('login.php', public_url_path('login.php'));
        $this->assertSame('login.php?role=admin', public_url_path('login.php?role=admin'));
        $this->assertSame('', public_url_path('index.php'));

        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.54 (Unix)';
        $this->assertTrue(pretty_urls_enabled());
        $this->assertSame('login', public_url_path('login.php'));

        $this->restorePrettyUrlEnv($previousEnv, $previousServer);
    }

    private function restorePrettyUrlEnv(mixed $previousEnv, mixed $previousServer): void
    {
        if ($previousEnv === null) {
            unset($_ENV['PRETTY_URLS']);
            putenv('PRETTY_URLS');
        } else {
            $_ENV['PRETTY_URLS'] = $previousEnv;
            putenv('PRETTY_URLS=' . $previousEnv);
        }

        if ($previousServer === null) {
            unset($_SERVER['SERVER_SOFTWARE']);
        } else {
            $_SERVER['SERVER_SOFTWARE'] = $previousServer;
        }
    }
}

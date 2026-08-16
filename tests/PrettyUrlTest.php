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
}

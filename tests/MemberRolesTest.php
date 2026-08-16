<?php

declare(strict_types=1);

namespace PoomConnect\Tests;

use PHPUnit\Framework\TestCase;

require_once APP_ROOT . '/includes/auth.php';

final class MemberRolesTest extends TestCase
{
    public function testEveryMemberRoleCanJoinAndHost(): void
    {
        foreach (['participant', 'organizer', 'moderator', 'admin', 'super_admin'] as $role) {
            $this->assertTrue(role_can_join_events($role), $role . ' should join');
            $this->assertTrue(role_can_host_events($role), $role . ' should host');
        }
    }

    public function testGuestsCannotJoinOrHost(): void
    {
        $this->assertFalse(role_can_join_events(null));
        $this->assertFalse(role_can_host_events(''));
        $this->assertFalse(role_can_join_events('unknown'));
    }
}

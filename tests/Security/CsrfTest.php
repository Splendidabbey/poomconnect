<?php

declare(strict_types=1);

namespace PoomConnect\Tests\Security;

use PoomConnect\Security\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsGeneratedAndStable(): void
    {
        $token = Csrf::token();

        $this->assertNotSame('', $token);
        $this->assertSame($token, Csrf::token());
    }

    public function testVerifyAcceptsMatchingToken(): void
    {
        $token = Csrf::token();

        $this->assertTrue(Csrf::verify($token));
    }

    public function testVerifyRejectsWrongToken(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify('not-the-right-token'));
    }

    public function testVerifyRejectsMissingToken(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify(null));
        $this->assertFalse(Csrf::verify(''));
    }

    public function testVerifyRejectsWhenNoTokenWasEverIssued(): void
    {
        $this->assertFalse(Csrf::verify('anything'));
    }
}

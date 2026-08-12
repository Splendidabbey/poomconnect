<?php

declare(strict_types=1);

namespace PoomConnect\Tests;

use PHPUnit\Framework\TestCase;

final class LocalizationTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testConvertFromThbUsesFixedRates(): void
    {
        $this->assertSame(1.0 * 1000, \convert_from_thb(1000, 'THB'));
        $this->assertSame(round(1000 * 0.028, 2), \convert_from_thb(1000, 'USD'));
    }

    public function testConvertFromThbRoundsJpyToWholeNumber(): void
    {
        $converted = \convert_from_thb(1000, 'JPY');

        $this->assertSame(round($converted), $converted);
    }

    public function testFormatCurrencyThaiLocaleUsesBahtSuffix(): void
    {
        $_SESSION['locale'] = 'th';
        $_SESSION['currency'] = 'THB';

        $this->assertSame('1,000 บาท', \format_currency(1000, 'THB'));
    }

    public function testFormatCurrencyUsdUsesDollarSymbol(): void
    {
        $_SESSION['locale'] = 'en';

        $this->assertSame('$28.00 USD', \format_currency(1000, 'USD'));
    }

    public function testFormatCurrencyFallsBackToSessionCurrencyWhenNotSpecified(): void
    {
        $_SESSION['locale'] = 'en';
        $_SESSION['currency'] = 'SGD';

        $this->assertStringContainsString('SGD', \format_currency(100));
    }

    public function testCurrencySymbolUnknownCurrencyReturnsEmptyString(): void
    {
        $this->assertSame('', \currency_symbol('XXX'));
    }
}

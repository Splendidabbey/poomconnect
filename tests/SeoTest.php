<?php

declare(strict_types=1);

namespace PoomConnect\Tests;

use PHPUnit\Framework\TestCase;

require_once APP_ROOT . '/includes/i18n.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/seo.php';

final class SeoTest extends TestCase
{
    public function testDocumentTitleUsesBrandAndTaglineForHome(): void
    {
        $this->assertSame(
            'Poom Connect — Meet. Connect. Belong.',
            seo_document_title('Poom Connect', 'Poom Connect', 'Meet. Connect. Belong.')
        );
    }

    public function testDocumentTitleAppendsBrand(): void
    {
        $this->assertSame(
            'Sunset Mixer | Poom Connect',
            seo_document_title('Sunset Mixer', 'Poom Connect', 'Meet. Connect. Belong.')
        );
    }

    public function testTruncateRespectsLimit(): void
    {
        $this->assertSame('Hello world', seo_truncate('Hello   world', 160));
        $this->assertSame(160, mb_strlen(seo_truncate(str_repeat('a', 400), 160)));
    }

    public function testOgLocaleMap(): void
    {
        $this->assertSame('en_US', seo_og_locale('en'));
        $this->assertSame('th_TH', seo_og_locale('th'));
        $this->assertSame('ja_JP', seo_og_locale('ja'));
    }

    public function testPrivateScriptsAreNoindex(): void
    {
        $this->assertFalse(seo_is_indexable('/poomconnect/login.php'));
        $this->assertFalse(seo_is_indexable('/admin/users.php'));
        $this->assertFalse(seo_is_indexable('/organizer/dashboard.php'));
        $this->assertTrue(seo_is_indexable('/poomconnect/events.php'));
        $this->assertTrue(seo_is_indexable('/poomconnect/index.php'));
        $this->assertTrue(seo_is_indexable('/poomconnect/signup.php'));
    }

    public function testStripTrackingKeepsContentQuery(): void
    {
        $url = seo_strip_tracking('https://poomconnect.com/event?slug=mixer&utm_source=fb&fbclid=abc');
        $this->assertSame('https://poomconnect.com/event?slug=mixer', $url);
    }

    public function testUrlWithLang(): void
    {
        $en = seo_url_with_lang('https://poomconnect.com/events', 'en');
        $th = seo_url_with_lang('https://poomconnect.com/events', 'th');
        $this->assertSame('https://poomconnect.com/events', $en);
        $this->assertSame('https://poomconnect.com/events?lang=th', $th);
    }
}

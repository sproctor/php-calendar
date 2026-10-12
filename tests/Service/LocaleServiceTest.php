<?php

namespace App\Tests\Service;

use App\Service\LocaleService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class LocaleServiceTest extends TestCase
{
    public static function formattingLocaleCases(): iterable
    {
        yield 'user region wins' => ['en', 'en_GB', 'en-US,en;q=0.9', 'en_GB'];
        yield 'browser region' => ['en', null, 'en-GB,en;q=0.9', 'en_GB'];
        yield 'user without region uses browser' => ['en', 'en', 'en-AU', 'en_AU'];
        yield 'skips other languages' => ['fr', null, 'en-GB,fr-CA;q=0.8', 'fr_CA'];
        yield 'user locale for another language ignored' => ['fr', 'en_GB', 'fr-BE', 'fr_BE'];
        yield 'no region anywhere' => ['fr', null, 'fr,en-US;q=0.5', 'fr'];
        yield 'no header' => ['en', null, null, 'en'];
    }

    #[DataProvider('formattingLocaleCases')]
    public function testFormattingLocale(string $language, ?string $userLocale, ?string $acceptLanguage, string $expected): void
    {
        $request = new Request();
        $request->setLocale($language);
        if ($acceptLanguage !== null) {
            $request->headers->set('Accept-Language', $acceptLanguage);
        }
        $service = new LocaleService($this->createStub(KernelInterface::class));

        $this->assertSame($expected, $service->getFormattingLocale($request, $userLocale));
    }

    public static function translationCases(): iterable
    {
        yield 'exact language' => ['de', 'de'];
        yield 'regional form of a language' => ['de_AT', 'de'];
        yield 'exact region' => ['zh_TW', 'zh_TW'];
        yield 'other region of a language' => ['zh_CN', 'zh'];
        yield 'region-only translation' => ['pt_BR', 'pt_BR'];
        yield 'hyphenated' => ['pt-BR', 'pt_BR'];
        yield 'no translation' => ['xx', null];
    }

    #[DataProvider('translationCases')]
    public function testTranslationFor(string $locale, ?string $expected): void
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn(dirname(__DIR__, 2));
        $service = new LocaleService($kernel);

        $this->assertSame($expected, $service->translationFor($locale));
    }

    public static function pickLanguageCases(): iterable
    {
        yield 'browser language' => ['de-DE,de;q=0.9', 'de'];
        yield 'no header' => [null, 'en'];
        yield 'no translated language' => ['xx', 'en'];
    }

    #[DataProvider('pickLanguageCases')]
    public function testPickLanguage(?string $acceptLanguage, string $expected): void
    {
        $request = new Request();
        if ($acceptLanguage !== null) {
            $request->headers->set('Accept-Language', $acceptLanguage);
        }
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn(dirname(__DIR__, 2));
        $service = new LocaleService($kernel);

        $this->assertSame($expected, $service->pickLanguage($request, null));
    }

    public function testRegionalTranslationFormatsForItsRegion(): void
    {
        $request = new Request();
        $request->setLocale('zh_TW');
        $request->headers->set('Accept-Language', 'zh-CN');
        $service = new LocaleService($this->createStub(KernelInterface::class));

        $this->assertSame('zh_TW', $service->getFormattingLocale($request, null));
    }

    public function testRightToLeft(): void
    {
        $this->assertTrue(LocaleService::isRightToLeft('he'));
        $this->assertTrue(LocaleService::isRightToLeft('ar_EG'));
        $this->assertFalse(LocaleService::isRightToLeft('de'));
    }
}

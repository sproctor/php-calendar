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
}

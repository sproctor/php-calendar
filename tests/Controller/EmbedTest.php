<?php

namespace App\Tests\Controller;

use App\Tests\AppWebTestCase;

class EmbedTest extends AppWebTestCase
{
    public function testCalendarViewsCanBeEmbedded(): void
    {
        $calendar = $this->createCalendar();
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/month/2026/10", ['embed' => '1']);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Security-Policy', 'frame-ancestors *');
        $this->assertResponseNotHasHeader('X-Frame-Options');
        $this->assertSelectorExists('body[data-embed="1"]');
        $this->assertSelectorNotExists('nav.navbar');
    }

    public function testOtherPagesCanOnlyBeFramedBySameOrigin(): void
    {
        $calendar = $this->createCalendar();
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/month/2026/10");
        $this->assertResponseHeaderSame('X-Frame-Options', 'SAMEORIGIN');
        $this->assertSelectorExists('nav.navbar');

        $this->client->request('GET', '/login', ['embed' => '1']);
        $this->assertResponseHeaderSame('X-Frame-Options', 'SAMEORIGIN');
    }

    public function testEmbedCodeChoosesTheLook(): void
    {
        $calendar = $this->createCalendar();
        $url = "/en/calendar/{$calendar->getSlug()}/month/2026/10";

        $this->client->request('GET', $url, ['embed' => '1']);
        $this->assertSelectorTextContains('body', 'Open the full calendar');
        $this->assertSelectorNotExists('head style');
        $this->assertSelectorExists('html[data-bs-theme="light"]');

        $this->client->request('GET', $url, [
            'embed' => '1',
            'header' => '0',
            'bg' => 'transparent',
            'accent' => '#0a6',
            'font' => '"Fira Sans", Georgia, serif',
            'mode' => 'dark',
            'theme' => 'bootswatch:flatly',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('body', 'Open the full calendar');
        $css = $this->client->getCrawler()->filter('head style')->text();
        $this->assertStringContainsString('--bs-body-bg: transparent', $css);
        $this->assertStringContainsString('--bs-primary: #00aa66', $css);
        $this->assertStringContainsString('--bs-primary-rgb: 0, 170, 102', $css);
        $this->assertStringContainsString('--bs-body-font-family: "Fira Sans", Georgia, serif', $css);
        $this->assertSelectorExists('html[data-bs-theme="dark"]');
        $this->assertSelectorExists('link#phpc-theme[href*="bootswatch/flatly/bootstrap.min.css"]');
    }

    public function testInvalidEmbedOptionsAreIgnored(): void
    {
        $calendar = $this->createCalendar();
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/month/2026/10", [
            'embed' => '1',
            'bg' => 'red;}body{display:none',
            'accent' => 'red',
            'font' => 'x</style><script>alert(1)</script>',
            'mode' => 'sepia',
            'theme' => 'missing',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('head style');
        $this->assertSelectorNotExists('script:not([src])');
        $this->assertSelectorExists('html[data-bs-theme="light"]');
        $this->assertSelectorExists('link#phpc-theme[href*="bootstrap/bootstrap.min.css"]');
    }

    public function testEmbedOptionsOnlyApplyToEmbeddedPages(): void
    {
        $calendar = $this->createCalendar();
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/month/2026/10", ['accent' => '#0a6', 'header' => '0']);
        $this->assertSelectorNotExists('head style');
        $this->assertSelectorExists('nav.navbar');
    }

    public function testCalendarAdminWritesTheEmbedCode(): void
    {
        $calendar = $this->createCalendar('Club events');
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/admin");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#embed-tab');
        $builder = $this->client->getCrawler()->filter('.phpc-embed-builder');
        $this->assertSame('http://localhost/en/calendar/club-events/', $builder->attr('data-calendar'));
        $this->assertMatchesRegularExpression('%^http://localhost/static/embed\.js%', $builder->attr('data-script'));
        $this->assertSelectorExists('#embed-theme option[value="bootswatch:flatly"]');
    }
}

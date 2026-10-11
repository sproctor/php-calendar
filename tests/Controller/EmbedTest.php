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
}

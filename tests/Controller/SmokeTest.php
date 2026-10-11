<?php

namespace App\Tests\Controller;

use App\Tests\AppWebTestCase;

class SmokeTest extends AppWebTestCase
{
    public function testRedirectsToSetupWithoutCalendars(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseRedirects('/setup');
    }

    public function testAdminCanViewMonth(): void
    {
        $calendar = $this->createCalendar();
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/month/2026/10");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.navbar-brand', 'Test calendar');
    }

    public function testMonthStartsOnLocaleFirstDayOfWeek(): void
    {
        $calendar = $this->createCalendar();
        $this->login($this->createUser('admin', admin: true));
        $url = "/en/calendar/{$calendar->getSlug()}/month/2026/10";

        $this->client->request('GET', $url, server: ['HTTP_ACCEPT_LANGUAGE' => 'en-US']);
        $this->assertSelectorTextSame('.phpc-month-view thead th:nth-child(2)', 'Sun');

        $this->client->request('GET', $url, server: ['HTTP_ACCEPT_LANGUAGE' => 'en-GB']);
        $this->assertSelectorTextSame('.phpc-month-view thead th:nth-child(2)', 'Mon');
        // 2026-09-28 is the Monday before October 1st
        $this->assertSelectorTextContains('.phpc-month-view tbody tr:first-child td:nth-child(2)', 'Sept 28');
    }
}

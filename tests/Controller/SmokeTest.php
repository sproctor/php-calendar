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
        $this->client->request('GET', "/en/calendar/{$calendar->getCid()}/month/2026/10");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.navbar-brand', 'Test calendar');
    }
}

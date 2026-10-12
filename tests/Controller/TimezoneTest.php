<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Tests\AppWebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class TimezoneTest extends AppWebTestCase
{
    private const URL = '/en/calendar/club/month/2026/10';

    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $calendar = $this->createCalendar('Club');
        $calendar->setTimezone('America/New_York');
        $this->em()->flush();
    }

    private function note(): string
    {
        return $this->client->getCrawler()->filter('.phpc-timezone-note')->attr('data-timezone');
    }

    public function testCalendarTimezoneIsTheDefault(): void
    {
        $this->client->request('GET', self::URL);
        $this->assertSame('America/New_York', $this->note());
        $this->assertSelectorTextContains('.phpc-timezone-note', 'Eastern Time');
        $this->assertSelectorTextContains('.phpc-timezone-note', "the calendar's timezone");
        // Revealed by phpc.js when the browser's timezone differs
        $this->assertSelectorExists('.phpc-timezone-local[hidden]');
        $this->assertSelectorNotExists('.phpc-timezone-reset');
    }

    public function testCookieOverridesTheCalendar(): void
    {
        $this->client->getCookieJar()->set(new Cookie('phpc_timezone', 'Europe/Paris'));
        $this->client->request('GET', self::URL);
        $this->assertSame('Europe/Paris', $this->note());
        $this->assertSelectorTextContains('.phpc-timezone-note', 'chosen on this device');
        $this->assertSelectorExists('.phpc-timezone-reset');
    }

    public function testInvalidCookieIsIgnored(): void
    {
        $this->client->getCookieJar()->set(new Cookie('phpc_timezone', 'Mars/Olympus_Mons'));
        $this->client->request('GET', self::URL);
        $this->assertSame('America/New_York', $this->note());
    }

    public function testUserSettingBeatsTheCookie(): void
    {
        $user = $this->createUser('alice');
        $user->setTimezone('Asia/Tokyo');
        $this->em()->flush();
        $this->login($user);
        $this->client->getCookieJar()->set(new Cookie('phpc_timezone', 'Europe/Paris'));

        $this->client->request('GET', self::URL);
        $this->assertSame('Asia/Tokyo', $this->note());
        $this->assertSelectorExists('.phpc-timezone-note a[href="/en/settings"]');
        $this->assertSelectorNotExists('.phpc-timezone-local');
    }

    public function testTimesFollowTheChosenTimezone(): void
    {
        // 17:00 UTC is 1 PM in New York and 7 PM in Paris
        $this->createEvent($this->calendar, null, 'Meeting', '2026-10-15 17:00 UTC', '2026-10-15 18:00 UTC');

        $this->client->request('GET', '/en/calendar/club/day/2026/10/15');
        $this->assertSelectorTextContains('main', '1:00');

        $this->client->getCookieJar()->set(new Cookie('phpc_timezone', 'Europe/Paris'));
        $this->client->request('GET', '/en/calendar/club/day/2026/10/15');
        $this->assertSelectorTextContains('main', '7:00');
    }
}

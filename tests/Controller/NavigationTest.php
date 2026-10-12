<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Tests\AppWebTestCase;

class NavigationTest extends AppWebTestCase
{
    public function testMonthHeadingAndTodayButton(): void
    {
        $calendar = $this->createCalendar('Club');
        $this->client->request('GET', '/en/calendar/club/month/2027/3', server: ['HTTP_ACCEPT_LANGUAGE' => 'en-US']);
        $this->assertSelectorTextSame('h1', 'March 2027');
        $this->assertSelectorExists('a[href="/en/calendar/club/"]:contains("Today")');
        $this->assertSelectorExists('a[aria-label="Previous month"][href="/en/calendar/club/month/2027/2"]');

        $this->client->request('GET', '/fr/calendar/club/month/2027/3');
        $this->assertSelectorTextSame('h1', 'Mars 2027');
    }

    public function testPhoneAgendaListsEveryEventOfTheMonth(): void
    {
        $calendar = $this->createCalendar('Club');
        $calendar->setEventsMax(2);
        $this->em()->flush();
        foreach (['One', 'Two', 'Three'] as $i => $subject) {
            $this->createEvent($calendar, null, $subject, "2026-10-15 1$i:00", "2026-10-15 1$i:30");
        }
        // Outside the month, but inside the grid
        $this->createEvent($calendar, null, 'Next month', '2026-11-01 10:00', '2026-11-01 11:00');

        $crawler = $this->client->request('GET', '/en/calendar/club/month/2026/10');
        $this->assertSelectorTextContains('.phpc-month-view', 'More events');
        $agenda = $crawler->filter('.phpc-agenda')->text();
        foreach (['One', 'Two', 'Three'] as $subject) {
            $this->assertStringContainsString($subject, $agenda);
        }
        $this->assertStringNotContainsString('Next month', $agenda);
    }

    public function testCreateEventUsesTheViewedDate(): void
    {
        $this->createCalendar('Club');
        $this->login($this->createUser('admin', admin: true));

        $this->client->request('GET', '/en/calendar/club/month/2030/5');
        $this->assertSelectorExists('main a.btn-primary[href="/en/calendar/club/event/new/2030/05/01"]');
        // One "Create event" button per view; the navigation bar doesn't repeat it
        $this->assertSelectorNotExists('#phpc-navbar a[href*="/event/new"]');
        // Each day's shortcut says which day it's for
        $this->assertSelectorExists('a.phpc-add[aria-label="Create event on Friday, May 17"]');

        $this->client->request('GET', '/en/calendar/club/day/2030/05/17');
        $this->assertCount(1, $this->client->getCrawler()->filter('a[href="/en/calendar/club/event/new/2030/05/17"]'));
    }

    public function testEmptyDayOffersToCreateAnEvent(): void
    {
        $calendar = $this->createCalendar('Club');
        $calendar->setAnonPermission(Calendar::ANON_CREATE);
        $this->em()->flush();
        $this->client->request('GET', '/en/calendar/club/day/2030/05/17');
        $this->assertSelectorTextContains('main', 'No events on this day');
        $this->assertCount(1, $this->client->getCrawler()->filter('a[href="/en/calendar/club/event/new/2030/05/17"]'));
    }

    public function testOtherPagesLinkBackToTheLastCalendar(): void
    {
        $this->createCalendar('First');
        $this->createCalendar('Second');
        $this->login($this->createUser('user'));

        $this->client->request('GET', '/en/calendar/second/');
        $this->client->request('GET', '/en/settings');
        $this->assertSelectorTextContains('.navbar', 'Second');
    }

    public function testAdminLinksShareOneMenu(): void
    {
        $this->createCalendar('Club');
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', '/en/calendar/club/');
        $this->assertSelectorExists('.navbar .dropdown-menu a[href="/en/calendar/club/admin"]');
        $this->assertSelectorExists('.navbar .dropdown-menu a[href="/en/admin/"]');
    }

    public function testDayViewListsAllDayEventsFirst(): void
    {
        $calendar = $this->createCalendar('Club');
        $evening = $this->createEvent($calendar, null, 'Evening talk', '2026-10-22 19:00', '2026-10-22 20:00');
        $this->fresh($evening)->setDescription('Bring **questions**');
        $this->em()->flush();
        $this->createEvent($calendar, null, 'Retreat', '2026-10-21', '2026-10-23', \App\Entity\Occurrence::TIME_FULL_DAY);
        $this->createEvent($calendar, null, 'Late party', '2026-10-21 22:00', '2026-10-22 01:00');

        $crawler = $this->client->request('GET', '/en/calendar/club/day/2026/10/22', server: ['HTTP_ACCEPT_LANGUAGE' => 'en-US']);
        $sections = $crawler->filter('.phpc-day-list');
        $this->assertCount(2, $sections);
        $this->assertStringContainsString('Retreat', $sections->eq(0)->text());
        $this->assertStringContainsString('Oct 21, 2026 to Oct 23, 2026', $sections->eq(0)->text());
        $timed = $sections->eq(1)->filter('.phpc-day-item');
        $this->assertStringContainsString('Until 1:00', $timed->eq(0)->text());
        $this->assertStringContainsString('Late party', $timed->eq(0)->text());
        $this->assertStringContainsString('Evening talk', $timed->eq(1)->text());
        $this->assertStringContainsString('Bring <strong>questions</strong>', $timed->eq(1)->filter('.markdown')->html());
        // Anonymous visitors can't edit, so there are no selection boxes
        $this->assertSelectorNotExists('input[name="eid[]"]');
    }
}

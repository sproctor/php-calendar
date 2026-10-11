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
        $this->assertSelectorExists('#phpc-navbar a[href="/en/calendar/club/event/new/2030/05/01"]');

        $this->client->request('GET', '/en/calendar/club/day/2030/05/17');
        $this->assertSelectorExists('#phpc-navbar a[href="/en/calendar/club/event/new/2030/05/17"]');
    }

    public function testEmptyDayOffersToCreateAnEvent(): void
    {
        $calendar = $this->createCalendar('Club');
        $calendar->setAnonPermission(Calendar::ANON_CREATE);
        $this->em()->flush();
        $this->client->request('GET', '/en/calendar/club/day/2030/05/17');
        $this->assertSelectorTextContains('a.btn-outline-primary', 'Create an event on this day');
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
}

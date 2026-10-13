<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\View;
use App\Tests\AppWebTestCase;
use Sabre\VObject\Reader;

class ViewTest extends AppWebTestCase
{
    private Calendar $club;
    private Calendar $board;
    private Calendar $holidays;

    protected function setUp(): void
    {
        parent::setUp();
        $this->club = $this->createCalendar('Club');
        $this->board = $this->createCalendar('Board');
        $this->board->setAnonPermission(Calendar::ANON_NONE);
        $this->holidays = $this->createCalendar('Holidays');
        $this->em()->flush();
        $this->createEvent($this->club, null, 'Picnic', '2026-10-15 17:00 UTC', '2026-10-15 18:00 UTC');
        $this->createEvent($this->board, null, 'Board meeting', '2026-10-16 17:00 UTC', '2026-10-16 18:00 UTC');
        $this->createEvent($this->holidays, null, 'Columbus Day', '2026-10-12', '2026-10-12', 1);
    }

    private function createView(string $title, array $calendars): View
    {
        $view = (new View())->setTitle($title)->setSlug(strtolower(str_replace(' ', '-', $title)))->setTimezone('America/Chicago');
        $view->setCalendars(array_map(fn(array $c) => [$this->fresh($c[0]), $c[1]], $calendars));
        $this->em()->persist($view);
        $this->em()->flush();

        return $view;
    }

    public function testAdminCreatesAView(): void
    {
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', '/en/admin/views');
        $this->client->clickLink('Create view');
        $this->submit('Save', ['view_form[title]' => 'Everything']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-danger', 'Choose at least one calendar.');

        $this->submit('Save', [
            'view_form[title]' => 'Club and holidays',
            "view_form[calendar_{$this->club->getCid()}]" => true,
            "view_form[calendar_{$this->holidays->getCid()}]" => true,
            "view_form[color_{$this->holidays->getCid()}]" => '#cf222e',
        ]);
        $this->assertResponseRedirects('/en/admin/views');
        $view = $this->em()->getRepository(View::class)->findOneBy(['title' => 'Club and holidays']);
        $this->assertSame(['Club', 'Holidays'], array_map(fn(Calendar $c) => $c->getTitle(), $view->getCalendars()));
        $this->assertSame('#cf222e', $view->getCalendarColor($this->fresh($this->holidays)));
        $this->client->followRedirect();
        $this->assertSelectorTextContains('main table', 'Club and holidays');

        // Not the built-in view's name
        $this->client->request('GET', '/en/admin/view/new');
        $this->submit('Save', ['view_form[title]' => 'All', 'view_form[slug]' => 'all', "view_form[calendar_{$this->club->getCid()}]" => true]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testShowsItsCalendarsEventsMarkedByCalendar(): void
    {
        $this->createView('Club and holidays', [[$this->club, '#2da44e'], [$this->holidays, '#cf222e']]);

        $this->client->request('GET', '/en/view/club-and-holidays/month/2026/10');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'October 2026');
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Picnic', $html);
        $this->assertStringContainsString('Columbus Day', $html);
        $this->assertStringNotContainsString('Board meeting', $html);
        $this->assertSelectorExists('.phpc-occurrence[style*="border-inline-start: .35rem solid #cf222e"]');
        // The legend, and the view's timezone
        $this->assertSelectorTextContains('main', 'Holidays');
        $this->assertSelectorExists('.phpc-timezone-note[data-timezone="America/Chicago"]');
    }

    public function testVisitorsOnlySeeWhatTheyCouldSeeOnEachCalendar(): void
    {
        $this->createView('Everything', [[$this->club, '#2da44e'], [$this->board, '#8250df']]);
        $this->createView('Board only', [[$this->board, '#8250df']]);

        $this->client->request('GET', '/en/view/everything/month/2026/10');
        $this->assertStringNotContainsString('Board meeting', $this->client->getResponse()->getContent());
        $this->client->request('GET', '/en/view/board-only/');
        $this->assertResponseRedirects();

        $member = $this->createUser('pat');
        $this->grant($this->board, $member, 'read');
        $this->login($member);
        $this->client->request('GET', '/en/view/everything/month/2026/10');
        $this->assertStringContainsString('Board meeting', $this->client->getResponse()->getContent());
    }

    public function testDayViewAndSearch(): void
    {
        $this->createView('Club and holidays', [[$this->club, '#2da44e'], [$this->holidays, '#cf222e']]);
        $this->login($this->createUser('admin', admin: true));

        $this->client->request('GET', '/en/view/club-and-holidays/day/2026/10/12');
        $this->assertSelectorTextContains('.phpc-day-item', 'Columbus Day');
        $this->assertSelectorTextContains('.phpc-day-item', 'Holidays');
        // Deleting several events at once only works within a calendar
        $this->assertSelectorNotExists('input[name="eid[]"]');

        $this->client->request('GET', '/en/view/club-and-holidays/search', ['q' => 'day']);
        $this->assertSelectorTextContains('main table', 'Columbus Day');
    }

    public function testCreatingAnEventAsksWhichCalendar(): void
    {
        $this->createView('Club and holidays', [[$this->club, '#2da44e'], [$this->holidays, '#cf222e']]);
        $editor = $this->createUser('pat');
        $this->grant($this->club, $editor, 'read', 'create');
        $this->login($editor);

        // Only one calendar to add to: straight to its form
        $this->client->request('GET', '/en/view/club-and-holidays/event/new/2026/10/20');
        $this->assertResponseRedirects('/en/calendar/club/event/new/2026/10/20');

        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', '/en/view/club-and-holidays/event/new/2026/10/20');
        $this->assertSelectorExists('a[href="/en/calendar/holidays/event/new/2026/10/20"]');
        $this->assertSelectorExists('a[href="/en/calendar/club/event/new/2026/10/20"]');
    }

    public function testAllCalendars(): void
    {
        $this->client->request('GET', '/en/view/all/month/2026/10');
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Picnic', $html);
        $this->assertStringContainsString('Columbus Day', $html);
        $this->assertStringNotContainsString('Board meeting', $html);
        $this->assertSelectorTextContains('.navbar', 'All calendars');
    }

    public function testFeeds(): void
    {
        $this->createView('Everything', [[$this->club, '#2da44e'], [$this->board, '#8250df']]);
        $this->client->request('GET', '/view/everything/feed.ics');
        $public = array_map(fn($e) => (string) $e->SUMMARY, Reader::read($this->client->getResponse()->getContent())->select('VEVENT'));
        $this->assertContains('Picnic', $public);
        $this->assertNotContains('Board meeting', $public);

        // A board member's personal links, for this view and for all calendars
        $member = $this->createUser('pat');
        $this->grant($this->board, $member, 'read');
        $this->login($member);
        foreach (['everything', 'all'] as $slug) {
            $link = $this->client->request('GET', "/en/view/$slug/")->filter('#feed-personal')->attr('value');
            $this->client->restart();
            $this->client->request('GET', $link);
            $this->assertResponseIsSuccessful();
            $personal = array_map(fn($e) => (string) $e->SUMMARY, Reader::read($this->client->getResponse()->getContent())->select('VEVENT'));
            $this->assertContains('Board meeting', $personal, $slug);
            $this->login($this->fresh($member));
        }
    }

    public function testDeletingAViewKeepsItsCalendars(): void
    {
        $view = $this->createView('Everything', [[$this->club, '#2da44e']]);
        $this->login($this->createUser('admin', admin: true));
        $this->postWithToken('/en/admin/views', "/en/admin/view/{$view->getVid()}/delete");
        $this->assertResponseRedirects('/en/admin/views');
        $this->em()->clear();
        $this->assertNull($this->em()->find(View::class, $view->getVid()));
        $this->assertNotNull($this->em()->find(Calendar::class, $this->club->getCid()));
    }
}

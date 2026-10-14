<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\View;
use App\Tests\AppWebTestCase;

/**
 * The mini calendar, a small month for embedding in other sites
 */
class MiniCalendarTest extends AppWebTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Club');
        $this->createEvent($this->calendar, null, 'Picnic', '2026-10-15 17:00', '2026-10-15 18:00');
    }

    /**
     * The subjects listed under the grid for a day
     *
     * @return list<string>
     */
    private function dayEvents(string $day): array
    {
        return $this->client->getCrawler()->filter("[data-day-panel=\"$day\"] li a")->each(fn($a) => $a->text());
    }

    public function testShowsTheMonthWithEachDaysEvents(): void
    {
        $this->client->request('GET', '/en/calendar/club/mini/2026/10');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.phpc-mini', 'October 2026');
        $this->assertSame(['Picnic'], $this->dayEvents('2026-10-15'));
        $this->assertSame([], $this->dayEvents('2026-10-16'));
        $this->assertSelectorTextContains('[data-day-panel="2026-10-16"]', 'No events');
        $this->assertCount(1, $this->client->getCrawler()->filter('td[data-day="2026-10-15"] .phpc-mini-dots > span'));
        $this->assertCount(0, $this->client->getCrawler()->filter('td[data-day="2026-10-16"] .phpc-mini-dots > span'));

        // Day numbers open the full day page in a new tab
        $this->assertSelectorExists('td[data-day="2026-10-15"] a[href="/en/calendar/club/day/2026/10/15"][target="_blank"]');
        $this->assertSelectorExists('a[aria-label="Previous month"][href="/en/calendar/club/mini/2026/9"]');
        $this->assertSelectorExists('a[aria-label="Next month"][href="/en/calendar/club/mini/2026/11"]');
        $this->assertSelectorExists('a[href="/en/calendar/club/month/2026/10"][target="_blank"]');

        $this->client->request('GET', '/en/calendar/club/mini/');
        $this->assertResponseIsSuccessful();
        $this->client->request('GET', '/en/calendar/club/mini/2026/13');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLeavesOutEventsTheVisitorCannotSee(): void
    {
        $role = (new CalendarRole($this->fresh($this->calendar)))->setName('Board');
        $this->em()->persist($role);
        $category = (new Category($this->fresh($this->calendar)))->setName('Board only')->setRole($role);
        $this->em()->persist($category);
        $this->createEvent($this->calendar, null, 'Budget', '2026-10-20 17:00', '2026-10-20 18:00')
            ->setCategory($category);
        $this->em()->flush();

        $this->client->request('GET', '/en/calendar/club/mini/2026/10');
        $this->assertSame([], $this->dayEvents('2026-10-20'));
        $this->assertCount(0, $this->client->getCrawler()->filter('td[data-day="2026-10-20"] .phpc-mini-dots > span'));
        $this->assertStringNotContainsString('Budget', $this->client->getResponse()->getContent());
    }

    public function testKeepsTheFilter(): void
    {
        $category = (new Category($this->fresh($this->calendar)))->setName('Social');
        $this->em()->persist($category);
        $this->createEvent($this->calendar, null, 'Dance', '2026-10-15 19:00', '2026-10-15 20:00')
            ->setCategory($category);
        $this->em()->flush();
        $catid = $category->getCatid();

        $this->client->request('GET', "/en/calendar/club/mini/2026/10?category=$catid");
        $this->assertSame(['Dance'], $this->dayEvents('2026-10-15'));
        $this->assertSelectorExists("a[aria-label=\"Next month\"][href=\"/en/calendar/club/mini/2026/11?category=$catid\"]");
        $this->assertSelectorExists("td[data-day=\"2026-10-15\"] a[href=\"/en/calendar/club/day/2026/10/15?category=$catid\"]");
    }

    public function testCanBeEmbedded(): void
    {
        $this->client->request('GET', '/en/calendar/club/mini/2026/10', ['embed' => '1']);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Security-Policy', 'frame-ancestors *');
        $this->assertSelectorNotExists('nav.navbar');

        $this->client->request('GET', '/en/calendar/club/mini/2026/10');
        $this->assertResponseHeaderSame('X-Frame-Options', 'SAMEORIGIN');
    }

    public function testViewsHaveOne(): void
    {
        $other = $this->createCalendar('Other');
        $this->createEvent($other, null, 'Elsewhere', '2026-10-15 19:00', '2026-10-15 20:00');
        $view = (new View())->setTitle('Both')->setSlug('both')->setTimezone('UTC');
        $view->setCalendars([[$this->fresh($this->calendar), null], [$this->fresh($other), '#cf222e']]);
        $this->em()->persist($view);
        $this->em()->flush();

        $this->client->request('GET', '/en/view/both/mini/2026/10', ['embed' => '1']);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Security-Policy', 'frame-ancestors *');
        $this->assertSame(['Picnic', 'Elsewhere'], $this->dayEvents('2026-10-15'));
        $this->assertSelectorExists('td[data-day="2026-10-15"] .phpc-mini-dots > span[style="background: #cf222e;"]');
        $this->assertSelectorExists('a[aria-label="Next month"][href="/en/view/both/mini/2026/11"]');
    }
}

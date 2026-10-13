<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use App\Entity\View;
use App\Tests\AppWebTestCase;
use DateTimeImmutable;

/**
 * Showing only one category's or author's events on the month and day pages
 */
class EventFilterTest extends AppWebTestCase
{
    private Calendar $calendar;
    private User $alice;
    private User $bob;
    private Category $meetings;
    private Category $social;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Club');
        $this->alice = $this->createUser('alice');
        $this->bob = $this->createUser('bob');
        $this->meetings = $this->createCategory('Meetings');
        $this->social = $this->createCategory('Social');
        $this->createCategorizedEvent($this->alice, 'Standup', '2026-10-15', $this->meetings);
        $this->createCategorizedEvent($this->bob, 'Picnic', '2026-10-16', $this->social);
        $this->createCategorizedEvent($this->alice, 'Coffee', '2026-10-16', null);
    }

    private function createCategory(string $name, ?CalendarRole $role = null): Category
    {
        $category = (new Category($this->fresh($this->calendar)))->setName($name)->setColor('#1f6feb')->setRole($role);
        $this->em()->persist($category);
        $this->em()->flush();

        return $category;
    }

    private function createCategorizedEvent(User $owner, string $subject, string $date, ?Category $category): void
    {
        $event = $this->createEvent($this->calendar, $owner, $subject, "$date 17:00", "$date 18:00");
        $event->setCategory($category === null ? null : $this->fresh($category));
        $this->em()->flush();
    }

    /**
     * The subjects of the events in the month grid, sorted
     *
     * @return list<string>
     */
    private function monthEvents(): array
    {
        $subjects = $this->client->getCrawler()->filter('.phpc-occurrence')->each(fn($a) => $a->attr('data-bs-title'));
        sort($subjects);

        return $subjects;
    }

    /**
     * @return list<string>
     */
    private function options(string $select): array
    {
        return $this->client->getCrawler()->filter("#$select option")->each(fn($o) => $o->text());
    }

    public function testFiltersTheMonthByCategory(): void
    {
        $catid = $this->meetings->getCatid();
        $this->client->request('GET', "/en/calendar/club/month/2026/10?category=$catid");
        $this->assertResponseIsSuccessful();
        $this->assertSame(['Standup'], $this->monthEvents());
        $this->assertSelectorTextContains('.phpc-filter-notice', 'Showing only Meetings');
        $this->assertSelectorExists('#filter-category option[selected][value="' . $catid . '"]');

        // Moving around keeps the filter
        $this->assertSelectorExists("a[aria-label=\"Next month\"][href=\"/en/calendar/club/month/2026/11?category=$catid\"]");
        $this->assertSelectorExists("a.phpc-day-number[href=\"/en/calendar/club/day/2026/10/15?category=$catid\"]");
        // The legend's badge for the chosen category turns the filter off again
        $this->assertSelectorExists('[aria-label="Categories"] a[aria-current="true"][href="/en/calendar/club/month/2026/10"]');
        $this->assertSelectorExists("[aria-label=\"Categories\"] a[href=\"/en/calendar/club/month/2026/10?category={$this->social->getCatid()}\"]");

        $this->client->clickLink('Show all events');
        $this->assertSame(['Coffee', 'Picnic', 'Standup'], $this->monthEvents());
        $this->assertSelectorNotExists('a[aria-current="true"]');
    }

    public function testFiltersByAuthorAndByBoth(): void
    {
        $uid = $this->alice->getUid();
        $this->client->request('GET', "/en/calendar/club/month/2026/10?author=$uid");
        $this->assertSame(['Coffee', 'Standup'], $this->monthEvents());
        $this->assertSelectorTextContains('.phpc-filter-notice', 'Showing only By: alice');
        $this->assertSame(['Anyone', 'alice', 'bob'], $this->options('filter-author'));

        $this->client->request('GET', "/en/calendar/club/month/2026/10?author=$uid&category={$this->social->getCatid()}");
        $this->assertSame([], $this->monthEvents());
    }

    public function testDayViewKeepsTheFilter(): void
    {
        $uid = $this->bob->getUid();
        $this->client->request('GET', "/en/calendar/club/day/2026/10/16?author=$uid");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.phpc-day-list', 'Picnic');
        $this->assertSelectorTextNotContains('.phpc-day-list', 'Coffee');
        $this->assertSelectorExists("a[aria-label=\"Previous day\"][href=\"/en/calendar/club/day/2026/10/15?author=$uid\"]");
    }

    public function testOnlyOffersWhatTheVisitorCanSee(): void
    {
        $member = $this->createUser('member');
        $role = (new CalendarRole($this->fresh($this->calendar)))->setName('Board');
        $role->addUser($this->fresh($member));
        $this->em()->persist($role);
        $boardOnly = $this->createCategory('Board only', $role);
        $this->createCategorizedEvent($this->createUser('carol'), 'Budget', '2026-10-20', $boardOnly);
        $this->createCategorizedEvent($this->createUser('dave'), 'Surprise', '2026-10-21', null);
        $this->em()->getRepository(Event::class)->findOneBy(['subject' => 'Surprise'])
            ->setPubtime(new DateTimeImmutable('+1 year'));
        $this->em()->flush();

        $this->client->request('GET', '/en/calendar/club/month/2026/10?category=' . $boardOnly->getCatid());
        // A category the visitor can't see doesn't filter, and isn't offered
        $this->assertSame(['Coffee', 'Picnic', 'Standup'], $this->monthEvents());
        $this->assertSelectorNotExists('a[aria-current="true"]');
        $this->assertSame(['All categories', 'Meetings', 'Social'], $this->options('filter-category'));
        // Nor are the authors of events they can't see
        $this->assertSame(['Anyone', 'alice', 'bob'], $this->options('filter-author'));

        $this->login($this->fresh($member));
        $this->client->request('GET', '/en/calendar/club/month/2026/10?category=' . $boardOnly->getCatid());
        $this->assertSame(['Budget'], $this->monthEvents());
        $this->assertSame(['Anyone', 'alice', 'bob', 'carol'], $this->options('filter-author'));
    }

    public function testFiltersAView(): void
    {
        $other = $this->createCalendar('Other');
        $this->createEvent($other, $this->bob, 'Elsewhere', '2026-10-15 19:00', '2026-10-15 20:00');
        $view = (new View())->setTitle('Both')->setSlug('both')->setTimezone('UTC');
        $view->setCalendars([[$this->fresh($this->calendar), null], [$this->fresh($other), null]]);
        $this->em()->persist($view);
        $this->em()->flush();

        $this->client->request('GET', '/en/view/both/month/2026/10?author=' . $this->bob->getUid());
        $this->assertSame(['Elsewhere', 'Picnic'], $this->monthEvents());
        $this->assertSelectorExists('#filter-category optgroup[label="Club"]');
    }
}

<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Entity\View;
use App\Tests\AppWebTestCase;

/**
 * Full-day events in a category with Mark the day set shade their days, for holidays and days off
 */
class MarkedDaysTest extends AppWebTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Club');
    }

    private function createCategory(string $name, string $color, bool $marksDay, ?CalendarRole $role = null): Category
    {
        $category = (new Category($this->fresh($this->calendar)))
            ->setName($name)->setColor($color)->setMarksDay($marksDay)->setRole($role);
        $this->em()->persist($category);
        $this->em()->flush();

        return $category;
    }

    private function createFullDayEvent(string $subject, string $start, string $end, ?Category $category): void
    {
        $event = $this->createEvent($this->calendar, null, $subject, $start, $end, Occurrence::TIME_FULL_DAY);
        $event->setCategory($category === null ? null : $this->fresh($category));
        $this->em()->flush();
    }

    /**
     * The days of the month grid that are shaded, as day-of-month numbers, for the month shown
     *
     * @return list<string>
     */
    private function markedDays(): array
    {
        return $this->client->getCrawler()->filter('td.phpc-marked:not(.phpc-shadow) .phpc-day-number')
            ->each(fn($a) => trim($a->text()));
    }

    public function testFullDayEventsInAMarkingCategoryShadeTheirDays(): void
    {
        $holidays = $this->createCategory('Holidays', '#cf222e', true);
        $birthdays = $this->createCategory('Birthdays', '#1f6feb', false);
        $this->createFullDayEvent('Fall break', '2026-10-12', '2026-10-13', $holidays);
        $this->createFullDayEvent('Ann', '2026-10-22', '2026-10-22', $birthdays);
        $this->createFullDayEvent('Cleanup', '2026-10-23', '2026-10-23', null);
        $event = $this->createEvent($this->calendar, null, 'Half day', '2026-10-20 17:00', '2026-10-20 18:00');
        $event->setCategory($this->fresh($holidays));
        $this->em()->flush();

        $this->client->request('GET', '/en/calendar/club/month/2026/10');
        $this->assertResponseIsSuccessful();
        $this->assertSame(['12', '13'], $this->markedDays());
        $cell = $this->client->getCrawler()->filter('td.phpc-marked')->first();
        $this->assertStringContainsString('--phpc-mark: #cf222e', $cell->attr('style'));
        $this->assertSame('Holidays', $cell->filter('.visually-hidden')->text());
        // The phone layout names the category on the day
        $this->assertSelectorTextContains('#agenda-2026-10-12', 'Holidays');
        $this->assertSelectorTextNotContains('#agenda-2026-10-22', 'Birthdays');

        $this->client->request('GET', '/en/calendar/club/day/2026/10/13');
        $this->assertSelectorTextContains('h1 + .badge', 'Holidays');
        $this->client->request('GET', '/en/calendar/club/day/2026/10/20');
        $this->assertSelectorNotExists('h1 + .badge');
    }

    public function testCategoriesHiddenFromTheVisitorDoNotMarkDays(): void
    {
        $member = $this->createUser('member');
        $role = (new CalendarRole($this->fresh($this->calendar)))->setName('Staff');
        $role->addUser($this->fresh($member));
        $this->em()->persist($role);
        $staffDays = $this->createCategory('Staff days off', '#cf222e', true, $role);
        $this->createFullDayEvent('Retreat', '2026-10-15', '2026-10-15', $staffDays);

        $this->client->request('GET', '/en/calendar/club/month/2026/10');
        $this->assertSame([], $this->markedDays());

        $this->login($this->fresh($member));
        $this->client->request('GET', '/en/calendar/club/month/2026/10');
        $this->assertSame(['15'], $this->markedDays());
    }

    public function testViewsShowTheirCalendarsMarkedDays(): void
    {
        $this->createFullDayEvent('Fall break', '2026-10-12', '2026-10-12', $this->createCategory('Holidays', '#cf222e', true));
        $other = $this->createCalendar('Other');
        $view = (new View())->setTitle('Both')->setSlug('both')->setTimezone('UTC');
        $view->setCalendars([[$this->fresh($this->calendar), null], [$this->fresh($other), '#1f6feb']]);
        $this->em()->persist($view);
        $this->em()->flush();

        $this->client->request('GET', '/en/view/both/month/2026/10');
        $this->assertSame(['12'], $this->markedDays());
        $this->client->request('GET', '/en/view/all/month/2026/10');
        $this->assertSame(['12'], $this->markedDays());
    }
}

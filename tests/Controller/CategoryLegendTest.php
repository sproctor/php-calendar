<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\View;
use App\Tests\AppWebTestCase;

/**
 * The month view's legend of which color is which category
 */
class CategoryLegendTest extends AppWebTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Club');
    }

    private function createCategory(Calendar $calendar, string $name, ?CalendarRole $role = null): Category
    {
        $category = (new Category($this->fresh($calendar)))->setName($name)->setColor('#1f6feb')->setRole($role);
        $this->em()->persist($category);
        $this->em()->flush();

        return $category;
    }

    /**
     * The legend's groups as their label followed by their categories' names
     *
     * @return list<list<string>>
     */
    private function legend(): array
    {
        return $this->client->getCrawler()->filter('[role="group"][aria-label="Categories"] > span')
            ->each(fn($group) => $group->children()->each(fn($span) => trim($span->text())));
    }

    public function testListsTheCategoriesTheVisitorCanSee(): void
    {
        $member = $this->createUser('member');
        $role = (new CalendarRole($this->fresh($this->calendar)))->setName('Board');
        $role->addUser($this->fresh($member));
        $this->em()->persist($role);
        $this->createCategory($this->calendar, 'Meetings');
        $this->createCategory($this->calendar, 'Board only', $role);

        $this->client->request('GET', '/en/calendar/club/month/2026/10');
        $this->assertResponseIsSuccessful();
        $this->assertSame([['Categories', 'Meetings']], $this->legend());
        $this->assertSelectorExists('[aria-label="Categories"] .badge[style*="background: #1f6feb"]');

        $this->login($this->fresh($member));
        $this->client->request('GET', '/en/calendar/club/month/2026/10');
        $this->assertSame([['Categories', 'Board only', 'Meetings']], $this->legend());
    }

    public function testCalendarsWithoutCategoriesHaveNoLegend(): void
    {
        $this->client->request('GET', '/en/calendar/club/month/2026/10');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[aria-label="Categories"]');
    }

    public function testViewsListEachReadableCalendarsCategories(): void
    {
        $board = $this->createCalendar('Board');
        $board->setAnonPermission(Calendar::ANON_NONE);
        $empty = $this->createCalendar('Empty');
        $this->em()->flush();
        $this->createCategory($this->calendar, 'Meetings');
        $this->createCategory($board, 'Budget');
        $view = (new View())->setTitle('Everything')->setSlug('everything')->setTimezone('UTC');
        $view->setCalendars([[$this->fresh($this->calendar), null], [$this->fresh($board), null], [$this->fresh($empty), null]]);
        $this->em()->persist($view);
        $this->em()->flush();

        $this->client->request('GET', '/en/view/everything/month/2026/10');
        $this->assertSame([['Club', 'Meetings']], $this->legend());

        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', '/en/view/everything/month/2026/10');
        $this->assertSame([['Club', 'Meetings'], ['Board', 'Budget']], $this->legend());
    }
}

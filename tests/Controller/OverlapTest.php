<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Tests\AppWebTestCase;

class OverlapTest extends AppWebTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Room');
        $this->calendar->setTimezone('UTC');
        $this->em()->flush();
        $this->createEvent($this->calendar, null, 'Choir', '2026-10-15 18:00 UTC', '2026-10-15 19:00 UTC');
        $this->login($this->createUser('admin', admin: true));
    }

    private function policy(int $policy): void
    {
        $this->fresh($this->calendar)->setOverlapPolicy($policy);
        $this->em()->flush();
    }

    /**
     * Submits the new event form for a timed event on $date from $from to $to
     */
    private function createAt(string $date, string $from, string $to, array $extra = []): void
    {
        $this->client->request('GET', "/en/calendar/room/event/new/" . str_replace('-', '/', $date));
        $this->submit('Save', [
            'event_form[subject]' => 'Booking',
            'event_form[times][time_type]' => Occurrence::TIME_NORMAL,
            'event_form[times][start][date]' => $date,
            'event_form[times][start][time]' => $from,
            'event_form[times][end][date]' => $date,
            'event_form[times][end][time]' => $to,
        ] + $extra);
    }

    private function bookings(): int
    {
        return $this->em()->getRepository(Event::class)->count(['subject' => 'Booking']);
    }

    public function testAllowedByDefault(): void
    {
        $this->createAt('2026-10-15', '18:30', '19:30');
        $this->assertResponseRedirects();
        $this->assertSame(1, $this->bookings());
    }

    public function testDisallowed(): void
    {
        $this->policy(Calendar::OVERLAP_DISALLOW);
        $this->createAt('2026-10-15', '18:30', '19:30');
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-danger', 'This is at the same time as: Choir (');
        $this->assertSelectorNotExists('input[name="event_form[allow_overlap]"]');
        $this->assertSame(0, $this->bookings());

        // Ending as the other starts is fine
        $this->createAt('2026-10-15', '17:00', '18:00');
        $this->assertResponseRedirects();
        $this->assertSame(1, $this->bookings());
    }

    public function testWarned(): void
    {
        $this->policy(Calendar::OVERLAP_WARN);
        $this->client->request('GET', '/en/calendar/room/event/new/2026/10/15');
        $this->assertSelectorNotExists('input[name="event_form[allow_overlap]"]');

        $this->createAt('2026-10-15', '18:30', '19:30');
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-warning', 'Choir');
        $this->assertSame(0, $this->bookings());

        $this->submit('Save', ['event_form[allow_overlap]' => true]);
        $this->assertResponseRedirects();
        $this->assertSame(1, $this->bookings());
    }

    public function testAllDayAndToBeAnnounced(): void
    {
        $this->policy(Calendar::OVERLAP_DISALLOW);
        $this->createEvent($this->calendar, null, 'Closed', '2026-10-20', '2026-10-21', Occurrence::TIME_FULL_DAY);
        $this->createEvent($this->calendar, null, 'Speaker', '2026-10-22', '2026-10-22', Occurrence::TIME_TBA);

        // An all-day event takes up its days
        $this->createAt('2026-10-21', '09:00', '10:00');
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-danger', 'Closed');
        // A time to be announced doesn't
        $this->createAt('2026-10-22', '09:00', '10:00');
        $this->assertResponseRedirects();
    }

    public function testRepeatingSeriesIsChecked(): void
    {
        $this->policy(Calendar::OVERLAP_DISALLOW);
        // Thursdays at 18:30: the first one hits the choir
        $this->createAt('2026-10-08', '18:30', '19:30', [
            'event_form[repeats]' => 'W',
            'event_form[frequency]' => '1',
            'event_form[until]' => '2026-10-29',
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-danger', 'Choir');
    }

    public function testHiddenEventsStayHidden(): void
    {
        $this->policy(Calendar::OVERLAP_DISALLOW);
        $role = new CalendarRole($this->fresh($this->calendar));
        $role->setName('Board');
        $category = (new Category($this->fresh($this->calendar)))->setName('Board only')->setRole($role);
        $this->em()->persist($role);
        $this->em()->persist($category);
        $this->createEvent($this->calendar, null, 'Secret meeting', '2026-10-16 18:00 UTC', '2026-10-16 19:00 UTC')
            ->setCategory($category);
        $this->em()->flush();
        $member = $this->createUser('pat');
        $this->grant($this->calendar, $member, 'read', 'create');
        $this->login($member);

        $this->createAt('2026-10-16', '18:30', '19:30');
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-danger', "An event you can't see");
        $this->assertSelectorTextNotContains('.alert-danger', 'Secret meeting');
    }

    public function testEditingAnEvent(): void
    {
        $this->policy(Calendar::OVERLAP_DISALLOW);
        $choir = $this->em()->getRepository(Event::class)->findOneBy(['subject' => 'Choir']);

        // Its own dates don't count
        $this->client->request('GET', "/en/event/{$choir->getEid()}/edit");
        $this->submit('Save', [
            'event_form[modify]' => true,
            'event_form[times][time_type]' => Occurrence::TIME_NORMAL,
            'event_form[times][start][date]' => '2026-10-15',
            'event_form[times][start][time]' => '18:30',
            'event_form[times][end][date]' => '2026-10-15',
            'event_form[times][end][time]' => '19:30',
        ]);
        $this->assertResponseRedirects();

        // Adding a date that hits another event
        $this->createEvent($this->calendar, null, 'Yoga', '2026-10-17 10:00 UTC', '2026-10-17 11:00 UTC');
        $this->client->request('GET', "/en/event/{$choir->getEid()}/occurrence/new");
        $this->submit('Save', [
            'form[times][time_type]' => Occurrence::TIME_NORMAL,
            'form[times][start][date]' => '2026-10-17',
            'form[times][start][time]' => '10:30',
            'form[times][end][date]' => '2026-10-17',
            'form[times][end][time]' => '11:30',
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-danger', 'Yoga');
    }

    public function testCalendarSetting(): void
    {
        $this->client->request('GET', '/en/calendar/room/admin');
        $this->submit('Save', ['calendar_form[overlap_policy]' => (string) Calendar::OVERLAP_WARN]);
        $this->assertSame(Calendar::OVERLAP_WARN, $this->fresh($this->calendar)->getOverlapPolicy());
    }
}

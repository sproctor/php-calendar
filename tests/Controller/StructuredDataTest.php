<?php

namespace App\Tests\Controller;

use App\Entity\CalendarRole;
use App\Entity\Calendar;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Service\EventStructuredData;
use App\Tests\AppWebTestCase;
use DateTimeImmutable;

class StructuredDataTest extends AppWebTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Club');
        $this->calendar->setTimezone('America/New_York');
        $this->em()->flush();
    }

    /**
     * The JSON-LD on $event's page, or null
     */
    private function jsonLd(Event $event): ?array
    {
        $crawler = $this->client->request('GET', "/en/event/{$event->getEid()}");
        $this->assertResponseIsSuccessful();
        $script = $crawler->filter('head script[type="application/ld+json"]');

        return $script->count() === 0 ? null : json_decode($script->text(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function testTimedEventWithAPlace(): void
    {
        $event = $this->createEvent($this->calendar, null, 'Picnic', '2026-10-15 17:00 UTC', '2026-10-15 19:00 UTC');
        $event->setLocation('Riverside Park');
        $event->setDescription("Bring **snacks**.\n\n- Plates\n- Cups");
        $this->em()->flush();

        $data = $this->jsonLd($event);
        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Event', $data['@type']);
        $this->assertSame('Picnic', $data['name']);
        // In the calendar's timezone
        $this->assertSame('2026-10-15T13:00:00-04:00', $data['startDate']);
        $this->assertSame('2026-10-15T15:00:00-04:00', $data['endDate']);
        $this->assertSame(['@type' => 'Place', 'name' => 'Riverside Park', 'address' => 'Riverside Park'], $data['location']);
        $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $data['eventAttendanceMode']);
        $this->assertSame("Bring snacks.\nPlates\nCups", $data['description']);
        $this->assertSame('Club', $data['organizer']['name']);
        $this->assertStringEndsWith("/en/event/{$event->getEid()}", $data['url']);
    }

    public function testFullDayOnlineEvent(): void
    {
        $event = $this->createEvent($this->calendar, null, 'Retreat', '2026-10-15', '2026-10-17', Occurrence::TIME_FULL_DAY);
        $event->setLocation('https://meet.example.com/retreat');
        $this->em()->flush();

        $data = $this->jsonLd($event);
        $this->assertSame('2026-10-15', $data['startDate']);
        $this->assertSame('2026-10-17', $data['endDate']);
        $this->assertSame(['@type' => 'VirtualLocation', 'url' => 'https://meet.example.com/retreat'], $data['location']);
        $this->assertSame('https://schema.org/OnlineEventAttendanceMode', $data['eventAttendanceMode']);
        $this->assertArrayNotHasKey('description', $data);
    }

    public function testRepeatingEventListsUpcomingDates(): void
    {
        $event = $this->createEvent($this->calendar, null, 'Weekly', '2026-01-01 17:00 UTC', '2026-01-01 18:00 UTC');
        for ($week = 1; $week < 30; $week++) {
            $start = new DateTimeImmutable("2026-01-01 17:00 UTC +$week weeks");
            new Occurrence($this->fresh($event), $start, $start->modify('+1 hour'), Occurrence::TIME_NORMAL);
        }
        $this->em()->flush();
        $service = static::getContainer()->get(EventStructuredData::class);

        $data = $service->build($this->fresh($event), new DateTimeImmutable('2026-03-01 UTC'));
        $this->assertCount(EventStructuredData::MAX_OCCURRENCES, $data);
        $this->assertStringStartsWith('2026-03-05', $data[0]['startDate']);

        // All past: just the last one
        $data = $service->build($this->fresh($event), new DateTimeImmutable('2027-01-01 UTC'));
        $this->assertStringStartsWith('2026-07-23', $data['startDate']);
    }

    public function testOnlyForEventsAnyoneCanSee(): void
    {
        $this->login($this->createUser('admin', admin: true));

        $unpublished = $this->createEvent($this->calendar, null, 'Soon');
        $unpublished->setPubtime(new DateTimeImmutable('+1 year'));
        $role = new CalendarRole($this->fresh($this->calendar));
        $role->setName('Board');
        $category = (new Category($this->fresh($this->calendar)))->setName('Board only')->setRole($role);
        $this->em()->persist($role);
        $this->em()->persist($category);
        $restricted = $this->createEvent($this->calendar, null, 'Board meeting');
        $restricted->setCategory($category);
        $this->em()->flush();
        $this->assertNull($this->jsonLd($unpublished));
        $this->assertNull($this->jsonLd($restricted));

        $private = $this->createCalendar('Private');
        $private->setAnonPermission(Calendar::ANON_NONE);
        $this->em()->flush();
        $this->assertNull($this->jsonLd($this->createEvent($private, null, 'Secret')));
    }

    public function testCantBreakOutOfTheScript(): void
    {
        $event = $this->createEvent($this->calendar, null, '</script><script>alert(1)</script>');

        $this->client->request('GET', "/en/event/{$event->getEid()}");
        $this->assertStringNotContainsString('<script>alert', $this->client->getResponse()->getContent());
        $this->assertSame('</script><script>alert(1)</script>', $this->jsonLd($event)['name']);
    }
}

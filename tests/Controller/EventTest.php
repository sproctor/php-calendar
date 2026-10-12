<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Entity\FieldDefinition;
use App\Entity\Occurrence;
use App\Tests\AppWebTestCase;

class EventTest extends AppWebTestCase
{
    public function testCreateRepeatingEvent(): void
    {
        $calendar = $this->createCalendar();
        $this->login($this->createUser('admin', admin: true));

        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/event/new/2026/10/15");
        $this->assertResponseIsSuccessful();
        $this->submit('Save', [
            'event_form[subject]' => 'Book club',
            'event_form[description]' => 'Bring **snacks**',
            'event_form[times][time_type]' => Occurrence::TIME_NORMAL,
            'event_form[times][start][date]' => '2026-10-15',
            'event_form[times][start][time]' => '19:00',
            'event_form[times][end][date]' => '2026-10-15',
            'event_form[times][end][time]' => '21:00',
            'event_form[repeats]' => 'W',
            'event_form[frequency]' => '2',
            'event_form[until]' => '2026-11-30',
        ]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h3', 'Book club');

        $event = $this->em()->getRepository(Event::class)->findOneBy(['subject' => 'Book club']);
        $starts = $event->getOccurrences()->map(fn(Occurrence $o) => $o->getStart()->format('m-d H:i'))->toArray();
        $this->assertSame(['10-15 19:00', '10-29 19:00', '11-12 19:00', '11-26 19:00'], array_values($starts));
    }

    public function testRejectsEndBeforeStart(): void
    {
        $calendar = $this->createCalendar();
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/event/new/2026/10/15");
        $this->submit('Save', [
            'event_form[subject]' => 'Backwards',
            'event_form[times][time_type]' => Occurrence::TIME_FULL_DAY,
            'event_form[times][start_date]' => '2026-10-15',
            'event_form[times][end_date]' => '2026-10-14',
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback, .form-error-message', 'end');
    }

    public function testEditKeepsOccurrencesUnlessAsked(): void
    {
        $calendar = $this->createCalendar();
        $admin = $this->createUser('admin', admin: true);
        $event = $this->createEvent($calendar, $admin, 'Original');
        $this->login($admin);

        $this->client->request('GET', "/en/event/{$event->getEid()}/edit");
        $this->submit('Save', [
            'event_form[subject]' => 'Renamed',
            'event_form[times][start][time]' => '08:00',
        ]);
        $this->assertResponseRedirects();
        $this->em()->clear();
        $event = $this->em()->find(Event::class, $event->getEid());
        $this->assertSame('Renamed', $event->getSubject());
        $this->assertNotNull($event->getModified());
        $this->assertSame('17:00', $event->getOccurrences()->first()->getStart()->format('H:i'));
    }

    public function testOccurrenceLifecycle(): void
    {
        $calendar = $this->createCalendar();
        $admin = $this->createUser('admin', admin: true);
        $event = $this->createEvent($calendar, $admin);
        $eid = $event->getEid();
        $this->login($admin);

        $this->client->request('GET', "/en/event/$eid/occurrence/new");
        $this->submit('Save', [
            'form[times][time_type]' => Occurrence::TIME_TBA,
            'form[times][start_date]' => '2026-10-20',
            'form[times][end_date]' => '2026-10-20',
        ]);
        $this->assertResponseRedirects("/en/event/$eid");
        $this->em()->clear();
        $occurrences = $this->em()->find(Event::class, $eid)->getOccurrences();
        $this->assertCount(2, $occurrences);
        $added = $occurrences->last();
        $this->assertSame(Occurrence::TIME_TBA, $added->getTimeType());

        $this->client->request('GET', "/en/occurrence/{$added->getOid()}/edit");
        $this->submit('Save', ['form[times][start_date]' => '2026-10-21', 'form[times][end_date]' => '2026-10-21']);
        $this->em()->clear();
        $this->assertSame('2026-10-21', $this->em()->find(Occurrence::class, $added->getOid())->getStart()->format('Y-m-d'));

        // Removing both occurrences removes the event
        foreach ($occurrences as $occurrence) {
            $oid = $occurrence->getOid();
            $this->postWithToken("/en/event/$eid", "/en/occurrence/$oid/delete");
        }
        $this->assertResponseRedirects("/en/calendar/{$calendar->getSlug()}/");
        $this->em()->clear();
        $this->assertNull($this->em()->find(Event::class, $eid));
    }

    public function testCustomFields(): void
    {
        $calendar = $this->createCalendar();
        $field = (new FieldDefinition($calendar))->setName('Room')->setIsRequired(true)->setFormat('[A-Z]\d{3}');
        $this->em()->persist($field);
        $this->em()->flush();
        $this->login($this->createUser('admin', admin: true));

        $values = [
            'event_form[subject]' => 'Lecture',
            'event_form[times][start][date]' => '2026-10-15',
            'event_form[times][end][date]' => '2026-10-15',
        ];
        $name = "event_form[field_{$field->getFid()}]";
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/event/new/2026/10/15");
        $this->submit('Save', $values + [$name => 'basement']);
        $this->assertResponseStatusCodeSame(422);

        $this->submit('Save', $values + [$name => 'B101']);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('dl', 'Room');
        $this->assertSelectorTextContains('dl', 'B101');
    }

    public function testSearch(): void
    {
        $calendar = $this->createCalendar();
        $this->createEvent($calendar, null, 'Annual picnic');
        $this->createEvent($calendar, null, 'Board meeting');
        $cid = $calendar->getCid();
        $slug = $calendar->getSlug();

        $this->client->request('GET', "/en/calendar/$slug/search", ['q' => 'picnic']);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table', 'Annual picnic');
        $this->assertSelectorTextNotContains('table', 'Board meeting');

        $this->client->request('GET', "/en/calendar/$slug/search", ['q' => 'picnic', 'from' => '2026-11-01']);
        $this->assertSelectorTextContains('p', 'No events matched');
    }

    public function testMonthShowsWeekNumbers(): void
    {
        $calendar = $this->createCalendar();
        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/month/2026/10", server: ['HTTP_ACCEPT_LANGUAGE' => 'en-GB']);
        // ISO weeks 40 to 44 cover October 2026, shown without links
        $this->assertSelectorTextSame('.phpc-month-view tbody tr:first-child th', '40');
        $this->assertSelectorTextSame('.phpc-month-view tbody tr:last-child th', '44');
        $this->assertSelectorNotExists('.phpc-month-view tbody th a');
    }

    public function testTimesFollowTheViewersTimezone(): void
    {
        $calendar = $this->createCalendar();
        $calendar->setTimezone('America/New_York');
        $this->em()->flush();
        $user = $this->createUser('tokyo');
        $user->setTimezone('Asia/Tokyo');
        $this->em()->flush();
        // 17:00 in New York is 21:00 UTC, 06:00 the next day in Tokyo
        $event = $this->createEvent($calendar, null, 'Call', '2026-10-15 21:00 UTC', '2026-10-15 22:00 UTC');

        $this->client->request('GET', "/en/event/{$event->getEid()}");
        $this->assertSelectorTextContains('.phpc-occ', '5:00');

        $this->login($user);
        $this->client->request('GET', "/en/event/{$event->getEid()}");
        $this->assertSelectorTextContains('.phpc-occ', 'Oct 16, 2026');
        $this->assertSelectorTextContains('.phpc-occ', '6:00');
    }
}

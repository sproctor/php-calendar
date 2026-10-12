<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Tests\AppWebTestCase;
use DateTimeImmutable;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class FeedTest extends AppWebTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Club');
        $this->calendar->setTimezone('America/New_York');
        $this->em()->flush();
    }

    private function fetch(string $url): VCalendar
    {
        $this->client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/calendar; charset=utf-8');

        return Reader::read($this->client->getResponse()->getContent());
    }

    /**
     * SUMMARY => VEVENT
     */
    private function events(VCalendar $feed): array
    {
        $events = [];
        foreach ($feed->select('VEVENT') as $vevent) {
            $events[(string) $vevent->SUMMARY] = $vevent;
        }

        return $events;
    }

    private function boardOnlyCategory(?User $member = null): Category
    {
        $role = new CalendarRole($this->fresh($this->calendar));
        $role->setName('Board');
        if ($member !== null) {
            $role->addUser($this->fresh($member));
        }
        $category = (new Category($this->fresh($this->calendar)))->setName('Board only')->setRole($role);
        $this->em()->persist($role);
        $this->em()->persist($category);
        $this->em()->flush();

        return $category;
    }

    /**
     * The personal feed link in the subscribe dialog
     */
    private function personalLink(): string
    {
        $crawler = $this->client->request('GET', '/en/calendar/club/');

        return $crawler->filter('#feed-personal')->attr('value');
    }

    public function testPublicFeed(): void
    {
        $near = (new DateTimeImmutable('+1 week'))->format('Y-m-d');
        $timed = $this->createEvent($this->calendar, null, 'Picnic', "$near 17:00 UTC", "$near 19:00 UTC");
        $timed->setLocation('Riverside Park')->setDescription('Bring **snacks**');
        $this->createEvent($this->calendar, null, 'Retreat', $near, (new DateTimeImmutable("$near +2 days"))->format('Y-m-d'), Occurrence::TIME_FULL_DAY);
        $this->createEvent($this->calendar, null, 'Speaker', $near, $near, Occurrence::TIME_TBA);
        $this->createEvent($this->calendar, null, 'Long ago', '2020-01-01 12:00 UTC', '2020-01-01 13:00 UTC');
        $hidden = $this->createEvent($this->calendar, null, 'Not yet published', "$near 12:00 UTC", "$near 13:00 UTC");
        $hidden->setPubtime(new DateTimeImmutable('+1 year'));
        $restricted = $this->createEvent($this->calendar, null, 'Board meeting', "$near 12:00 UTC", "$near 13:00 UTC");
        $restricted->setCategory($this->boardOnlyCategory());
        $this->em()->flush();

        $feed = $this->fetch('/calendar/club/feed.ics');
        $this->assertSame([], $feed->validate(), 'The feed is valid iCalendar');
        $this->assertSame('Club', (string) $feed->{'X-WR-CALNAME'});
        $events = $this->events($feed);
        $this->assertEqualsCanonicalizing(['Picnic', 'Retreat', 'Speaker'], array_keys($events));

        $picnic = $events['Picnic'];
        $this->assertSame(str_replace('-', '', $near) . 'T170000Z', (string) $picnic->DTSTART);
        $this->assertSame('Riverside Park', (string) $picnic->LOCATION);
        $this->assertSame('Bring snacks', (string) $picnic->DESCRIPTION);
        $this->assertStringEndsWith("/en/event/{$timed->getEid()}", (string) $picnic->URL);
        $this->assertMatchesRegularExpression('/^occurrence-\d+@/', (string) $picnic->UID);

        // All day: dates, with DTEND the day after the last one
        $retreat = $events['Retreat'];
        $this->assertSame('DATE', (string) $retreat->DTSTART['VALUE']);
        $this->assertSame((new DateTimeImmutable("$near +3 days"))->format('Ymd'), (string) $retreat->DTEND);

        $this->assertStringStartsWith('TBA', (string) $events['Speaker']->DESCRIPTION);
    }

    public function testCaching(): void
    {
        $this->client->request('GET', '/calendar/club/feed.ics');
        $this->assertResponseHeaderSame('Cache-Control', 'max-age=900, public');
        $etag = $this->client->getResponse()->getEtag();

        $this->client->request('GET', '/calendar/club/feed.ics', server: ['HTTP_IF_NONE_MATCH' => $etag]);
        $this->assertResponseStatusCodeSame(304);
    }

    public function testPrivateCalendarHasNoPublicFeed(): void
    {
        $this->calendar->setAnonPermission(Calendar::ANON_NONE);
        $this->em()->flush();

        $this->client->request('GET', '/calendar/club/feed.ics');
        $this->assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/en/calendar/club/');
        $this->assertSelectorNotExists('link[rel="alternate"][type="text/calendar"]');
    }

    public function testPersonalFeedIncludesWhatTheUserCanSee(): void
    {
        $pat = $this->createUser('pat');
        $this->createEvent($this->calendar, null, 'Board meeting', '+1 week 12:00', '+1 week 13:00')
            ->setCategory($this->boardOnlyCategory($pat));
        $this->em()->flush();
        $this->login($pat);

        $link = $this->personalLink();
        $this->assertMatchesRegularExpression('~/calendar/club/feed/\d+-[0-9a-f]{32}\.ics$~', $link);
        $this->client->restart();

        $this->assertArrayHasKey('Board meeting', $this->events($this->fetch($link)));
        $this->assertResponseHeaderSame('Cache-Control', 'max-age=900, private');
        $this->assertArrayNotHasKey('Board meeting', $this->events($this->fetch('/calendar/club/feed.ics')));
    }

    public function testPersonalLinksCanBeRevoked(): void
    {
        $pat = $this->createUser('pat');
        $this->login($pat);
        $link = $this->personalLink();
        $this->fetch($link);

        // Only for that calendar
        $other = $this->createCalendar('Other');
        $this->client->request('GET', str_replace('/calendar/club/', "/calendar/{$other->getSlug()}/", $link));
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/en/calendar/club/');
        $this->client->submitForm('Reset my personal links');
        $this->assertResponseRedirects('/en/calendar/club/');
        $this->client->request('GET', $link);
        $this->assertResponseStatusCodeSame(404);
        $newLink = $this->personalLink();
        $this->fetch($newLink);

        // Nor for disabled accounts
        $this->fresh($pat)->setIsDisabled(true);
        $this->em()->flush();
        $this->client->request('GET', $newLink);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSubscribeDialog(): void
    {
        $this->client->request('GET', '/en/calendar/club/');
        $this->assertSelectorExists('link[rel="alternate"][type="text/calendar"][href$="/calendar/club/feed.ics"]');
        $this->assertSelectorExists('#feed-public');
        $this->assertSelectorNotExists('#feed-personal');
        $this->assertSelectorExists('a[href^="webcal://"][href$="/calendar/club/feed.ics"]');

        $this->login($this->createUser('pat'));
        $this->client->request('GET', '/en/calendar/club/day/2026/10/15');
        $this->assertSelectorExists('#feed-public');
        $this->assertSelectorExists('#feed-personal');
    }
}

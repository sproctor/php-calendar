<?php
/*
 * Copyright Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\Service;

use App\Entity\Event;
use App\Entity\Occurrence;
use App\Security\Voter\EventVoter;
use DateTimeZone;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extra\Markdown\MarkdownRuntime;

/**
 * schema.org Event markup (JSON-LD) for an event's page, which search engines use to list events. Only
 * for events anyone can see without logging in, so private calendars don't advertise their events.
 */
class EventStructuredData
{
    /** Repeating events list this many upcoming dates */
    public const MAX_OCCURRENCES = 10;

    public function __construct(
        private EventVoter $eventVoter,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {
    }

    /**
     * The <script type="application/ld+json"> element for $event, or '' when it isn't public
     */
    public function script(Event $event, ?\DateTimeImmutable $now = null): string
    {
        $data = $this->build($event, $now ?? new \DateTimeImmutable());
        if ($data === null) {
            return '';
        }
        // JSON_HEX_TAG keeps "</script>" in an event's text from ending the element
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /**
     * One schema.org Event per date: the upcoming ones, or the last one if they're all past
     */
    public function build(Event $event, \DateTimeImmutable $now): ?array
    {
        if (!$this->eventVoter->canView($event, null)) {
            return null;
        }
        $occurrences = $event->getOccurrences()->toArray();
        if ($occurrences === []) {
            return null;
        }
        usort($occurrences, fn(Occurrence $a, Occurrence $b) => $a->getStart() <=> $b->getStart());
        $upcoming = array_values(array_filter($occurrences, fn(Occurrence $o) => $o->getEndBoundary() >= $now));
        $shown = $upcoming !== [] ? array_slice($upcoming, 0, self::MAX_OCCURRENCES) : [end($occurrences)];

        $calendar = $event->getCalendar();
        $common = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->getSubject(),
            'url' => $this->urlGenerator->generate('event_view', ['eid' => $event->getEid()], UrlGeneratorInterface::ABSOLUTE_URL),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'organizer' => [
                '@type' => 'Organization',
                'name' => $calendar->getTitle(),
                'url' => $this->urlGenerator->generate('default_view', ['slug' => $calendar->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
        ];
        $description = $this->plainText($event->getDescription());
        if ($description !== '') {
            $common['description'] = $description;
        }
        $common += $this->location($event);

        $timezone = new DateTimeZone($calendar->getTimezone());
        $events = array_map(fn(Occurrence $o) => $common + $this->dates($o, $timezone), $shown);

        return count($events) === 1 ? $events[0] : $events;
    }

    private function dates(Occurrence $occurrence, DateTimeZone $timezone): array
    {
        return match ($occurrence->getTimeType()) {
            // Times with the calendar's UTC offset
            Occurrence::TIME_NORMAL => [
                'startDate' => $occurrence->getStart()->setTimezone($timezone)->format(DATE_ATOM),
                'endDate' => $occurrence->getEnd()->setTimezone($timezone)->format(DATE_ATOM),
            ],
            // Dates alone; the end date is the last day, as schema.org expects
            Occurrence::TIME_FULL_DAY => [
                'startDate' => $occurrence->getStart()->format('Y-m-d'),
                'endDate' => $occurrence->getEnd()->format('Y-m-d'),
            ],
            // The time isn't known yet
            default => ['startDate' => $occurrence->getStart()->format('Y-m-d')],
        };
    }

    private function location(Event $event): array
    {
        $url = $event->getLocationUrl();
        if ($url !== null) {
            return [
                'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
                'location' => ['@type' => 'VirtualLocation', 'url' => $url],
            ];
        }
        $location = $event->getLocation();
        if ($location !== null) {
            return [
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'location' => ['@type' => 'Place', 'name' => $location, 'address' => $location],
            ];
        }

        return [];
    }

    /**
     * The description's text without Markdown, as rendered on the page
     */
    private function plainText(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }
        $html = $this->twig->getRuntime(MarkdownRuntime::class)->convert($markdown);
        $text = html_entity_decode(strip_tags(preg_replace('~<(br|/p|/li|/h\d)>~i', "$0\n", $html)), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace(["~[ \t]+~", "~\n{2,}~"], [' ', "\n"], $text));
    }
}

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

use App\Entity\Calendar;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Repository\OccurrenceRepository;
use App\Security\Voter\EventVoter;
use DateTimeImmutable;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A calendar as iCalendar (RFC 5545) for calendar apps to subscribe to: one VEVENT per date of each
 * published event $user (or anyone, for null) can see, from a while ago to a couple of years ahead
 */
class CalendarFeed
{
    public const PAST = '-6 months';
    public const FUTURE = '+2 years';

    public function __construct(
        private OccurrenceRepository $occurrenceRepository,
        private EventVoter $eventVoter,
        private MarkdownText $markdownText,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function build(Calendar $calendar, ?User $user, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $language = $calendar->getLocale() ?? 'en';
        $utc = new DateTimeZone('UTC');

        $vcalendar = new VCalendar([
            'PRODID' => '-//PHP-Calendar//PHP-Calendar 3//EN',
            'NAME' => $calendar->getTitle(),
            'X-WR-CALNAME' => $calendar->getTitle(),
            'X-WR-TIMEZONE' => $calendar->getTimezone(),
            // How often apps should check for changes, where they listen
            'X-PUBLISHED-TTL' => 'PT1H',
        ]);
        $vcalendar->add('REFRESH-INTERVAL', 'PT1H', ['VALUE' => 'DURATION']);

        $host = $this->urlGenerator->getContext()->getHost() ?: 'php-calendar';
        $occurrences = $this->occurrenceRepository->findInRange($calendar, $now->modify(self::PAST), $now->modify(self::FUTURE));
        foreach ($occurrences as $occurrence) {
            $event = $occurrence->getEvent();
            if (!$event->isPublished() || !$this->eventVoter->canView($event, $user)) {
                continue;
            }
            $url = $this->urlGenerator->generate('event_view', ['_locale' => $language, 'eid' => $event->getEid()], UrlGeneratorInterface::ABSOLUTE_URL);
            $description = $this->markdownText->toPlainText($event->getDescription());

            $vevent = $vcalendar->add('VEVENT', [
                // Stable, so an app updates its copy instead of adding another
                'UID' => 'occurrence-' . $occurrence->getOid() . '@' . $host,
                'DTSTAMP' => ($event->getModified() ?? $event->getCreated())->setTimezone($utc),
                'CREATED' => $event->getCreated()->setTimezone($utc),
                'SUMMARY' => $event->getSubject(),
                'URL' => $url,
            ]);
            if ($event->getModified() !== null) {
                $vevent->add('LAST-MODIFIED', $event->getModified()->setTimezone($utc));
            }
            if ($occurrence->hasTime()) {
                $vevent->add('DTSTART', $occurrence->getStart()->setTimezone($utc));
                $vevent->add('DTEND', $occurrence->getEnd()->setTimezone($utc));
            } else {
                // All day; DTEND is the day after the last one
                $start = new DateTimeImmutable($occurrence->getStart()->format('Y-m-d'));
                $end = $occurrence->getTimeType() === Occurrence::TIME_FULL_DAY
                    ? new DateTimeImmutable($occurrence->getEnd()->format('Y-m-d'))
                    : $start;
                $vevent->add('DTSTART', $start, ['VALUE' => 'DATE']);
                $vevent->add('DTEND', $end->modify('+1 day'), ['VALUE' => 'DATE']);
                if ($occurrence->getTimeType() === Occurrence::TIME_TBA) {
                    $tba = $this->translator->trans('to-be-announced-label', [], 'messages', $language);
                    $description = $tba . ($description !== '' ? "\n\n" . $description : '');
                }
            }
            if ($description !== '') {
                $vevent->add('DESCRIPTION', $description);
            }
            if ($event->getLocation() !== null) {
                $vevent->add('LOCATION', $event->getLocation());
            }
            if ($event->getCategory() !== null) {
                $vevent->add('CATEGORIES', $event->getCategory()->getName());
            }
        }

        return $vcalendar->serialize();
    }
}

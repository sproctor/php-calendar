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
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Repository\OccurrenceRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Finds the events on a calendar that would be at the same time as new or changed dates, for calendars
 * that warn about overlaps or don't allow them (Calendar::OVERLAP_*). Timed events overlap when their
 * times do (one ending as another starts doesn't count); all-day events take up their whole days in
 * the calendar's timezone; to-be-announced times never overlap, since there's nothing to compare.
 */
class OverlapChecker
{
    public function __construct(private OccurrenceRepository $occurrenceRepository)
    {
    }

    /**
     * The occurrences of other events that overlap any of $times, in start order
     *
     * @param list<array{0: DateTimeInterface, 1: DateTimeInterface, 2: int}> $times start, end and time type
     * @param ?Event $event the event the times are for, whose own occurrences don't count
     * @return Occurrence[]
     */
    public function findOverlaps(Calendar $calendar, array $times, ?Event $event = null): array
    {
        $timezone = new DateTimeZone($calendar->getTimezone());
        $spans = array_values(array_filter(array_map(fn(array $t) => self::span($t[0], $t[1], $t[2], $timezone), $times)));
        if ($spans === []) {
            return [];
        }
        $from = min(array_column($spans, 0));
        $to = max(array_column($spans, 1));

        $overlaps = [];
        foreach ($this->occurrenceRepository->findInRange($calendar, $from, $to) as $occurrence) {
            if ($event !== null && $occurrence->getEvent() === $event) {
                continue;
            }
            $other = self::span($occurrence->getStart(), $occurrence->getEnd(), $occurrence->getTimeType(), $timezone);
            if ($other === null) {
                continue;
            }
            foreach ($spans as [$start, $end]) {
                if ($start < $other[1] && $other[0] < $end) {
                    $overlaps[$occurrence->getOid() ?? spl_object_id($occurrence)] = $occurrence;
                    break;
                }
            }
        }

        return array_values($overlaps);
    }

    /**
     * The time something takes up, as [start, end), or null when its time isn't known
     *
     * @return ?array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private static function span(DateTimeInterface $start, DateTimeInterface $end, int $timeType, DateTimeZone $timezone): ?array
    {
        return match ($timeType) {
            Occurrence::TIME_NORMAL => [
                DateTimeImmutable::createFromInterface($start),
                // Something with no length still takes up its moment
                max(DateTimeImmutable::createFromInterface($end), DateTimeImmutable::createFromInterface($start)->modify('+1 second')),
            ],
            Occurrence::TIME_FULL_DAY => [
                new DateTimeImmutable($start->format('Y-m-d'), $timezone),
                (new DateTimeImmutable($end->format('Y-m-d'), $timezone))->modify('+1 day'),
            ],
            default => null,
        };
    }
}

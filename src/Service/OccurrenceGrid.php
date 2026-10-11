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

/**
 * Finds the occurrences someone can see on a calendar and lays them out by day
 */
class OccurrenceGrid
{
    public function __construct(
        private OccurrenceRepository $occurrenceRepository,
        private EventVoter $eventVoter,
        private CalendarDates $dates,
    ) {
    }

    /**
     * Occurrences overlapping [$from, $to) that $user can see
     *
     * @return Occurrence[]
     */
    public function findVisible(Calendar $calendar, DateTimeImmutable $from, DateTimeImmutable $to, ?User $user): array
    {
        return array_values(array_filter(
            $this->occurrenceRepository->findInRange($calendar, $from, $to),
            fn(Occurrence $o) => $this->eventVoter->canView($o->getEvent(), $user),
        ));
    }

    /**
     * Visible occurrences grouped by day, keyed by CalendarDates::dayKey(). Multi-day occurrences appear on each day.
     * Each day holds at most the calendar's maximum events; when there are more, a null follows them to
     * stand for "more events".
     *
     * @return array<string, array<Occurrence|null>>
     */
    public function byDay(Calendar $calendar, DateTimeImmutable $from, DateTimeImmutable $to, ?User $user): array
    {
        $max = $calendar->getEventsMax();
        $days = [];
        foreach ($this->findVisible($calendar, $from, $to, $user) as $occurrence) {
            $day = max($occurrence->getStart(), $from)->setTime(0, 0);
            $end = min($occurrence->getEndBoundary(), $to);
            // Every day the occurrence touches, at least its first. A timed occurrence ending at
            // midnight doesn't continue into that day.
            do {
                $key = $this->dates->dayKey($day);
                $count = count($days[$key] ?? []);
                if ($count < $max) {
                    $days[$key][] = $occurrence;
                } elseif ($count === $max) {
                    $days[$key][] = null;
                }
                $day = $day->modify('+1 day');
            } while ($day < $end);
        }

        return $days;
    }
}

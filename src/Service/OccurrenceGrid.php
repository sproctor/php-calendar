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

use App\Entity\Category;
use App\Entity\EventSource;
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
    public function findVisible(EventSource $source, DateTimeImmutable $from, DateTimeImmutable $to, ?User $user): array
    {
        return array_values(array_filter(
            $this->occurrenceRepository->findInRange($source, $from, $to),
            fn(Occurrence $o) => $this->eventVoter->canView($o->getEvent(), $user),
        ));
    }

    /**
     * Visible occurrences grouped by day, keyed by CalendarDates::dayKey(), in date order. Multi-day
     * occurrences appear on each day they touch.
     *
     * @return array<string, Occurrence[]>
     */
    public function byDay(EventSource $source, DateTimeImmutable $from, DateTimeImmutable $to, ?User $user): array
    {
        $days = [];
        foreach ($this->findVisible($source, $from, $to, $user) as $occurrence) {
            $day = max($occurrence->getStart(), $from)->setTime(0, 0);
            $end = min($occurrence->getEndBoundary(), $to);
            // Every day the occurrence touches, at least its first. A timed occurrence ending at
            // midnight doesn't continue into that day.
            do {
                $days[$this->dates->dayKey($day)][] = $occurrence;
                $day = $day->modify('+1 day');
            } while ($day < $end);
        }
        ksort($days);

        return $days;
    }

    /**
     * Limits each day of byDay() output to $max occurrences. When a day has more, a null follows them
     * to stand for "more events".
     *
     * @param array<string, Occurrence[]> $days
     * @return array<string, array<Occurrence|null>>
     */
    public static function capped(array $days, int $max): array
    {
        return array_map(
            fn(array $occurrences) => count($occurrences) > $max
                ? [...array_slice($occurrences, 0, $max), null]
                : $occurrences,
            $days,
        );
    }

    /**
     * The categories marking a day (holidays, days off): those of its full-day occurrences that have
     * Mark the day set, each once, in occurrence order
     *
     * @param array<Occurrence|null> $occurrences one day's occurrences
     * @return Category[]
     */
    public static function dayMarks(array $occurrences): array
    {
        $marks = [];
        foreach ($occurrences as $occurrence) {
            $category = $occurrence?->getEvent()->getCategory();
            if ($category !== null && $category->marksDay()
                && $occurrence->getTimeType() === Occurrence::TIME_FULL_DAY) {
                $marks[spl_object_id($category)] = $category;
            }
        }

        return array_values($marks);
    }
}

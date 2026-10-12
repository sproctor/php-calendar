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
use DateTimeImmutable;

/**
 * Creates an event's occurrences from a start, an end and an optional repeat rule
 */
class OccurrenceGenerator
{
    public const REPEAT_NEVER = '0';
    public const REPEAT_DAILY = 'D';
    public const REPEAT_WEEKLY = 'W';
    public const REPEAT_MONTHLY = 'M';
    public const REPEAT_YEARLY = 'Y';

    /** Upper limit on the occurrences one repeat rule creates */
    public const MAX_OCCURRENCES = 1000;

    /**
     * Adds occurrences to $event for the dates times() gives
     *
     * @return Occurrence[] the new occurrences
     */
    public function generate(
        Event $event,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $timeType,
        string $repeat = self::REPEAT_NEVER,
        int $frequency = 1,
        ?DateTimeImmutable $until = null,
    ): array {
        return array_map(
            fn(array $times) => new Occurrence($event, ...$times),
            $this->times($start, $end, $timeType, $repeat, $frequency, $until),
        );
    }

    /**
     * Makes $event's occurrences the dates times() gives, keeping the occurrences it already has where it
     * can, so they keep their IDs (which calendar apps subscribed to the feed know them by):
     *
     *  1. an occurrence with exactly the new times stays as it is;
     *  2. one starting on the same day as a new date moves to its times (a changed time of day);
     *  3. the rest are moved to the remaining new dates in order (e.g. a weekly event moved to another
     *     weekday);
     *  4. new dates left over are added, and occurrences left over are removed.
     */
    public function update(
        Event $event,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $timeType,
        string $repeat = self::REPEAT_NEVER,
        int $frequency = 1,
        ?DateTimeImmutable $until = null,
    ): void {
        $wanted = $this->times($start, $end, $timeType, $repeat, $frequency, $until);
        $existing = $event->getOccurrences()->toArray();
        usort($existing, fn(Occurrence $a, Occurrence $b) => $a->getStart() <=> $b->getStart());

        $rules = [
            fn(Occurrence $o, array $times) => $o->hasTimes(...$times),
            fn(Occurrence $o, array $times) => $o->getStart()->format('Y-m-d') === $times[0]->format('Y-m-d'),
            fn(Occurrence $o, array $times) => true,
        ];
        foreach ($rules as $matches) {
            foreach ($wanted as $i => $times) {
                foreach ($existing as $j => $occurrence) {
                    if ($matches($occurrence, $times)) {
                        $occurrence->setTimes(...$times);
                        unset($wanted[$i], $existing[$j]);
                        break;
                    }
                }
            }
        }
        foreach ($wanted as $times) {
            new Occurrence($event, ...$times);
        }
        foreach ($existing as $occurrence) {
            $event->removeOccurrence($occurrence);
        }
    }

    /**
     * The times of an event's occurrences: $start–$end, then, when $repeat isn't REPEAT_NEVER, one every
     * $frequency days/weeks/months/years whose start is on or before $until. Monthly and yearly repeats
     * skip dates that don't exist (the 31st in a 30-day month, February 29th in other years) rather
     * than rolling over into the next month.
     *
     * @return list<array{0: DateTimeImmutable, 1: DateTimeImmutable, 2: int}> start, end and time type
     */
    public function times(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $timeType,
        string $repeat = self::REPEAT_NEVER,
        int $frequency = 1,
        ?DateTimeImmutable $until = null,
    ): array {
        if ($repeat === self::REPEAT_NEVER || $until === null) {
            return [[$start, $end, $timeType]];
        }
        if ($frequency < 1) {
            throw new \InvalidArgumentException('Frequency must be at least 1');
        }

        $duration = $start->diff($end);
        $lastDay = $until->format('Y-m-d');
        $occurrences = [];
        for ($n = 0; count($occurrences) < self::MAX_OCCURRENCES; $n++) {
            $step = $n * $frequency;
            $next = match ($repeat) {
                self::REPEAT_DAILY => $start->modify("+$step days"),
                self::REPEAT_WEEKLY => $start->modify('+' . ($step * 7) . ' days'),
                self::REPEAT_MONTHLY => $start->modify("+$step months"),
                self::REPEAT_YEARLY => $start->modify("+$step years"),
                default => throw new \InvalidArgumentException("Unknown repeat rule: $repeat"),
            };
            if ($next->format('Y-m-d') > $lastDay) {
                break;
            }
            // modify() rolls Jan 31 + 1 month over to Mar 3; skip those dates
            if (($repeat === self::REPEAT_MONTHLY || $repeat === self::REPEAT_YEARLY)
                && $next->format('d') !== $start->format('d')) {
                continue;
            }
            $occurrences[] = [$next, $next->add($duration), $timeType];
        }

        return $occurrences;
    }

    /**
     * Guesses the repeat rule that produced $occurrences, for prefilling the edit form
     *
     * @param Occurrence[] $occurrences in start order
     * @return array{0: string, 1: int} the repeat rule and frequency
     */
    public function inferRepeat(array $occurrences): array
    {
        if (count($occurrences) < 2) {
            return [self::REPEAT_NEVER, 1];
        }
        $first = $occurrences[0]->getStart();
        $second = $occurrences[1]->getStart();
        $days = (int) $first->diff($second)->format('%r%a');
        $months = ((int) $second->format('Y') - (int) $first->format('Y')) * 12
            + (int) $second->format('n') - (int) $first->format('n');

        $candidates = [];
        if ($months > 0 && $months % 12 === 0 && $first->format('d') === $second->format('d')) {
            $candidates[] = [self::REPEAT_YEARLY, intdiv($months, 12)];
        }
        if ($months > 0 && $first->format('d') === $second->format('d')) {
            $candidates[] = [self::REPEAT_MONTHLY, $months];
        }
        if ($days > 0 && $days % 7 === 0) {
            $candidates[] = [self::REPEAT_WEEKLY, intdiv($days, 7)];
        }
        if ($days > 0) {
            $candidates[] = [self::REPEAT_DAILY, $days];
        }

        foreach ($candidates as [$repeat, $frequency]) {
            $expected = $this->generate(
                new Event($occurrences[0]->getEvent()->getCalendar(), null),
                $first,
                $occurrences[0]->getEnd(),
                $occurrences[0]->getTimeType(),
                $repeat,
                $frequency,
                end($occurrences)->getStart(),
            );
            $matches = count($expected) === count($occurrences);
            foreach ($expected as $i => $occurrence) {
                $matches = $matches && $occurrence->getStart() == $occurrences[$i]->getStart();
            }
            if ($matches) {
                return [$repeat, $frequency];
            }
        }

        return [self::REPEAT_NEVER, 1];
    }
}

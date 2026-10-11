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

use DateTimeImmutable;
use DateTimeInterface;
use IntlCalendar;
use IntlDateFormatter;
use IntlDatePatternGenerator;
use Locale;

/**
 * Calendar arithmetic for the month and week views. Week rules (the first day of the week and how
 * weeks are numbered) come from the current locale, \Locale::getDefault(), which
 * FormattingLocaleSubscriber sets for each request. Dates are midnight in PHP's default timezone.
 */
class CalendarDates
{
    /**
     * Midnight on the given day. Out-of-range days and months roll over, so day 0 is the last day of
     * the previous month.
     */
    public function date(int $year, int $month, int $day): DateTimeImmutable
    {
        return (new DateTimeImmutable('today'))->setDate($year, $month, $day);
    }

    public function isToday(DateTimeInterface $date): bool
    {
        return $date->format('Y-m-d') === (new DateTimeImmutable('today'))->format('Y-m-d');
    }

    /**
     * The key used to group things by day
     */
    public function dayKey(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    /**
     * The first day of the week as an ISO-8601 day number, 1 for Monday through 7 for Sunday
     */
    public function firstDayOfWeek(): int
    {
        // IntlCalendar numbers days 1 for Sunday through 7 for Saturday
        $firstDay = IntlCalendar::createInstance(null, Locale::getDefault())->getFirstDayOfWeek();

        return $firstDay === IntlCalendar::DOW_SUNDAY ? 7 : $firstDay - 1;
    }

    /**
     * How far $date is into its week: 0 for the first day of the week, 6 for the last
     */
    public function dayOfWeek(DateTimeInterface $date): int
    {
        return ((int) $date->format('N') - $this->firstDayOfWeek() + 7) % 7;
    }

    /**
     * The first day shown in the month grid: the start of the week containing the 1st
     */
    public function monthGridStart(int $year, int $month): DateTimeImmutable
    {
        $first = $this->date($year, $month, 1);

        return $first->modify('-' . $this->dayOfWeek($first) . ' days');
    }

    /**
     * The number of weeks (grid rows) the month touches
     */
    public function weeksInMonth(int $year, int $month): int
    {
        $first = $this->date($year, $month, 1);
        $days = (int) $first->format('t');

        return intdiv($this->dayOfWeek($first) + $days + 6, 7);
    }

    public function weekOfYear(DateTimeInterface $date): int
    {
        return $this->intlCalendar($date)->get(IntlCalendar::FIELD_WEEK_OF_YEAR);
    }

    /**
     * The year that $date's week number belongs to, which differs from the calendar year around January 1st
     */
    public function weekYear(DateTimeInterface $date): int
    {
        return $this->intlCalendar($date)->get(IntlCalendar::FIELD_YEAR_WOY);
    }

    /**
     * The first day of week $week of week-numbering year $year
     */
    public function weekStart(int $week, int $year): DateTimeImmutable
    {
        $calendar = IntlCalendar::createInstance('UTC', Locale::getDefault());
        $calendar->clear();
        $calendar->set(IntlCalendar::FIELD_YEAR_WOY, $year);
        $calendar->set(IntlCalendar::FIELD_WEEK_OF_YEAR, $week);
        $calendar->set(IntlCalendar::FIELD_DAY_OF_WEEK, $calendar->getFirstDayOfWeek());
        $start = $calendar->toDateTime();

        return $this->date((int) $start->format('Y'), (int) $start->format('n'), (int) $start->format('j'));
    }

    /**
     * The month's full name, e.g. "October"
     */
    public function monthName(DateTimeInterface $date): string
    {
        return $this->format($date, 'MMMM');
    }

    /**
     * Formats $date with an ICU pattern in the current locale and timezone
     */
    public function format(DateTimeInterface $date, string $pattern): string
    {
        $formatter = new IntlDateFormatter(
            Locale::getDefault(),
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $date->getTimezone(),
            null,
            $pattern,
        );

        return $formatter->format($date);
    }

    /**
     * Formats $date from an ICU skeleton, which lists the fields to show (e.g. "yMMMM" for "October 2026");
     * the locale decides their order and punctuation
     */
    public function formatSkeleton(DateTimeInterface $date, string $skeleton): string
    {
        $pattern = (new IntlDatePatternGenerator(Locale::getDefault()))->getBestPattern($skeleton);

        return $this->format($date, $pattern === false ? $skeleton : $pattern);
    }

    /**
     * The inverse of dayKey()
     */
    public function fromDayKey(string $key): DateTimeImmutable
    {
        [$year, $month, $day] = array_map('intval', explode('-', $key));

        return $this->date($year, $month, $day);
    }

    /**
     * An IntlCalendar on $date's calendar day, with the current locale's week rules
     */
    private function intlCalendar(DateTimeInterface $date): IntlCalendar
    {
        $calendar = IntlCalendar::createInstance('UTC', Locale::getDefault());
        $calendar->clear();
        $calendar->set((int) $date->format('Y'), (int) $date->format('n') - 1, (int) $date->format('j'));

        return $calendar;
    }
}

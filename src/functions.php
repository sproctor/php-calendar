<?php

function days_in_year(int $year): int
{
    return 365 + intval(create_datetime(1, 1, $year)->format('L'));
}

function days_between(DateTimeInterface $date1, DateTimeInterface $date2): int
{
    $year1 = intval($date1->format('Y'));
    $year2 = intval($date2->format('Y'));
    if ($year2 < $year1) {
        return -days_between($date2, $date1);
    }
    $days = 0;
    for ($year = $year1; $year < $year2; $year++) {
        $days += days_in_year($year);
    }
    // add day of year of $date2, subtract day of year of $date1
    $days += intval($date2->format('z'));
    $days -= intval($date1->format('z'));
    return $days;
}

/**
 * returns the number of weeks in $month
 */
function weeks_in_month(int $month, int $year): int
{
    $days = days_in_month($month, $year);

    // days not in this month in the partial weeks
    $days_in_week_before_month = day_of_week($month, 1, $year);
    $days_in_week_after_month = 6 - day_of_week($month, $days, $year);

    // add up the days in the month and the outliers in the partial weeks
    // divide by 7 for the weeks in the month
    return intval(($days_in_week_before_month + $days + $days_in_week_after_month) / 7);
}

/**
 * return the week number for $date in the current locale
 */
function week_of_year(DateTimeInterface $date): int
{
    return locale_calendar($date)->get(\IntlCalendar::FIELD_WEEK_OF_YEAR);
}

/**
 * return the year of week of year for $date in the current locale
 */
function year_of_week_of_year(DateTimeInterface $date): int
{
    return locale_calendar($date)->get(\IntlCalendar::FIELD_YEAR_WOY);
}

/**
 * An IntlCalendar set to $date's calendar day, with the current locale's week rules
 */
function locale_calendar(DateTimeInterface $date): \IntlCalendar
{
    $calendar = \IntlCalendar::createInstance('UTC', \Locale::getDefault());
    $calendar->clear();
    $calendar->set(intval($date->format('Y')), intval($date->format('n')) - 1, intval($date->format('j')));
    return $calendar;
}

/**
 * Takes a date, returns the full month name
 */
function month_name(DateTimeInterface $date): string
{
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "MMMM" // full month format
    );
    return $formatter->format($date);
}

/**
 * Returns the number of days between the start of the week and the given day,
 * 0 for the first day of the week, 6 for the last. The first day of the week
 * depends on the current locale.
 */
function day_of_week(int $month, int $day, int $year): int
{
    return day_of_week_date(_create_datetime($month, $day, $year));
}

function date_index(DateTimeInterface $date): string
{
    return $date->format('Y-m-d');
}

function is_today(DateTimeInterface $date): bool
{
    return days_between($date, new \DateTime()) == 0;
}

/**
 * Returns the number of days between the start of the week and $date,
 * 0 for the first day of the week, 6 for the last. The first day of the week
 * depends on the current locale.
 */
function day_of_week_date(DateTimeInterface $date): int
{
    return (intval($date->format('N')) - first_day_of_week() + 7) % 7;
}

/**
 * Returns the first day of the week in the current locale as an ISO-8601
 * day number, 1 for Monday through 7 for Sunday
 */
function first_day_of_week(): int
{
    // IntlCalendar numbers days 1 for Sunday through 7 for Saturday
    $first_day = \IntlCalendar::createInstance(null, \Locale::getDefault())->getFirstDayOfWeek();
    return $first_day === \IntlCalendar::DOW_SUNDAY ? 7 : $first_day - 1;
}

/**
 * Returns the first day of week $week of week-numbering year $year, using the
 * current locale's week rules (the same ones week_of_year() uses)
 */
function week_start_date(int $week, int $year): DateTimeImmutable
{
    $calendar = \IntlCalendar::createInstance('UTC', \Locale::getDefault());
    $calendar->clear();
    $calendar->set(\IntlCalendar::FIELD_YEAR_WOY, $year);
    $calendar->set(\IntlCalendar::FIELD_WEEK_OF_YEAR, $week);
    $calendar->set(\IntlCalendar::FIELD_DAY_OF_WEEK, $calendar->getFirstDayOfWeek());
    /** @noinspection PhpUnhandledExceptionInspection */
    return new DateTimeImmutable($calendar->toDateTime()->format('Y-m-d'));
}

/**
 * Returns the number of days in $month
 */
function days_in_month(int $month, int $year): int
{
    return intval(_create_datetime($month, 1, $year)->format('t'));
}

/**
 * normalize date after month or day were incremented or decremented
 */
function normalize_date(int &$month, int &$day, int &$year): void
{
    if ($month < 1) {
        $month += 12;
        $year--;
        normalize_date($month, $day, $year);
    } elseif ($month > 12) {
        $month -= 12;
        $year++;
        normalize_date($month, $day, $year);
    }
    if ($day <= 0) {
        $month--;
        if ($month < 1) {
            $month += 12;
            $year--;
        }
        $day += days_in_month($month, $year);
        normalize_date($month, $day, $year);
    } elseif ($day > days_in_month($month, $year)) {
        $day -= days_in_month($month, $year);
        $month++;
        if ($month > 12) {
            $month -= 12;
            $year++;
        }
        normalize_date($month, $day, $year);
    }
}

function _create_datetime(int $month, int $day, int $year): DateTimeImmutable
{
    /** @noinspection PhpUnhandledExceptionInspection */
    return new DateTimeImmutable(sprintf("%04d-%02d-%02d", $year, $month, $day));
}

function create_datetime(int $month, int $day, int $year): DateTimeImmutable
{
    normalize_date($month, $day, $year);
    return _create_datetime($month, $day, $year);
}

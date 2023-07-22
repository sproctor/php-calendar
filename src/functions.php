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
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "w" // short month format
    );
    return $formatter->format($date);
}


/**
 * return the year of week of year for $date in the current locale
 */
function year_of_week_of_year(DateTimeInterface $date): int
{
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "Y" // short month format
    );
    return $formatter->format($date);
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
 * Returns the number of days in the week before the
 * taking into account whether we start on sunday or monday
 * 1 for Monday, 7 for Sunday
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

function datetime_from_timestamp(string $timestamp): DateTimeInterface
{

    $date = \DateTimeImmutable::createFromFormat('U', $timestamp);
    $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    return $date;
}

/**
 *  returns the number of days in the week before the
 *  taking into account whether we start on sunday or monday
 *  1 for Monday, 7 for Sunday
 */
function day_of_week_date(DateTimeInterface $date): int
{
    return intval($date->format('N'));
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

<?php
/*
 * Copyright 2017 Sean Proctor
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

/*
   this file contains all the re-usable functions for the calendar
*/

namespace PhpCalendar;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;

define('PHPC_CONFIG_FILE', realpath(__DIR__.'/../config.php'));
define('PHPC_VERSION', '2.1.0');
define('PHPC_DEBUG', 1);

function __($msg)
{
    global $context;

    if (empty($context->translator)) {
        return $msg;
    }

    return $context->translator->trans($msg);
}

function minute_pad($minute)
{
    return sprintf('%02d', $minute);
}

function escape_entities($string)
{
    return htmlspecialchars($string, ENT_NOQUOTES, "UTF-8");
}

/**
 * @param bool $val
 * @return string
 */
function asbool($val)
{
    return $val ? "1" : "0";
}

/**
 * @param \DateTimeInterface $date
 * @param int                $date_format
 * @param bool               $hours24
 * @return string
 */
function format_datetime(\DateTimeInterface $date, $date_format, $hours24)
{
    return format_date($date, $date_format) . ' '
    . __('at') . ' ' . format_time($date, $hours24);
}

/**
 * @param \DateTimeInterface $date
 * @param int                $date_format
 * @return string
 */
function format_date(\DateTimeInterface $date, $date_format)
{
    $month = short_month_name($date->format('n'));
    $day = $date->format('j');
    $year = $date->format('Y');
    
    switch ($date_format) {
        default:
        case 0:
            return "$month $day, $year";
        case 1:
            return "$year $month $day";
        case 2:
            return "$day $month $year";
    }
}

/**
 * @param \DateTimeInterface $date
 * @param int                $date_format
 * @return string
 */
function format_date_short(\DateTimeInterface $date, $date_format)
{
    switch ($date_format) {
        default:
        case 0: // Month Day Year
            return $date->format('n\/j\/Y');
        case 1: // Year Month Day
            return $date->format('Y\-n\-j');
        case 2: // Day Month Year
            return $date->format('j\-n\-Y');
    }
}

/**
 * @param \DateTimeInterface $date
 * @param bool               $hour24
 * @return string
 */
function format_time(\DateTimeInterface $date, $hour24)
{
    if ($hour24) {
        return $date->format('G\:i');
    } else {
        return $date->format('g\:i\ A');
    }
}

/**
 * @param int $year
 * @return int
 */
function days_in_year($year)
{
    return 365 + intval(create_datetime(1, 1, $year)->format('L'));
}

/**
 * @param \DateTimeInterface $date1
 * @param \DateTimeInterface $date2
 * @return int
 */
function days_between(\DateTimeInterface $date1, \DateTimeInterface $date2)
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
 * @return string[]
 */
function get_language_mappings()
{
    static $mappings = null;

    if (empty($mappings)) {
        $mappings = array();
        $finder = new Finder();

        foreach ($finder->name('*.mo')->in(__DIR__.'/../translations')->files() as $file) {
            $code = $file->getBasename('.mo');
            $lang = Intl::getLanguageBundle()->getLanguageName($code);
            $mappings[$lang] = $code;
        }
    }

    return $mappings;
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
/**
 * @param int $month
 * @param int $day
 * @param int $year
 * @param int $week_start
 * @return int
 */
function day_of_week($month, $day, $year, $week_start)
{
    return day_of_week_date(_create_datetime($month, $day, $year), $week_start);
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week_date(\DateTimeInterface $date, $week_start)
{
    $days = intval($date->format('w'));

    return ($days + 7 - $week_start) % 7;
}

// returns the number of days in $month
/**
 * @param int $month
 * @param int $year
 * @return int
 */
function days_in_month($month, $year)
{
    return intval(_create_datetime($month, 1, $year)->format('t'));
}

//returns the number of weeks in $month
/**
 * @param int $month
 * @param int $year
 * @param int $week_start
 * @return number
 */
function weeks_in_month($month, $year, $week_start)
{
    $days = days_in_month($month, $year);

    // days not in this month in the partial weeks
    $days_before_month = day_of_week($month, 1, $year, $week_start);
    $days_after_month = 6 - day_of_week($month, $days, $year, $week_start);

    // add up the days in the month and the outliers in the partial weeks
    // divide by 7 for the weeks in the month
    return intval(($days_before_month + $days + $days_after_month) / 7);
}

/**
 * @param int $year
 * @param int $week_start
 * @return int
 */
function weeks_in_year($year, $week_start)
{
    // This is true for ISO, not US
    if ($week_start == 1) {
        return _create_datetime(12, 28, $year)->format("W");
    }
    // else
    return intval((day_of_week(1, 1, $year, $week_start) + days_in_year($year)) / 7);
}

/**
 * return the week number for $date in the current locale
 * 
 * @param \DateTimeInterface $date
 * @return int
 */
function week_of_year(\DateTimeInterface $date)
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
 * 
 * @param \DateTimeInterface $date
 * @return int
 */
function year_of_week_of_year(\DateTimeInterface $date)
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
 * @param Context            $context
 * @param string             $action
 * @param \DateTimeInterface $date
 * @return string
 */
function action_date_url(Context $context, $action, \DateTimeInterface $date)
{
    return action_url(
        $context,
        $action,
        ['year' => $date->format('Y'), 'month' => $date->format('n'), 'day' => $date->format('j')]
    );
}

/**
 * @param Context $context
 * @param string  $action
 * @param string  $eid
 * @return string
 */
function action_event_url(Context $context, $action, $eid)
{
    return action_url($context, $action, array("eid" => $eid));
}

/**
 * @param Context $context
 * @param string  $action
 * @param string  $eid
 * @return string
 */
function action_occurrence_url(Context $context, $action, $oid)
{
    return action_url($context, $action, array("oid" => $oid));
}

/**
 * @param Context $context
 * @param string|null $action
 * @param string[] $parameters
 * @param string|null $hash
 * @return string
 */
function action_url(Context $context, $action = null, $parameters = array(), $hash = null)
{
    if (!empty($context->calendar)) {
        $parameters['phpcid'] = $context->calendar->getCid();
    }
    $url = $context->request->getScriptName();
    $first = true;
    if ($action !== null) {
        $url .= "?action={$action}";
        $first = false;
    }
    foreach ($parameters as $key => $value) {
        $url .= ($first ? '?' : '&')."$key=$value";
        $first = false;
    }
    if ($hash !== null) {
        $url .= '#'.$hash;
    }
    return $url;
}

/**
 * @param Request $request
 * @return string
 */
function append_parameter_url(Request $request, $parameter)
{
    $uri = $request->getRequestUri();
    if (strpos($uri, "?") !== false) {
        $uri .= '&';
    } else {
        $uri .= '?';
    }
    return $uri.$parameter;
}

// takes a menu $html and appends an entry
/**
 * @param Context $context
 * @param string  $action
 * @param string  $text
 * @return string
 */
function menu_item(Context $context, $action, $text)
{
    $url = htmlentities(action_url($context, $action));
    $active = $context->getAction() == $action ? " active" : "";
    return "<li class=\"nav-item$active\"><a class=\"nav-link\" href=\"$url\">$text</a></li>";
}

/**
 * @param string   $title
 * @param string[] $values // Array of URL => title
 * @return string // dropdown box that will change the page to the URL from $values when an element is selected
 */
function create_dropdown($title, $values)
{
    $output = "<div class=\"nav-item dropdown\">\n"
    ."    <a class=\"nav-link dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\" role=\"button\" aria-haspopup=\"true\" aria-expanded=\"false\">$title</a>\n"
    ."    <div class=\"dropdown-menu\">\n";
    foreach ($values as $key => $value) {
        $output .= "        <a class=\"dropdown-item\" href=\"$key\">$value</a>\n";
    }
    return $output . "    </div></div>";
}

/**
 * Takes a date, returns the full month name
 * 
 * @param \DateTimeInterface $date
 * @return string
 */
function month_name(\DateTimeInterface $date)
{
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "MMMM" // short month format
    );
    return $formatter->format($date);
}

/**
 * @param \DateTimeInterface $date
 * @return string
 */
function day_name(\DateTimeInterface $date)
{
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "EEEE" // short month format
    );
    return $formatter->format($date);
}

/**
 * @param \DateTimeInterface $date
 * @return string
 */
function short_day_name(\DateTimeInterface $date)
{
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "E" // short month format
    );
    return $formatter->format($date);
}

/**
 * @param \DateTimeInterface $date
 * @return string
 */
function short_month_name(\DateTimeInterface $date)
{
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "MMM" // short month format
    );
    return $formatter->format($date);
}

/**
 * @param \DateTimeInterface $date
 * @return string
 */
function date_index(\DateTimeInterface $date)
{
    return $date->format('Y-m-d');
}

/**
 * @param \DateTimeInterface $date
 * @return boolean
 */
function is_today(\DateTimeInterface $date)
{
    return days_between($date, new \DateTime()) == 0;
}

/**
 * normalize date after month or day were incremented or decremented
 *
 * @param $month
 * @param $day
 * @param $year
 */
function normalize_date(&$month, &$day, &$year)
{
    if ($month < 1) {
        $month = 12;
        $year--;
    } elseif ($month > 12) {
        $month = 1;
        $year++;
    }
    if ($day <= 0) {
        $month--;
        if ($month < 1) {
            $month += 12;
            $year--;
        }
        $day += days_in_month($month, $year);
    } elseif ($day > days_in_month($month, $year)) {
        $day -= days_in_month($month, $year);
        $month++;
        if ($month > 12) {
            $month -= 12;
            $year++;
        }
    }
}

/**
 * @param \DateTimeInterface $date
 * @return string
 */
function datetime_to_sql_date(\DateTimeInterface $date)
{
    $utcDate = new \DateTime($date->format('Y-m-d H:i:s'), $date->getTimezone());
    $utcDate->setTimezone(new \DateTimeZone('UTC'));
    return $utcDate->format('Y-m-d H:i:s');
}

/**
 * @param string $str
 * @return \DateTime
 */
function datetime_from_sql_date($str)
{
    $date = \DateTime::createFromFormat('Y-m-d H:i:s', $str, new \DateTimeZone('UTC'));
    $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    return $date;
}

/**
 * @param string $timestamp
 * @return \DateTime
 */
function datetime_from_timestamp($timestamp)
{

    $date = \DateTime::createFromFormat('U', $timestamp);
    $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    return $date;
}

/**
 * @param int $month
 * @param int $day
 * @param int $year
 * @return \DateTime
 */
function _create_datetime($month, $day, $year)
{
    return new \DateTime(sprintf("%04d-%02d-%02d", $year, $month, $day));
}

/**
 * @param int $month
 * @param int $day
 * @param int $year
 * @return \DateTime
 */
function create_datetime($month, $day, $year)
{
    normalize_date($month, $day, $year);
    return _create_datetime($month, $day, $year);
}

/**
 * @param Calendar           $calendar
 * @param User               $user
 * @param \DateTimeInterface $from
 * @param \DateTimeInterface $to
 * @return array
 */
function get_occurrences_by_day(Calendar $calendar, User $user, \DateTimeInterface $from, \DateTimeInterface $to)
{
    $all_occurrences = $calendar->getOccurrencesByDateRange($from, $to);
    $occurrences_by_day = array();

    foreach ($all_occurrences as $occurrence) {
        if (!$occurrence->canRead($user)) {
            continue;
        }

        $end = $occurrence->getEnd();
        $start = $occurrence->getStart();

        if ($start > $from) {
            $diff = new \DateInterval("P0D");
        } else { // the event started before the range we're showing
            $diff = $from->diff($start);
        }

        // put the event in every day until the end
        for ($date = $start->add($diff); $date < $to && $date <= $end; $date = $date->add(new \DateInterval("P1D"))) {
            $key = date_index($date);
            if (!isset($occurrences_by_day[$key])) {
                $occurrences_by_day[$key] = array();
            }
            if (sizeof($occurrences_by_day[$key]) == $calendar->getMaxDisplayEvents()) {
                $occurrences_by_day[$key][] = null;
            }
            if (sizeof($occurrences_by_day[$key]) > $calendar->getMaxDisplayEvents()) {
                continue;
            }
            $occurrences_by_day[$key][] = $occurrence;
        }
    }
    return $occurrences_by_day;
}

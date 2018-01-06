<?php
/*
 * Copyright 2018 Sean Proctor
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

/**
 * Translates the given message, replacing parameters in it
 *
 * @param string      $id
 * @param string[]    $parameters
 * @param string|null $domain
 * @param string|null $locale
 * @return string
 */
function __($id, $parameters = array(), $domain = null, $locale = null)
{
    global $context;

    if (empty($context->translator)) {
        return $id;
    }

    return $context->translator->trans($id, $parameters, $domain, $locale);
}

/**
 * Translates the given message, replacing parameters in it
 *
 * @param string      $id
 * @param int         $number
 * @param string[]    $parameters
 * @param string|null $domain
 * @param string|null $locale
 * @return string
 */
function transchoice($id, $number, $parameters = array(), $domain = null, $locale = null)
{
    global $context;

    if (empty($context->translator)) {
        return $id;
    }

    return $context->translator->trans($id, $number, $parameters, $domain, $locale);
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
            $lang = Intl::getLanguageBundle()->getLanguageName($code, null, $code);
            $mappings[$lang] = $code;
        }
    }

    return $mappings;
}

/**
 * Returns the number of days in the week before the
 * taking into account whether we start on sunday or monday
 *
 * @param int $month
 * @param int $day
 * @param int $year
 * @return int
 */
function day_of_week($month, $day, $year)
{
    return day_of_week_date(_create_datetime($month, $day, $year));
}

/**
 *  returns the number of days in the week before the
 *  taking into account whether we start on sunday or monday
 *
 * @param \DateTimeInterface $date
 * @return int
 */
function day_of_week_date(\DateTimeInterface $date)
{
    $formatter = new \IntlDateFormatter(
        \Locale::getDefault(),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        "c" // short month format
    );
    return intval($formatter->format($date));
}

/**
 * Returns the number of days in $month
 *
 * @param int $month
 * @param int $year
 * @return int
 */
function days_in_month($month, $year)
{
    return intval(_create_datetime($month, 1, $year)->format('t'));
}

/**
 * returns the number of weeks in $month
 *
 * @param int $month
 * @param int $year
 * @return number
 */
function weeks_in_month($month, $year)
{
    $days = days_in_month($month, $year);

    // days not in this month in the partial weeks
    $days_before_month = day_of_week($month, 1, $year);
    $days_after_month = 6 - day_of_week($month, $days, $year);

    // add up the days in the month and the outliers in the partial weeks
    // divide by 7 for the weeks in the month
    return intval(($days_before_month + $days + $days_after_month) / 7);
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

/**
 * Takes a menu $html and appends an entry
 *
 * @param Context $context
 * @param string  $action
 * @param string  $text
 * @return string
 */
function menu_item(Context $context, $action, $text)
{
    $url = htmlentities($context->createUrl($action));
    $active = $context->getAction() == $action ? " active" : "";
    return "<li class=\"nav-item$active\"><a class=\"nav-link\" href=\"$url\">$text</a></li>";
}

/**
 * @param string   $title
 * @param string[] $values Array of URL => title
 * @return string          Dropdown box that will change the page to the URL from $values when an element is selected
 */
function create_dropdown($title, $values)
{
    $output = "<div class=\"nav-item dropdown\">\n"
    ."    <a class=\"nav-link dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\" role=\"button\" aria-haspopup=\"true\" aria-expanded=\"false\">$title</a>\n"
    ."    <div class=\"dropdown-menu\">\n";
    foreach ($values as $key => $value) {
        $output .= "        <a class=\"dropdown-item\" href=\"$value\">$key</a>\n";
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
        "MMMM" // full month format
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

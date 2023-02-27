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

namespace App\Twig;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'menu_item',
                [$this, 'menuItem'],
                [
                    'needs_context' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'append_parameter_url',
                [$this, 'append_parameter_url']
            ),
            new TwigFunction(
                'dropdown',
                [$this, 'createDropdown'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'is_date_in_month',
                function (array $context, DateTimeInterface $date) {
                    $app = $context['app'];
                    $request = $app->getRequest();
                    $active_route = $request->get('_route');
                    return $active_route == 'display_month'
                        && intval($date->format('n')) == $context['month']
                        && intval($date->format('Y')) == $context['year'];
                },
                ['needs_context' => true]
            ),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'month_name',
                [$this, 'monthName']
            ),
            new TwigFilter(
                'month_abbr',
                [$this, 'shortMonthName']
            ),
            new TwigFilter(
                'day_name',
                [$this, 'dayName']
            ),
            new TwigFilter(
                'day_abbr',
                [$this, 'shortDayName']
            ),
            new TwigFilter(
                'week_link',
                function (array $twigContext, DateTimeInterface $date) {
                    $context = $twigContext['context'];
                    $week = \week_of_year($date);
                    $year = \year_of_week_of_year($date);
//                    $url = $context->createUrl('display_week', ['week' => $week, 'year' => $year]);
                    $url = "";
                    return "<a href=\"$url\">$week</a>";
                },
                [
                    'is_safe' => array('html'),
                    'needs_context' => true,
                ]
            )
        ];
    }

    function menuItem(array $context, string $action, string $text, string $url, ?string $icon = null): string
    {
        $app = $context['app'];
        $request = $app->getRequest();
        $active_route = $request->get('_route');
        $active = $active_route === $action ? " active" : "";
        if ($icon != null) {
            $text = "<i class=\"bi-$icon\"></i> $text";
        }
        return "<li class=\"nav-item$active\"><a class=\"nav-link\" href=\"$url\">$text</a></li>";
    }

    function append_parameter_url(Request $request, string $parameter): string
    {
        $uri = $request->getRequestUri();
        if (str_contains($uri, "?")) {
            $uri .= '&';
        } else {
            $uri .= '?';
        }
        return $uri . $parameter;
    }

    /**
     * @param string $title
     * @param string[] $values Array of URL => title
     * @return string          Dropdown box that will change the page to the URL from $values when an element is selected
     */
    function createDropdown(string $title, array $values): string
    {
        $output = "<div class=\"nav-item dropdown\">"
            . "<a class=\"nav-link dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\" role=\"button\""
            . " aria-haspopup=\"true\" aria-expanded=\"false\">$title</a>"
            . "<div class=\"dropdown-menu\">";
        foreach ($values as $key => $value) {
            $output .= "<a class=\"dropdown-item\" href=\"$value\">$key</a>";
        }
        return $output . "</div></div>";
    }

    /**
     * Takes a date, returns the full month name
     *
     * @param DateTimeInterface $date
     * @return string
     */
    function monthName(DateTimeInterface $date): string
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
     * @param DateTimeInterface $date
     * @return string
     */
    function shortDayName(DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            "E"
        );
        return $formatter->format($date);
    }

    /**
     * @param DateTimeInterface $date
     * @return string
     */
    function dayName(DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            "EEEE"
        );
        return $formatter->format($date);
    }

    /**
     * @param DateTimeInterface $date
     * @return string
     */
    function shortMonthName(DateTimeInterface $date)
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
}
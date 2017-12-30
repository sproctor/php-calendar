<?php
/*
 * Copyright 2016 Sean Proctor
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
   This file has the functions for the day displays of the calendar
*/

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;

class DayPage extends Page
{
    // View for a single day
    /**
     * @param Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        $occurrences = $context->db->getOccurrencesByDate(
            $context->calendar->getCid(),
            $context->getYear(),
            $context->getMonth(),
            $context->getDay()
        );
        
        return new Response($context->twig->render("day_page.html.twig", array('occurrences' => $occurrences)));
    }
}

function create_day_menu(Context $context, $year, $month, $day)
{
    $html = tag('div', attrs('class="phpc-bar ui-widget-content"'));
    
    $prev_time = mktime(0, 0, 0, $month, $day - 1, $year);
    $prev_day = date('j', $prev_time);
    $prev_month = date('n', $prev_time);
    $prev_year = date('Y', $prev_time);
    $prev_month_name = month_name($prev_month);

    $last_args = array('year' => $prev_year, 'month' => $prev_month, 'day' => $prev_day);

    menu_item_prepend($context, $html, "$prev_month_name $prev_day", 'display_day', $last_args);

    $next_time = mktime(0, 0, 0, $month, $day + 1, $year);
    $next_day = date('j', $next_time);
    $next_month = date('n', $next_time);
    $next_year = date('Y', $next_time);
    $nextmonthname = month_name($next_month);

    $next_args = array('year' => $next_year, 'month' => $next_month,
    'day' => $next_day);

    menu_item_append($context, $html, "$nextmonthname $next_day", 'display_day', $next_args);

    return $html;
}

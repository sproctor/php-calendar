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
   This file has the functions for the main displays of the calendar
*/

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;

class MonthPage extends Page
{
    /**
     * Full display for a month
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        $cid = $context->calendar->getCid();
        $month = $context->getMonth();
        $year = $context->getYear();

        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $months[$context->request->getScriptName()."?action=display_month&amp;phpcid=$cid&amp;month=$i&amp;year=$year"] =
            month_name($i);
        }
        $years = array();
        for ($i = $year - 5; $i <= $year + 5; $i++) {
            $years[$context->request->getScriptName()."?action=display_month&amp;phpcid=$cid&amp;month=$month&amp;year=$i"] = $i;
        }
        $next_month = $month + 1;
        $next_year = $year;
        if ($next_month > 12) {
            $next_month -= 12;
            $next_year++;
        }
        $prev_month = $month - 1;
        $prev_year = $year;
        if ($prev_month < 1) {
            $prev_month += 12;
            $prev_year--;
        }

        $week_start = $context->calendar->getWeekStart();
        $weeks = weeks_in_month($month, $year, $week_start);

        $first_day = 1 - day_of_week($month, 1, $year, $week_start);
        $from_date = create_datetime($month, $first_day, $year);

        $last_day = $weeks * 7 - day_of_week($month, 1, $year, $week_start);
        $to_date = create_datetime($month, $last_day + 1, $year);

        $template_variables = array();
        $template_variables['cid'] = $cid;
        $template_variables['prev_month'] = $prev_month;
        $template_variables['prev_year'] = $prev_year;
        $template_variables['next_month'] = $next_month;
        $template_variables['next_year'] = $next_year;
        $template_variables['month_name'] = month_name($month);
        $template_variables['months'] = $months;
        $template_variables['year'] = $year;
        $template_variables['years'] = $years;
        $template_variables['week_start'] = $week_start;
        $template_variables['weeks'] = $weeks;
        $template_variables['occurrences'] = get_occurrences_by_day(
            $context->calendar,
            $context->user,
            $from_date,
            $to_date
        );
        $template_variables['start_date'] = $from_date;
        return new Response($context->twig->render("month_page.html.twig", $template_variables));
    }
}

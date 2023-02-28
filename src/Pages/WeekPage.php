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

/*
   This file has the functions for the main displays of the calendar
*/

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;

class WeekPage extends Page
{
    /**
     * Full display for a month
     *
     * @param  Context $context
     * @return Response
     * @throws \Exception
     */
    public function action(Context $context)
    {
        $week = $context->request->get('week');
        if ($week == null) {
            $week = week_of_year($context->getDate());
        }
        $year = $context->getYear();
        
        $day_of_year = 2 + ($week - 1) * 7 - day_of_week(1, 1, $year);
        $from_date = create_datetime(1, $day_of_year, $year);
        $to_date = create_datetime(1, $day_of_year + 7, $year);

        $prev_date = (clone $from_date)->add(new \DateInterval('P1W'));
        $template_variables = [];
        $template_variables['prev_week_url'] = $context->createUrl(
            'display_week',
            ['week' => week_of_year($prev_date), 'year' => year_of_week_of_year($prev_date)]
        );
        $template_variables['next_week_url'] = $context->createUrl(
            'display_week',
            ['week' => week_of_year($to_date), 'year' => year_of_week_of_year($to_date)]
        );
        $template_variables['week'] = $week;
        $template_variables['date'] = $context->getDate();
        $template_variables['year'] = $year;
        $template_variables['occurrences'] = $context->calendar->getOccurrencesByDay(
            $from_date,
            $to_date,
            $context->user
        );
        $template_variables['start_date'] = $from_date;
        return new Response($context->render("week_page.html.twig", $template_variables));
    }
}

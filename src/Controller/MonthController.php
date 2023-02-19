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

namespace App\Controller;

use App\Context;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to display the month view.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 *
 * @Route("/{cid}/month")
 */
class MonthController extends AbstractController
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @Route("/", name="default_month_display")
     */
    public function displayDefaults(int $cid): Response
    {
        return $this->displayMonth(new DateTimeImmutable());
    }

    private function displayMonth(DateTimeInterface $datetime): Response {
//        $months = array();
//        for ($i = 1; $i <= 12; $i++) {
//            $months[month_name(new \DateTime(sprintf("%04d-%02d", $year, $i)))] =
//                $context->createUrl('display_month', ['year' => $year, 'month' => $i]);
//        }
//        $years = array();
//        for ($i = $year - 5; $i <= $year + 5; $i++) {
//            $years[$i] = $context->createUrl('display_month', ['month' => $month, 'year' => $i]);
//        }
//        $next_month = $month + 1;
//        $next_year = $year;
//        if ($next_month > 12) {
//            $next_month -= 12;
//            $next_year++;
//        }
//        $prev_month = $month - 1;
//        $prev_year = $year;
//        if ($prev_month < 1) {
//            $prev_month += 12;
//            $prev_year--;
//        }
//
//        $weeks = weeks_in_month($month, $year);
//
//        $first_day = 2 - day_of_week($month, 1, $year);
//        $from_date = create_datetime($month, $first_day, $year);
//
//        $last_day = $weeks * 7 - day_of_week($month, 1, $year);
//        $to_date = create_datetime($month, $last_day + 1, $year);
//
        $template_variables = array();
//        $template_variables['prev_month_url'] =
//            $context->createUrl('display_month', ['year' => $prev_year, 'month' => $prev_month]);
//        $template_variables['next_month_url'] =
//            $context->createUrl('display_month', ['year' => $next_year, 'month' => $next_month]);
//        $template_variables['date'] = $context->getDate();
//        $template_variables['months'] = $months;
//        $template_variables['year'] = $year;
//        $template_variables['years'] = $years;
//        $template_variables['weeks'] = $weeks;
//        $template_variables['occurrences'] = $context->getCalendar()->getOccurrencesByDay(
//            $from_date,
//            $to_date,
//            $context->user
//        );
//        $template_variables['start_date'] = $from_date;
        return new Response($this->render("month_page.html.twig", $template_variables));
    }
}
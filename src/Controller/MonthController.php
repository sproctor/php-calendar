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
use App\Entity\Calendar;
use App\Entity\User;
use App\Repository\CalendarRepository;
use App\Repository\OccurrenceRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * Controller used to display the month view.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
#[Route("/calendars/{cid}")]
class MonthController extends AbstractController
{
    private Context $context;
    private LoggerInterface $logger;

    public function __construct(Context $context, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->logger = $logger;
    }

    #[Route("/", name: "default_month_display")]
    public function defaultRoute(
        Request $request,
        int $cid,
        CalendarRepository $calendar_repository,
        OccurrenceRepository $occurrence_repository,
    ): Response {
        $calendar = $calendar_repository->find($cid);
        $user = $this->getUser();
        return $this->displayMonth($request, $calendar, $user, new DateTimeImmutable(), $occurrence_repository);
    }

    #[Route("/month/{year}/{month}", name: "display_month")]
    public function monthRoute(
        Request $request,
        int $cid,
        int $year,
        int $month,
        CalendarRepository $calendar_repository,
        OccurrenceRepository $occurrence_repository,
    ): Response {
        $calendar = $calendar_repository->find($cid);
        $user = $this->getUser();
        $date = new DateTimeImmutable(sprintf("%04d-%02d", $year, $month));
        return $this->displayMonth($request, $calendar, $user, $date, $occurrence_repository);
    }

    private function displayMonth(
        Request $request,
        Calendar $calendar,
        ?User $user,
        DateTimeInterface $datetime,
        OccurrenceRepository $occurrence_repository,
    ): Response {
        $cid = $calendar->getCid();
        $year = intval($datetime->format('Y'));
        $month = intval($datetime->format('n'));
        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $months[month_name(new \DateTimeImmutable(sprintf("%04d-%02d", $year, $i)))] =
                $this->generateUrl('display_month', ['cid' => $cid, 'year' => $year, 'month' => $i]);
        }
        $years = array();
        for ($i = $year - 5; $i <= $year + 5; $i++) {
            $years[$i] = $this->generateUrl('display_month', ['cid' => $cid, 'month' => $month, 'year' => $i]);
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

        $weeks = \weeks_in_month($month, $year);

        $first_day = 2 - day_of_week($month, 1, $year);
        $from_date = create_datetime($month, $first_day, $year);

        $last_day = $weeks * 7 - day_of_week($month, 1, $year);
        $to_date = create_datetime($month, $last_day + 1, $year);

        $template_variables = array();
        $template_variables['calendar'] = $calendar;
        $template_variables['user'] = $user;
        $template_variables['query_string'] = $request->getPathInfo();
        $this->logger->debug("query string: " . $request->getPathInfo());
        $template_variables['action'] = 'display_month';
        $template_variables['prev_month_url'] =
            $this->generateUrl('display_month', ['cid' => $cid, 'year' => $prev_year, 'month' => $prev_month]);
        $template_variables['next_month_url'] =
            $this->generateUrl('display_month', ['cid' => $cid, 'year' => $next_year, 'month' => $next_month]);
        $template_variables['date'] = $datetime;
        $template_variables['month'] = $month;
        $template_variables['months'] = $months;
        $template_variables['year'] = $year;
        $template_variables['years'] = $years;
        $template_variables['weeks'] = $weeks;
        $template_variables['occurrences'] = $occurrence_repository->findOccurrencesByDay(
            $calendar,
            $from_date,
            $to_date,
            $user
        );
        $template_variables['start_date'] = $from_date;
        return new Response($this->renderView("month_page.html.twig", $template_variables));
    }
}

/**
 * Takes a date, returns the full month name
 *
 * @param DateTimeInterface $date
 * @return string
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

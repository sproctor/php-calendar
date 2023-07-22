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

use App\Entity\Calendar;
use App\Entity\User;
use App\Repository\CalendarRepository;
use App\Repository\OccurrenceRepository;
use App\Repository\UserPermissionsRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to display the month view.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
#[Route("/{_locale}/calendar/{cid}")]
class CalendarController extends AbstractController
{
    public function __construct(
        private LoggerInterface           $logger,
        private CalendarRepository        $calendar_repository,
        private OccurrenceRepository      $occurrence_repository,
        private UserPermissionsRepository $user_permissions_repository,
    )
    {
    }

    #[Route("/", name: "default_view")]
    public function defaultRoute(
        int $cid,
    ): Response
    {
        $calendar = $this->calendar_repository->find($cid);
        $user = $this->getUser();
        return $this->displayMonth($calendar, $user, new DateTimeImmutable());
    }

    #[Route("/month/{year}/{month}", name: "month_view")]
    public function monthRoute(
        int $cid,
        int $year,
        int $month,
    ): Response
    {
        $calendar = $this->calendar_repository->find($cid);
        $user = $this->getUser();
        $date = new DateTimeImmutable(sprintf("%04d-%02d", $year, $month));
        return $this->displayMonth($calendar, $user, $date);
    }

    #[Route("/week/{year}/{week}", name: "week_view")]
    public function weekRoute(
        int $cid,
        int $year,
        int $week,
    ): Response
    {
        $calendar = $this->calendar_repository->find($cid);
        $user = $this->getUser();
//        $date = new DateTimeImmutable(sprintf("%04d-%02d", $year, $month));
//        return $this->displayMonth($calendar, $user, $date);
        return new Response();
    }

    #[Route('/day/{year}/{month}/{day}', name: 'day_view')]
    public function dayRoute(int $cid, int $year, int $month, int $day): Response
    {
        $calendar = $this->calendar_repository->find($cid);
        $user = $this->getUser();
        $date = new DateTimeImmutable("$year-$month-$day");
        $permissions = $this->user_permissions_repository->getUserPermissions($cid, $user);
        $yesterday = $date->sub(new DateInterval('P1D'));
        $tomorrow = $date->add(new DateInterval('P1D'));
        $occurrences = // array_filter(
            $this->occurrence_repository->findOccurrencesByDate($cid, $year, $month, $day);
//            fn(Occurrence $occurrence) => $permissions->canRead() || $occurrence->getEvent()->isOwner($user)
//        );

        return $this->render('calendar/day_view.html.twig',
            [
                'calendar' => $calendar,
                'date' => $date,
                'permissions' => $permissions,
                'prev_date' => $yesterday,
                'next_date' => $tomorrow,
                'occurrences' => $occurrences,
            ]
        );
    }

    private function displayMonth(
        Calendar          $calendar,
        ?User             $user,
        DateTimeInterface $datetime,
    ): Response
    {
        $cid = $calendar->getCid();
        $year = intval($datetime->format('Y'));
        $month = intval($datetime->format('n'));
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
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $months[month_name(new \DateTimeImmutable(sprintf("%04d-%02d", $year, $i)))] =
                $this->generateUrl('month_view', ['cid' => $cid, 'year' => $year, 'month' => $i]);
        }
        $years = [];
        for ($i = $year - 5; $i <= $year + 5; $i++) {
            $years[$i] = $this->generateUrl('month_view', ['cid' => $cid, 'month' => $month, 'year' => $i]);
        }

        $prev_month_url =
            $this->generateUrl('month_view', ['cid' => $cid, 'year' => $prev_year, 'month' => $prev_month]);
        $next_month_url =
            $this->generateUrl('month_view', ['cid' => $cid, 'year' => $next_year, 'month' => $next_month]);

        $weeks = \weeks_in_month($month, $year);

        $first_day = 2 - day_of_week($month, 1, $year);
        $from_date = create_datetime($month, $first_day, $year);

        $last_day = $weeks * 7 - day_of_week($month, 1, $year);
        $to_date = create_datetime($month, $last_day + 1, $year);

        $permissions = $this->user_permissions_repository->getUserPermissions($cid, $user);

        $occurrences = $this->occurrence_repository->findOccurrencesByDay(
            $calendar,
            $from_date,
            $to_date,
            $user
        );

        return $this->render("calendar/month_view.html.twig",
            [
                'calendar' => $calendar,
                'date' => $datetime,
                'months' => $months,
                'years' => $years,
                'permissions' => $permissions,
                'prev_month_url' => $prev_month_url,
                'next_month_url' => $next_month_url,
                'weeks' => $weeks,
                'occurrences' => $occurrences,
                'start_date' => $from_date,
            ]
        );
    }
}

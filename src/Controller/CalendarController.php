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
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to display the month view.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
#[Route("/calendars/{cid}")]
class CalendarController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private CalendarRepository $calendar_repository,
        private OccurrenceRepository $occurrence_repository,
        private UserPermissionsRepository $user_permissions_repository,
    )
    {
    }

    #[Route("/month", name: "default_month_display")]
    public function defaultRoute(
        int $cid,
    ): Response {
        $calendar = $this->calendar_repository->find($cid);
        $user = $this->getUser();
        return $this->displayMonth($calendar, $user, new DateTimeImmutable());
    }

    #[Route("/month/{year}/{month}", name: "display_month")]
    public function monthRoute(
        int $cid,
        int $year,
        int $month,
    ): Response {
        $calendar = $this->calendar_repository->find($cid);
        $user = $this->getUser();
        $date = new DateTimeImmutable(sprintf("%04d-%02d", $year, $month));
        return $this->displayMonth($calendar, $user, $date);
    }

    private function displayMonth(
        Calendar $calendar,
        ?User $user,
        DateTimeInterface $datetime,
    ): Response {
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

        $weeks = \weeks_in_month($month, $year);

        $first_day = 2 - day_of_week($month, 1, $year);
        $from_date = create_datetime($month, $first_day, $year);

        $last_day = $weeks * 7 - day_of_week($month, 1, $year);
        $to_date = create_datetime($month, $last_day + 1, $year);

        $user_permissions = null;
        if ($user !== null) {
            $user_permissions = $this->user_permissions_repository->getUserPermissions($cid, $user->getUid());
        }

        $template_variables = get_variables_for_calendar(
            function ($route, $parameters = []) {$this->generateUrl($route, $parameters); },
            $calendar,
            $user,
            $datetime,
            $user_permissions,
            $this->user_permissions_repository->getUserPermissions($cid, null),
        );
        $template_variables['prev_month_url'] =
            $this->generateUrl('display_month', ['cid' => $cid, 'year' => $prev_year, 'month' => $prev_month]);
        $template_variables['next_month_url'] =
            $this->generateUrl('display_month', ['cid' => $cid, 'year' => $next_year, 'month' => $next_month]);

        $template_variables['weeks'] = $weeks;
        $template_variables['occurrences'] = $this->occurrence_repository->findOccurrencesByDay(
            $calendar,
            $from_date,
            $to_date,
            $user
        );
        $template_variables['start_date'] = $from_date;
        return new Response($this->renderView("calendar/month_view.html.twig", $template_variables));
    }
}

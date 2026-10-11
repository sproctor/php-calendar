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
use App\Repository\OccurrenceRepository;
use App\Security\Voter\CalendarVoter;
use App\Security\Voter\EventVoter;
use App\Service\OccurrenceGrid;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The month, week and day views of a calendar, and search
 */
#[Route('/{_locale}/calendar/{cid}', requirements: ['cid' => '\d+'])]
class CalendarController extends AbstractController
{
    public function __construct(
        private OccurrenceGrid $grid,
    ) {
    }

    #[Route('/', name: 'default_view', methods: ['GET'])]
    public function defaultRoute(#[MapEntity(id: 'cid')] Calendar $calendar): Response
    {
        return $this->displayMonth($calendar, new DateTimeImmutable('first day of this month midnight'));
    }

    #[Route('/month/{year}/{month}', name: 'month_view', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}'], methods: ['GET'])]
    public function monthRoute(#[MapEntity(id: 'cid')] Calendar $calendar, int $year, int $month): Response
    {
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException();
        }

        return $this->displayMonth($calendar, create_datetime($month, 1, $year));
    }

    #[Route('/week/{year}/{week}', name: 'week_view', requirements: ['year' => '\d{4}', 'week' => '\d{1,2}'], methods: ['GET'])]
    public function weekRoute(#[MapEntity(id: 'cid')] Calendar $calendar, int $year, int $week): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $calendar);
        if ($week < 1 || $week > 53) {
            throw $this->createNotFoundException();
        }

        $start = week_start_date($week, $year);
        $end = $start->modify('+7 days');
        $previous = $start->modify('-7 days');

        return $this->render('calendar/week_view.html.twig', [
            'calendar' => $calendar,
            'date' => $start,
            'start_date' => $start,
            'week' => $week,
            'year' => $year,
            'occurrences' => $this->grid->byDay($calendar, $start, $end, $this->currentUser()),
            'prev_week_url' => $this->generateUrl('week_view', [
                'cid' => $calendar->getCid(),
                'year' => year_of_week_of_year($previous),
                'week' => week_of_year($previous),
            ]),
            'next_week_url' => $this->generateUrl('week_view', [
                'cid' => $calendar->getCid(),
                'year' => year_of_week_of_year($end),
                'week' => week_of_year($end),
            ]),
        ]);
    }

    #[Route('/day/{year}/{month}/{day}', name: 'day_view', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}', 'day' => '\d{1,2}'], methods: ['GET'])]
    public function dayRoute(#[MapEntity(id: 'cid')] Calendar $calendar, int $year, int $month, int $day): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $calendar);
        if (!checkdate($month, $day, $year)) {
            throw $this->createNotFoundException();
        }

        $date = _create_datetime($month, $day, $year);
        $next = $date->modify('+1 day');

        return $this->render('calendar/day_view.html.twig', [
            'calendar' => $calendar,
            'date' => $date,
            'prev_date' => $date->modify('-1 day'),
            'next_date' => $next,
            'occurrences' => $this->grid->findVisible($calendar, $date, $next, $this->currentUser()),
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(
        #[MapEntity(id: 'cid')] Calendar $calendar,
        Request $request,
        OccurrenceRepository $occurrenceRepository,
        EventVoter $eventVoter,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $calendar);

        $query = trim($request->query->getString('q'));
        $from = self::parseDate($request->query->getString('from'));
        $to = self::parseDate($request->query->getString('to'));
        $sort = $request->query->getString('sort') === 'subject' ? 'subject' : 'start';
        $order = $request->query->getString('order') === 'DESC' ? 'DESC' : 'ASC';

        $results = [];
        $keywords = array_filter(preg_split('/\s+/', $query), fn($k) => $k !== '');
        if ($keywords !== [] || $from !== null || $to !== null) {
            $results = array_filter(
                $occurrenceRepository->search($calendar, $keywords, $from, $to?->modify('+1 day'), $sort, $order),
                fn($o) => $eventVoter->canView($o->getEvent(), $this->currentUser()),
            );
        }

        return $this->render('search.html.twig', [
            'calendar' => $calendar,
            'query' => $query,
            'from' => $from,
            'to' => $to,
            'sort' => $sort,
            'order' => $order,
            'results' => $results,
        ]);
    }

    private static function parseDate(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? null : $date;
    }

    private function displayMonth(Calendar $calendar, DateTimeImmutable $date): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $calendar);

        $year = intval($date->format('Y'));
        $month = intval($date->format('n'));
        $cid = $calendar->getCid();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[month_name(_create_datetime($i, 1, $year))] =
                $this->generateUrl('month_view', ['cid' => $cid, 'year' => $year, 'month' => $i]);
        }
        $years = [];
        for ($i = $year - 5; $i <= $year + 5; $i++) {
            $years[$i] = $this->generateUrl('month_view', ['cid' => $cid, 'month' => $month, 'year' => $i]);
        }
        $previous = $date->modify('-1 month');
        $next = $date->modify('+1 month');

        $weeks = weeks_in_month($month, $year);
        // The grid starts on the first day of the week containing the 1st
        $first_day = 1 - day_of_week($month, 1, $year);
        $from_date = create_datetime($month, $first_day, $year);
        $to_date = create_datetime($month, $first_day + $weeks * 7, $year);

        return $this->render('calendar/month_view.html.twig', [
            'calendar' => $calendar,
            'date' => $date,
            'months' => $months,
            'years' => $years,
            'prev_month_url' => $this->generateUrl('month_view', [
                'cid' => $cid,
                'year' => $previous->format('Y'),
                'month' => $previous->format('n'),
            ]),
            'next_month_url' => $this->generateUrl('month_view', [
                'cid' => $cid,
                'year' => $next->format('Y'),
                'month' => $next->format('n'),
            ]),
            'weeks' => $weeks,
            'occurrences' => $this->grid->byDay($calendar, $from_date, $to_date, $this->currentUser()),
            'start_date' => $from_date,
        ]);
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}

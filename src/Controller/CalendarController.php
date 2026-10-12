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
use App\Service\CalendarDates;
use App\Service\OccurrenceGrid;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The month and day views of a calendar, and search
 */
#[Route('/{_locale}/calendar/{slug}', requirements: ['slug' => Calendar::SLUG_PATTERN])]
class CalendarController extends AbstractController
{
    public function __construct(
        private OccurrenceGrid $grid,
        private CalendarDates $dates,
    ) {
    }

    #[Route('/', name: 'default_view', methods: ['GET'])]
    public function defaultRoute(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar): Response
    {
        return $this->displayMonth($calendar, new DateTimeImmutable('first day of this month midnight'));
    }

    #[Route('/month/{year}/{month}', name: 'month_view', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}'], methods: ['GET'])]
    public function monthRoute(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, int $year, int $month): Response
    {
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException();
        }

        return $this->displayMonth($calendar, $this->dates->date($year, $month, 1));
    }

    #[Route('/day/{year}/{month}/{day}', name: 'day_view', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}', 'day' => '\d{1,2}'], methods: ['GET'])]
    public function dayRoute(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, int $year, int $month, int $day): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $calendar);
        if (!checkdate($month, $day, $year)) {
            throw $this->createNotFoundException();
        }

        $date = $this->dates->date($year, $month, $day);
        $next = $date->modify('+1 day');

        return $this->render('calendar/day_view.html.twig', [
            'calendar' => $calendar,
            'date' => $date,
            'prev_date' => $date->modify('-1 day'),
            'next_date' => $next,
            'today_url' => $this->generateUrl('day_view', ['slug' => $calendar->getSlug()]
                + $this->dateParts(new DateTimeImmutable('today'))),
            'create_date' => $date,
            'occurrences' => $this->grid->findVisible($calendar, $date, $next, $this->currentUser()),
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
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
        $slug = $calendar->getSlug();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$this->dates->monthName($this->dates->date($year, $i, 1))] =
                $this->generateUrl('month_view', ['slug' => $slug, 'year' => $year, 'month' => $i]);
        }
        $years = [];
        for ($i = $year - 5; $i <= $year + 5; $i++) {
            $years[$i] = $this->generateUrl('month_view', ['slug' => $slug, 'month' => $month, 'year' => $i]);
        }
        $previous = $date->modify('-1 month');
        $next = $date->modify('+1 month');

        $weeks = $this->dates->weeksInMonth($year, $month);
        $from_date = $this->dates->monthGridStart($year, $month);
        $to_date = $from_date->modify('+' . ($weeks * 7) . ' days');
        $days = $this->grid->byDay($calendar, $from_date, $to_date, $this->currentUser());
        $monthStart = $date->modify('first day of this month');
        $monthEnd = $monthStart->modify('+1 month');

        return $this->render('calendar/month_view.html.twig', [
            'calendar' => $calendar,
            'date' => $date,
            'months' => $months,
            'years' => $years,
            'prev_month_url' => $this->generateUrl('month_view', [
                'slug' => $slug,
                'year' => $previous->format('Y'),
                'month' => $previous->format('n'),
            ]),
            'next_month_url' => $this->generateUrl('month_view', [
                'slug' => $slug,
                'year' => $next->format('Y'),
                'month' => $next->format('n'),
            ]),
            'today_url' => $this->generateUrl('default_view', ['slug' => $slug]),
            'weeks' => $weeks,
            'occurrences' => OccurrenceGrid::capped($days, $calendar->getEventsMax()),
            // The phone layout lists every event of the month itself
            'agenda' => array_filter(
                $days,
                fn(string $day) => $day >= $monthStart->format('Y-m-d') && $day < $monthEnd->format('Y-m-d'),
                ARRAY_FILTER_USE_KEY,
            ),
            'start_date' => $from_date,
            'create_date' => $this->createDate($monthStart, $monthEnd),
        ]);
    }

    /**
     * The date "Create event" suggests for a view of [$from, $to): today when it's in view, else the first day
     */
    private function createDate(DateTimeImmutable $from, DateTimeImmutable $to): DateTimeImmutable
    {
        $today = new DateTimeImmutable('today');

        return $today >= $from && $today < $to ? $today : $from;
    }

    /**
     * @return array{year: string, month: string, day: string}
     */
    private function dateParts(DateTimeImmutable $date): array
    {
        return ['year' => $date->format('Y'), 'month' => $date->format('m'), 'day' => $date->format('d')];
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}

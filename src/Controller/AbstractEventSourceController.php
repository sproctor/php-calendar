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
use App\Entity\EventSource;
use App\Entity\User;
use App\Repository\OccurrenceRepository;
use App\Security\Voter\CalendarVoter;
use App\Security\Voter\EventVoter;
use App\Service\CalendarDates;
use App\Service\EventSourceRoutes;
use App\Service\OccurrenceGrid;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The month view, day view and search of an event source: a calendar (CalendarController) or a view of
 * several calendars (ViewController), whose routes call these
 */
abstract class AbstractEventSourceController extends AbstractController
{
    public function __construct(
        protected OccurrenceGrid $grid,
        protected CalendarDates $dates,
    ) {
    }

    protected function monthPage(EventSource $source, DateTimeImmutable $date): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $source);

        $year = intval($date->format('Y'));
        $month = intval($date->format('n'));

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$this->dates->monthName($this->dates->date($year, $i, 1))] = $this->monthUrl($source, $year, $i);
        }
        $years = [];
        for ($i = $year - 5; $i <= $year + 5; $i++) {
            $years[$i] = $this->monthUrl($source, $i, $month);
        }
        $previous = $date->modify('-1 month');
        $next = $date->modify('+1 month');

        $weeks = $this->dates->weeksInMonth($year, $month);
        $from_date = $this->dates->monthGridStart($year, $month);
        $to_date = $from_date->modify('+' . ($weeks * 7) . ' days');
        $days = $this->grid->byDay($source, $from_date, $to_date, $this->currentUser());
        $monthStart = $date->modify('first day of this month');
        $monthEnd = $monthStart->modify('+1 month');

        return $this->render('calendar/month_view.html.twig', $this->context($source) + [
            'date' => $date,
            'months' => $months,
            'years' => $years,
            'prev_month_url' => $this->monthUrl($source, (int) $previous->format('Y'), (int) $previous->format('n')),
            'next_month_url' => $this->monthUrl($source, (int) $next->format('Y'), (int) $next->format('n')),
            'today_url' => $this->generateUrl(EventSourceRoutes::route($source, 'default'), EventSourceRoutes::parameters($source)),
            'weeks' => $weeks,
            'occurrences' => OccurrenceGrid::capped($days, $source->getEventsMax()),
            'marks' => array_map(OccurrenceGrid::dayMarks(...), $days),
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

    protected function dayPage(EventSource $source, int $year, int $month, int $day): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $source);
        if (!checkdate($month, $day, $year)) {
            throw $this->createNotFoundException();
        }

        $date = $this->dates->date($year, $month, $day);
        $next = $date->modify('+1 day');
        $occurrences = $this->grid->findVisible($source, $date, $next, $this->currentUser());

        return $this->render('calendar/day_view.html.twig', $this->context($source) + [
            'date' => $date,
            'prev_date' => $date->modify('-1 day'),
            'next_date' => $next,
            'today_url' => $this->generateUrl(EventSourceRoutes::route($source, 'day'),
                EventSourceRoutes::parameters($source, $this->dateParts(new DateTimeImmutable('today')))),
            'create_date' => $date,
            'occurrences' => $occurrences,
            'marks' => OccurrenceGrid::dayMarks($occurrences),
        ]);
    }

    protected function searchPage(
        EventSource $source,
        Request $request,
        OccurrenceRepository $occurrenceRepository,
        EventVoter $eventVoter,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::READ, $source);

        $query = trim($request->query->getString('q'));
        $from = self::parseDate($request->query->getString('from'));
        $to = self::parseDate($request->query->getString('to'));
        $sort = $request->query->getString('sort') === 'subject' ? 'subject' : 'start';
        $order = $request->query->getString('order') === 'DESC' ? 'DESC' : 'ASC';

        $results = [];
        $keywords = array_filter(preg_split('/\s+/', $query), fn($k) => $k !== '');
        if ($keywords !== [] || $from !== null || $to !== null) {
            $results = array_filter(
                $occurrenceRepository->search($source, $keywords, $from, $to?->modify('+1 day'), $sort, $order),
                fn($o) => $eventVoter->canView($o->getEvent(), $this->currentUser()),
            );
        }

        return $this->render('search.html.twig', $this->context($source) + [
            'query' => $query,
            'from' => $from,
            'to' => $to,
            'sort' => $sort,
            'order' => $order,
            'results' => $results,
        ]);
    }

    /**
     * What every page about $source passes its template: `source`, and for a calendar `calendar` too,
     * which the layout uses for the calendar's menus
     */
    protected function context(EventSource $source): array
    {
        return ['source' => $source] + ($source instanceof Calendar ? ['calendar' => $source] : []);
    }

    /**
     * @return array{year: string, month: string, day: string}
     */
    protected function dateParts(DateTimeImmutable $date): array
    {
        return ['year' => $date->format('Y'), 'month' => $date->format('m'), 'day' => $date->format('d')];
    }

    protected function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }

    private function monthUrl(EventSource $source, int $year, int $month): string
    {
        return $this->generateUrl(EventSourceRoutes::route($source, 'month'),
            EventSourceRoutes::parameters($source, ['year' => $year, 'month' => $month]));
    }

    /**
     * The date "Create event" suggests for a view of [$from, $to): today when it's in view, else the first day
     */
    private function createDate(DateTimeImmutable $from, DateTimeImmutable $to): DateTimeImmutable
    {
        $today = new DateTimeImmutable('today');

        return $today >= $from && $today < $to ? $today : $from;
    }

    private static function parseDate(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? null : $date;
    }
}

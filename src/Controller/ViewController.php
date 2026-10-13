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
use App\Entity\View;
use App\Repository\OccurrenceRepository;
use App\Security\Voter\CalendarVoter;
use App\Security\Voter\EventVoter;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The month and day views and search of a view (several calendars together), and choosing which of its
 * calendars a new event goes on. The view comes from ViewValueResolver.
 */
#[Route('/{_locale}/view/{slug}', requirements: ['slug' => Calendar::SLUG_PATTERN])]
class ViewController extends AbstractEventSourceController
{
    #[Route('/', name: 'view_default', methods: ['GET'])]
    public function defaultRoute(View $view, Request $request): Response
    {
        return $this->monthPage($view, new DateTimeImmutable('first day of this month midnight'), $request);
    }

    #[Route('/month/{year}/{month}', name: 'view_month', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}'], methods: ['GET'])]
    public function monthRoute(View $view, int $year, int $month, Request $request): Response
    {
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException();
        }

        return $this->monthPage($view, $this->dates->date($year, $month, 1), $request);
    }

    #[Route('/day/{year}/{month}/{day}', name: 'view_day', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}', 'day' => '\d{1,2}'], methods: ['GET'])]
    public function dayRoute(View $view, int $year, int $month, int $day, Request $request): Response
    {
        return $this->dayPage($view, $year, $month, $day, $request);
    }

    #[Route('/search', name: 'view_search', methods: ['GET'])]
    public function search(View $view, Request $request, OccurrenceRepository $occurrenceRepository, EventVoter $eventVoter): Response
    {
        return $this->searchPage($view, $request, $occurrenceRepository, $eventVoter);
    }

    /**
     * "Create event" in a view: straight to the form when the visitor can only add events to one of
     * its calendars, otherwise a choice of them
     */
    #[Route('/event/new/{year}/{month}/{day}', name: 'view_create_event', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}', 'day' => '\d{1,2}'], methods: ['GET'])]
    public function createEvent(View $view, int $year, int $month, int $day): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::CREATE, $view);
        if (!checkdate($month, $day, $year)) {
            throw $this->createNotFoundException();
        }
        $date = ['year' => $year, 'month' => $month, 'day' => $day];
        $calendars = array_values(array_filter(
            $view->getCalendars(),
            fn(Calendar $calendar) => $this->isGranted(CalendarVoter::CREATE, $calendar),
        ));
        if (count($calendars) === 1) {
            return $this->redirectToRoute('create_event', ['slug' => $calendars[0]->getSlug()] + $date);
        }

        return $this->render('view/choose_calendar.html.twig', $this->context($view) + [
            'calendars' => $calendars,
            'date' => $this->dates->date($year, $month, $day),
            'date_parts' => $date,
        ]);
    }
}

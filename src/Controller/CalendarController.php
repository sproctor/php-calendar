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
use App\Repository\OccurrenceRepository;
use App\Security\Voter\EventVoter;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The month and day views of a calendar, and search
 */
#[Route('/{_locale}/calendar/{slug}', requirements: ['slug' => Calendar::SLUG_PATTERN])]
class CalendarController extends AbstractEventSourceController
{
    #[Route('/', name: 'default_view', methods: ['GET'])]
    public function defaultRoute(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar): Response
    {
        return $this->monthPage($calendar, new DateTimeImmutable('first day of this month midnight'));
    }

    #[Route('/month/{year}/{month}', name: 'month_view', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}'], methods: ['GET'])]
    public function monthRoute(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, int $year, int $month): Response
    {
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException();
        }

        return $this->monthPage($calendar, $this->dates->date($year, $month, 1));
    }

    #[Route('/day/{year}/{month}/{day}', name: 'day_view', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}', 'day' => '\d{1,2}'], methods: ['GET'])]
    public function dayRoute(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, int $year, int $month, int $day): Response
    {
        return $this->dayPage($calendar, $year, $month, $day);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        OccurrenceRepository $occurrenceRepository,
        EventVoter $eventVoter,
    ): Response {
        return $this->searchPage($calendar, $request, $occurrenceRepository, $eventVoter);
    }
}

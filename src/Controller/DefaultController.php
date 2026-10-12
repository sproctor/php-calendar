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

use App\Entity\User;
use App\Repository\CalendarRepository;
use App\Security\Voter\CalendarVoter;
use App\Service\LocaleService;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The start page: sends visitors to setup when there are no calendars, to the calendar when they can only
 * see one, and otherwise lists the calendars they can see
 */
class DefaultController extends AbstractController
{
    #[Route('/', name: 'default', methods: ['GET'])]
    public function index(Request $request, CalendarRepository $repository, LocaleService $localeService): Response
    {
        try {
            $calendars = $repository->findBy([], ['title' => 'ASC']);
        } catch (ConnectionException|TableNotFoundException) {
            return $this->redirectToRoute('setup');
        }
        if ($calendars === []) {
            return $this->redirectToRoute('setup');
        }

        $user = $this->getUser();
        $user = $user instanceof User ? $user : null;

        $readable = array_values(array_filter($calendars, fn($calendar) => $this->isGranted(CalendarVoter::READ, $calendar)));
        if (count($readable) === 1) {
            return $this->redirectToRoute('default_view', [
                '_locale' => $localeService->pickLanguage($request, $user, $readable[0]),
                'slug' => $readable[0]->getSlug(),
            ]);
        }
        if ($readable === [] && $user === null) {
            return $this->redirectToRoute('app_login');
        }

        // FormattingLocaleSubscriber picked the language, since this URL doesn't name one
        $locale = $request->getLocale();

        return $this->render('calendar_list.html.twig', [
            'calendars' => $readable,
            'locale' => $locale,
        ]);
    }
}

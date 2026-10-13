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
namespace App\View;

use App\Entity\Calendar;
use App\Entity\User;
use App\Entity\View;
use App\Repository\CalendarRepository;
use App\Security\Voter\CalendarVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The built-in view of every calendar the visitor can read (/view/all/), made on the fly rather than
 * saved. Its calendars aren't marked with colors, which would differ between visitors (each sees their
 * own set of calendars); events keep their category colors and show their calendar's name.
 */
class AllCalendarsView
{
    /** Distinct colors for marking calendars in a view, offered when admins add one */
    public const PALETTE = ['#1f6feb', '#2da44e', '#cf222e', '#8250df', '#bf8700', '#0a7ea4', '#bc4c00', '#d6336c', '#57606a', '#1a7f37'];

    public function __construct(
        private CalendarRepository $calendarRepository,
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * The view as the current visitor sees it, or as $user would (for their personal feed, which calendar
     * apps fetch without logging in)
     */
    public function build(?User $user = null): View
    {
        $calendars = array_values(array_filter(
            $this->calendarRepository->findBy([], ['title' => 'ASC']),
            fn(Calendar $calendar) => $user !== null
                ? $this->security->isGrantedForUser($user, CalendarVoter::READ, $calendar)
                : $this->security->isGranted(CalendarVoter::READ, $calendar),
        ));
        $view = (new View())
            ->setTitle($this->translator->trans('all-calendars-title'))
            ->setSlug(View::ALL)
            // One timezone if they share it; visitors' own settings come first anyway
            ->setTimezone($calendars !== [] ? $calendars[0]->getTimezone() : 'UTC')
            ->setEventsMax(max(array_map(fn(Calendar $calendar) => $calendar->getEventsMax(), $calendars) ?: [8]));

        return $view->setCalendars(array_map(fn(Calendar $calendar) => [$calendar, null], $calendars));
    }

    /**
     * Whether the visitor can read more than one calendar, so the view is worth offering
     */
    public function isUseful(): bool
    {
        return count($this->build()->getCalendars()) > 1;
    }
}

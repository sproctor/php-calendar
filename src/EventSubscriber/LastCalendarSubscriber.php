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

namespace App\EventSubscriber;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Install\InstallState;
use App\Repository\CalendarRepository;
use Doctrine\DBAL\Exception as DBALException;
use App\Security\Voter\CalendarVoter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Remembers the calendar last viewed, so pages that aren't about a calendar (settings, admin, login) can
 * link back to it. Only uses a session that already exists, so anonymous visitors don't get a cookie
 * just for this.
 */
class LastCalendarSubscriber implements EventSubscriberInterface
{
    private const SESSION_KEY = 'last_calendar';

    public function __construct(
        private RequestStack $requestStack,
        private CalendarRepository $calendarRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
        private InstallState $installState,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments'];
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$request->hasPreviousSession()) {
            return;
        }
        foreach ($event->getArguments() as $argument) {
            $calendar = match (true) {
                $argument instanceof Calendar => $argument,
                $argument instanceof Event => $argument->getCalendar(),
                $argument instanceof Occurrence => $argument->getEvent()->getCalendar(),
                default => null,
            };
            if ($calendar !== null) {
                $request->getSession()->set(self::SESSION_KEY, $calendar->getCid());

                return;
            }
        }
    }

    /**
     * The calendar last viewed in this session, or when there's none, the only calendar the visitor can
     * see. Null when neither applies.
     */
    public function getLastCalendar(): ?Calendar
    {
        if (!$this->installState->isConfigured()) {
            return null;
        }
        try {
            return $this->findLastCalendar();
        } catch (DBALException) {
            // E.g. the installer's pages while the database is being set up
            return null;
        }
    }

    private function findLastCalendar(): ?Calendar
    {
        $request = $this->requestStack->getMainRequest();
        if ($request !== null && $request->hasPreviousSession()) {
            $cid = $request->getSession()->get(self::SESSION_KEY);
            $calendar = $cid === null ? null : $this->calendarRepository->find($cid);
            if ($calendar !== null && $this->authorizationChecker->isGranted(CalendarVoter::READ, $calendar)) {
                return $calendar;
            }
        }
        $readable = array_filter(
            $this->calendarRepository->findAll(),
            fn(Calendar $calendar) => $this->authorizationChecker->isGranted(CalendarVoter::READ, $calendar),
        );

        return count($readable) === 1 ? reset($readable) : null;
    }
}

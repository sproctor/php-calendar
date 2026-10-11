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
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets PHP's default timezone, which forms and date formatting use, to the user's timezone. When the user
 * hasn't chosen one (or isn't logged in), uses the timezone of the calendar the request is about, found
 * from the controller's Calendar, Event or Occurrence argument. Dates are stored in UTC either way.
 */
class TimezoneSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
            KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            date_default_timezone_set($this->userTimezone() ?? 'UTC');
        }
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest() || $this->userTimezone() !== null) {
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
                date_default_timezone_set($calendar->getTimezone());

                return;
            }
        }
    }

    private function userTimezone(): ?string
    {
        $user = $this->security->getUser();
        $timezone = $user instanceof User ? $user->getTimezone() : null;

        return $timezone !== null && $timezone !== '' && in_array($timezone, \DateTimeZone::listIdentifiers(), true)
            ? $timezone
            : null;
    }
}

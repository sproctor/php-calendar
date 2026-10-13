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

use App\Entity\EventSource;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets PHP's default timezone, which forms and date formatting use. In order of preference: the user's
 * timezone setting; the timezone a visitor chose on this device (a cookie, so it works without logging
 * in); the timezone of the calendar the request is about, found from the controller's calendar or view, Event or
 * Occurrence argument; UTC. Dates are stored in UTC either way.
 */
class TimezoneSubscriber implements EventSubscriberInterface
{
    /** The cookie a visitor's own timezone choice is kept in; set and cleared by phpc.js */
    public const COOKIE = 'phpc_timezone';

    /** Request attribute saying where the timezone came from: one of the SOURCE_* constants */
    public const SOURCE_ATTRIBUTE = '_timezone_source';
    public const SOURCE_USER = 'user';
    public const SOURCE_COOKIE = 'cookie';
    public const SOURCE_CALENDAR = 'calendar';
    public const SOURCE_DEFAULT = 'default';

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
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        [$timezone, $source] = $this->chosenTimezone($request) ?? ['UTC', self::SOURCE_DEFAULT];
        date_default_timezone_set($timezone);
        $request->attributes->set(self::SOURCE_ATTRIBUTE, $source);
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || $this->chosenTimezone($request) !== null) {
            return;
        }
        foreach ($event->getArguments() as $argument) {
            $calendar = match (true) {
                $argument instanceof EventSource => $argument,
                $argument instanceof Event => $argument->getCalendar(),
                $argument instanceof Occurrence => $argument->getEvent()->getCalendar(),
                default => null,
            };
            if ($calendar !== null) {
                date_default_timezone_set($calendar->getTimezone());
                $request->attributes->set(self::SOURCE_ATTRIBUTE, self::SOURCE_CALENDAR);

                return;
            }
        }
    }

    /**
     * The timezone the visitor picked, as [timezone, source]: their user setting, else the one saved on
     * this device. Null when they haven't picked one, so the calendar's applies.
     *
     * @return array{0: string, 1: string}|null
     */
    private function chosenTimezone(Request $request): ?array
    {
        $user = $this->security->getUser();
        $timezone = $user instanceof User ? $user->getTimezone() : null;
        if (self::isValid($timezone)) {
            return [$timezone, self::SOURCE_USER];
        }
        $timezone = $request->cookies->get(self::COOKIE);
        if (self::isValid($timezone)) {
            return [$timezone, self::SOURCE_COOKIE];
        }

        return null;
    }

    private static function isValid(mixed $timezone): bool
    {
        return is_string($timezone) && $timezone !== '' && in_array($timezone, \DateTimeZone::listIdentifiers(), true);
    }
}

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

use App\Entity\User;
use App\Service\LocaleService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * Sets PHP's default locale, which ICU date formatting and the first day of the week use,
 * to the locale picked by LocaleService::getFormattingLocale(). Pages whose URL doesn't name a
 * language, such as the login page, get the one picked by LocaleService::pickLanguage().
 */
class FormattingLocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LocaleService $localeService,
        private Security $security,
        private LocaleSwitcher $localeSwitcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // After the router sets the request locale (32), LocaleListener (16) and the firewall (8)
        return [KernelEvents::REQUEST => ['onKernelRequest', 0]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $user = $this->security->getUser();
        $user = $user instanceof User ? $user : null;
        if ($event->isMainRequest() && !$request->attributes->has('_locale')) {
            // Too late for LocaleListener, which runs before the firewall knows the user
            $language = $this->localeService->pickLanguage($request, $user);
            $request->setLocale($language);
            $this->localeSwitcher->setLocale($language);
        }
        \Locale::setDefault($this->localeService->getFormattingLocale($request, $user?->getLocale()));
    }
}

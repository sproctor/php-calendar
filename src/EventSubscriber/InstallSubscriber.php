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

use App\Install\InstallState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Sends every request to the installer until a database is configured, and shows a maintenance page
 * while the database schema is out of date (after uploading a new version) until an admin updates it.
 */
class InstallSubscriber implements EventSubscriberInterface
{
    /** Routes that work before installation is finished */
    private const ALLOWED_ROUTES = ['setup', 'setup_update', 'setup_import', 'app_login', '_logout_main'];

    public function __construct(
        private InstallState $installState,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // After the router (32), so the route is known, and before the firewall (8)
        return [KernelEvents::REQUEST => ['onKernelRequest', 30]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = (string) $event->getRequest()->attributes->get('_route');
        if (in_array($route, self::ALLOWED_ROUTES, true) || str_starts_with($route, '_wdt') || str_starts_with($route, '_profiler')) {
            return;
        }

        if (!$this->installState->isConfigured()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('setup')));
        } elseif ($this->installState->hasPendingMigrations()) {
            $event->setResponse(new Response(
                $this->twig->render('setup/maintenance.html.twig'),
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Retry-After' => '300'],
            ));
        }
    }
}

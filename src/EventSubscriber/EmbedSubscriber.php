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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Controls framing. The read-only calendar pages can be embedded in other sites when requested with
 * ?embed=1 (see public/static/embed.js); every other response may only be framed by this site, which
 * keeps forms safe from clickjacking.
 */
class EmbedSubscriber implements EventSubscriberInterface
{
    public const EMBEDDABLE_ROUTES = ['default_view', 'month_view', 'day_view', 'event_view', 'search'];

    public static function isEmbedded(Request $request): bool
    {
        return $request->query->get('embed') === '1'
            && in_array($request->attributes->get('_route'), self::EMBEDDABLE_ROUTES, true);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $headers = $event->getResponse()->headers;
        if (self::isEmbedded($event->getRequest()) && $event->getResponse()->isSuccessful()) {
            $headers->set('Content-Security-Policy', 'frame-ancestors *');
        } elseif (!$headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }
    }
}

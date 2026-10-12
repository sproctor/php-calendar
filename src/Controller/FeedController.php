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
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CalendarFeed;
use App\Service\CalendarFeedLinks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Attribute\Route;

/**
 * iCalendar feeds that calendar apps subscribe to (see CalendarFeed and CalendarFeedLinks)
 */
class FeedController extends AbstractController
{
    /** How long apps and caches may reuse a feed before asking again */
    private const MAX_AGE = 900;

    public function __construct(
        private CalendarFeed $feed,
        private CalendarFeedLinks $links,
    ) {
    }

    #[Route('/calendar/{slug}/feed.ics', name: 'calendar_feed', requirements: ['slug' => Calendar::SLUG_PATTERN], methods: ['GET'])]
    public function public(Request $request, #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar): Response
    {
        if (!$this->links->isPublic($calendar)) {
            throw $this->createNotFoundException();
        }

        return $this->respond($request, $calendar, $this->feed->build($calendar, null), true);
    }

    #[Route('/calendar/{slug}/feed/{uid}-{token}.ics', name: 'calendar_personal_feed',
        requirements: ['slug' => Calendar::SLUG_PATTERN, 'uid' => '\d+', 'token' => '[0-9a-f]{32}'], methods: ['GET'])]
    public function personal(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        int $uid,
        string $token,
        UserRepository $userRepository,
    ): Response {
        $user = $userRepository->find($uid);
        // The same answer for every kind of bad link
        if ($user === null || $user->isDisabled() || !$this->links->isValidToken($calendar, $user, $token)) {
            throw $this->createNotFoundException();
        }

        return $this->respond($request, $calendar, $this->feed->build($calendar, $user), false);
    }

    /**
     * Makes new personal feed links for every calendar, so the old ones stop working
     */
    #[Route('/{_locale}/feeds/reset', name: 'reset_feed_links', methods: ['POST'])]
    public function reset(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('reset-feed-links', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        $user->resetFeedKey();
        $entityManager->flush();
        $this->addFlash('success', 'feed-links-reset-message');

        $back = $request->getPayload()->getString('back');

        return $this->redirect(str_starts_with($back, '/') && !str_starts_with($back, '//') ? $back : $this->generateUrl('default'));
    }

    private function respond(Request $request, Calendar $calendar, string $ics, bool $public): Response
    {
        $response = new Response($ics, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="' . $calendar->getSlug() . '.ics"',
        ]);
        $response->setEtag(hash('xxh128', $ics));
        $response->setMaxAge(self::MAX_AGE);
        // A personal feed must not be kept by shared caches
        $public ? $response->setPublic() : $response->setPrivate();
        // The feed doesn't depend on the session, which Symfony would otherwise make uncacheable
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        $response->isNotModified($request);

        return $response;
    }
}

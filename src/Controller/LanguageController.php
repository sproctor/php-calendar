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
use App\Service\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Choosing a language: remembering the one picked from the menu, and links to a calendar that don't
 * name a language
 */
class LanguageController extends AbstractController
{
    public function __construct(private LocaleService $localeService)
    {
    }

    /**
     * A calendar link without a language, e.g. to share: opens the calendar in the language picked by
     * LocaleService::pickLanguage(), which includes the calendar's default language
     */
    #[Route('/calendar/{slug}', name: 'calendar_entry', requirements: ['slug' => Calendar::SLUG_PATTERN], methods: ['GET'])]
    #[Route('/calendar/{slug}/', name: 'calendar_entry_slash', requirements: ['slug' => Calendar::SLUG_PATTERN], methods: ['GET'])]
    public function calendar(Request $request, #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar): Response
    {
        return $this->redirectToRoute('default_view', [
            '_locale' => $this->localeService->pickLanguage($request, $this->currentUser(), $calendar),
            'slug' => $calendar->getSlug(),
        ] + $request->query->all());
    }

    /**
     * The language menu: remembers $language and goes to "to", the current page in that language. A
     * logged-in user's setting changes too, unless it's already a regional form of $language.
     */
    #[Route('/language/{language}', name: 'change_language', methods: ['GET'])]
    public function change(Request $request, string $language, EntityManagerInterface $entityManager): Response
    {
        if (!$this->localeService->isLanguage($language)) {
            throw new NotFoundHttpException();
        }

        $user = $this->currentUser();
        if ($user !== null && \Locale::getPrimaryLanguage($user->getLocale() ?? '') !== $language) {
            $user->setLocale($language);
            $entityManager->flush();
        }

        // Only paths on this site, so the link can't send people elsewhere
        $to = (string) $request->query->get('to', '');
        if (!str_starts_with($to, '/') || str_starts_with($to, '//') || str_contains($to, '\\')) {
            $to = $request->getBasePath() . '/';
        }
        $response = new RedirectResponse($to);
        $response->headers->setCookie(Cookie::create(LocaleService::COOKIE, $language)
            ->withExpires(new \DateTimeImmutable('+1 year'))
            ->withPath($request->getBasePath() . '/')
            ->withSecure($request->isSecure())
            ->withSameSite(Cookie::SAMESITE_LAX));

        return $response;
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}

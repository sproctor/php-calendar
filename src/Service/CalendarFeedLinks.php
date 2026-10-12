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
namespace App\Service;

use App\Entity\Calendar;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Links calendar apps subscribe to: the public feed of a calendar anyone can read, and each user's
 * personal feed, which includes what only they can see. A personal link carries a token made from the
 * user's feed key, so it only works for that calendar and stops working when the key is reset.
 */
class CalendarFeedLinks
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.secret%')]
        private string $secret,
    ) {
    }

    public function isPublic(Calendar $calendar): bool
    {
        return $calendar->getAnonPermission() >= Calendar::ANON_READ;
    }

    public function publicUrl(Calendar $calendar): string
    {
        return $this->urlGenerator->generate('calendar_feed', ['slug' => $calendar->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * May create the user's feed key, which the caller saves
     */
    public function personalUrl(Calendar $calendar, User $user): string
    {
        return $this->urlGenerator->generate('calendar_personal_feed', [
            'slug' => $calendar->getSlug(),
            'uid' => $user->getUid(),
            'token' => $this->token($calendar, $user),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * The links to offer $user (or a visitor, for null) on $calendar's pages: 'public' when anyone may
     * read it, 'personal' when logged in. Saves the user's feed key the first time.
     *
     * @return array{public: ?string, personal: ?string}
     */
    public function linksFor(Calendar $calendar, ?User $user): array
    {
        $personal = null;
        if ($user !== null) {
            $isNew = $user->hasFeedKey() === false;
            $personal = $this->personalUrl($calendar, $user);
            if ($isNew) {
                $this->entityManager->flush();
            }
        }

        return ['public' => $this->isPublic($calendar) ? $this->publicUrl($calendar) : null, 'personal' => $personal];
    }

    public function isValidToken(Calendar $calendar, User $user, string $token): bool
    {
        return hash_equals($this->token($calendar, $user), $token);
    }

    private function token(Calendar $calendar, User $user): string
    {
        return substr(hash_hmac('sha256', $calendar->getCid() . "\0" . $user->getFeedKey(), $this->secret), 0, 32);
    }
}

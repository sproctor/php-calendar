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
use App\Entity\EventSource;
use App\Entity\User;
use App\Entity\View;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Links calendar apps subscribe to: the public feed of a calendar or view anyone can read, and each
 * user's personal feed, which includes what only they can see. A personal link carries a token made from
 * the user's feed key, so it only works for that calendar or view and stops working when the key is reset.
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

    /**
     * Whether anyone can read $source: the calendar, or for a view, at least one of its calendars
     */
    public function isPublic(EventSource $source): bool
    {
        foreach ($source->getCalendars() as $calendar) {
            if ($calendar->getAnonPermission() >= Calendar::ANON_READ) {
                return true;
            }
        }

        return false;
    }

    public function publicUrl(EventSource $source): string
    {
        return $this->urlGenerator->generate(EventSourceRoutes::route($source, 'feed'),
            EventSourceRoutes::parameters($source), UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * May create the user's feed key, which the caller saves
     */
    public function personalUrl(EventSource $source, User $user): string
    {
        return $this->urlGenerator->generate(EventSourceRoutes::route($source, 'personal_feed'), EventSourceRoutes::parameters($source, [
            'uid' => $user->getUid(),
            'token' => $this->token($source, $user),
        ]), UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * The links to offer $user (or a visitor, for null) on $source's pages: 'public' when anyone may
     * read it, 'personal' when logged in. Saves the user's feed key the first time.
     *
     * @return array{public: ?string, personal: ?string}
     */
    public function linksFor(EventSource $source, ?User $user): array
    {
        $personal = null;
        if ($user !== null) {
            $isNew = $user->hasFeedKey() === false;
            $personal = $this->personalUrl($source, $user);
            if ($isNew && $user->getUid() !== null) {
                // Saves just the key: a whole flush could save whatever else the request has half done
                $table = $this->entityManager->getClassMetadata(User::class)->getTableName();
                $this->entityManager->getConnection()->update($table, ['feed_key' => $user->getFeedKey()], ['uid' => $user->getUid()]);
            }
        }

        return ['public' => $this->isPublic($source) ? $this->publicUrl($source) : null, 'personal' => $personal];
    }

    public function isValidToken(EventSource $source, User $user, string $token): bool
    {
        return hash_equals($this->token($source, $user), $token);
    }

    private function token(EventSource $source, User $user): string
    {
        // A calendar's ID alone, as before views existed, so its links keep working
        $id = $source instanceof View ? 'view:' . ($source->getVid() ?? $source->getSlug()) : (string) $source->getCid();

        return substr(hash_hmac('sha256', $id . "\0" . $user->getFeedKey(), $this->secret), 0, 32);
    }
}

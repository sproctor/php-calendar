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

namespace App\Doctrine;

use App\Entity\Calendar;
use App\Repository\CalendarRepository;
use App\Service\CalendarSlugger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Gives new calendars without a slug one made from their title
 */
#[AsEntityListener(event: Events::prePersist, entity: Calendar::class)]
class CalendarSlugListener implements ResetInterface
{
    /** @var string[] slugs handed out in this request but not saved yet */
    private array $pending = [];

    public function __construct(
        private CalendarSlugger $slugger,
        private CalendarRepository $calendarRepository,
    ) {
    }

    public function prePersist(Calendar $calendar): void
    {
        if ($calendar->getSlug() !== '') {
            return;
        }
        $slug = $this->slugger->uniqueSlug(
            $calendar->getTitle(),
            fn(string $slug) => in_array($slug, $this->pending, true)
                || $this->calendarRepository->findOneBy(['slug' => $slug]) !== null,
        );
        $this->pending[] = $slug;
        $calendar->setSlug($slug);
    }

    public function reset(): void
    {
        $this->pending = [];
    }
}

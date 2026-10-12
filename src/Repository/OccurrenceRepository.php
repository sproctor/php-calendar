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

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Occurrence;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Occurrence>
 */
class OccurrenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Occurrence::class);
    }

    /**
     * Occurrences on $calendar that overlap [$from, $to), in start order. Doesn't check permissions.
     *
     * @return Occurrence[]
     */
    public function findInRange(Calendar $calendar, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        // Full-day occurrences are stored as UTC dates, so widen the query by a day on each side
        // and then compare in the current timezone
        $candidates = $this->createQueryBuilder('o')
            ->join('o.event', 'e')
            ->addSelect('e')
            ->where('e.calendar = :calendar AND o.start < :to AND o.end >= :from')
            ->setParameter('calendar', $calendar)
            ->setParameter('from', $from->modify('-1 day'))
            ->setParameter('to', $to->modify('+1 day'))
            ->orderBy('o.start')
            ->getQuery()
            ->getResult();

        $occurrences = array_filter(
            $candidates,
            fn(Occurrence $o) => $o->getStart() < $to && $o->getEndBoundary() > $from,
        );
        usort($occurrences, fn(Occurrence $a, Occurrence $b) => $a->getStart() <=> $b->getStart());

        return $occurrences;
    }

    /**
     * Occurrences on $calendar whose event's subject, description or location contains every keyword and that overlap
     * the optional date range, in the given order. Doesn't check permissions.
     *
     * @param string[] $keywords
     * @return Occurrence[]
     */
    public function search(
        Calendar $calendar,
        array $keywords,
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        string $sort,
        string $order,
    ): array {
        $query = $this->createQueryBuilder('o')
            ->join('o.event', 'e')
            ->addSelect('e')
            ->where('e.calendar = :calendar')
            ->setParameter('calendar', $calendar);
        foreach (array_values($keywords) as $i => $keyword) {
            $query->andWhere("e.subject LIKE :kw$i OR e.description LIKE :kw$i OR e.location LIKE :kw$i")
                ->setParameter("kw$i", '%' . addcslashes($keyword, '%_\\') . '%');
        }
        if ($from !== null) {
            $query->andWhere('o.end >= :from')->setParameter('from', $from->modify('-1 day'));
        }
        if ($to !== null) {
            $query->andWhere('o.start < :to')->setParameter('to', $to->modify('+1 day'));
        }
        $direction = $order === 'DESC' ? \SortDirection::Descending : \SortDirection::Ascending;
        $query->orderBy($sort === 'subject' ? 'e.subject' : 'o.start', $direction)
            ->addOrderBy('o.start', $direction);

        return array_values(array_filter(
            $query->getQuery()->getResult(),
            fn(Occurrence $o) => ($from === null || $o->getEndBoundary() > $from) && ($to === null || $o->getStart() < $to),
        ));
    }
}

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
use App\Entity\CalendarRole;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalendarRole>
 */
class CalendarRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarRole::class);
    }

    /**
     * The roles on $calendar that $user belongs to
     *
     * @return CalendarRole[]
     */
    public function findForUser(Calendar $calendar, User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.users', 'u')
            ->where('r.calendar = :calendar AND u = :user')
            ->setParameter('calendar', $calendar)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}

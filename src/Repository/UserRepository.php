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
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * The owners of published events on $calendars that aren't in a category outside $categories, by
     * username: the authors a visitor can filter by, without naming those of events hidden from them
     *
     * @param Calendar[] $calendars
     * @param Category[] $categories
     * @return User[]
     */
    public function findEventAuthors(array $calendars, array $categories): array
    {
        if ($calendars === []) {
            return [];
        }
        $events = $this->getEntityManager()->createQueryBuilder()
            ->select('e.eid')
            ->from(Event::class, 'e')
            ->where('e.owner = u AND e.calendar IN (:calendars)')
            ->andWhere('e.pubtime IS NULL OR e.pubtime <= :now')
            ->andWhere($categories === [] ? 'e.category IS NULL' : 'e.category IS NULL OR e.category IN (:categories)');

        $query = $this->createQueryBuilder('u')
            ->where('EXISTS (' . $events->getDQL() . ')')
            ->setParameter('calendars', $calendars)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('u.username');
        if ($categories !== []) {
            $query->setParameter('categories', $categories);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Finds who's logging in from what they typed: a username, or else an email address. Both compare
     * without regard to case, following the database's collation.
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneBy(['username' => $identifier])
            ?? ($identifier !== '' ? $this->findOneBy(['email' => $identifier]) : null);
    }

    /**
     * For UniqueEntity: users whose username or email is the value being checked, which would make
     * logging in with it ambiguous
     *
     * @param array<string, ?string> $criteria
     * @return User[]
     */
    public function findLoginConflicts(array $criteria): array
    {
        $value = reset($criteria);
        if ($value === null || $value === '') {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->where('u.username = :value OR u.email = :value')
            ->setParameter('value', $value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Saves a password re-hashed with the current algorithm after a successful login
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user: %s', $user::class));
        }
        $user->setHash($newHashedPassword);
        $this->getEntityManager()->flush();
    }
}

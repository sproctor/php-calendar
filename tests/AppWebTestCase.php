<?php

namespace App\Tests;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Entity\UserPermissions;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base class for functional tests. Every test starts with empty tables.
 */
abstract class AppWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $connection = $this->em()->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($connection->fetchFirstColumn('SHOW TABLES') as $table) {
            if ($table !== 'doctrine_migration_versions') {
                $connection->executeStatement("TRUNCATE TABLE `$table`");
            }
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Reloads $entity in the current entity manager. Each request resets the services, so entities
     * loaded before a request are detached afterwards.
     *
     * @template T of object
     * @param T $entity
     * @return T
     */
    protected function fresh(object $entity): object
    {
        $em = $this->em();
        if ($em->contains($entity)) {
            return $entity;
        }
        $class = get_class($entity);
        $id = $em->getClassMetadata($class)->getIdentifierValues($entity);

        return $em->find($class, $id);
    }

    protected function createUser(string $username, bool $admin = false, string $password = 'password'): User
    {
        $user = new User();
        $user->setUsername($username);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setHash($hasher->hashPassword($user, $password));
        $user->setIsAdmin($admin);
        $this->em()->persist($user);
        $this->em()->flush();
        return $user;
    }

    protected function createCalendar(string $title = 'Test calendar'): Calendar
    {
        $calendar = new Calendar();
        $calendar->setTitle($title);
        $this->em()->persist($calendar);
        $this->em()->flush();
        return $calendar;
    }

    protected function login(User $user): void
    {
        $this->client->loginUser($user);
    }

    protected function createEvent(
        Calendar $calendar,
        ?User $owner,
        string $subject = 'Event',
        string $start = '2026-10-15 17:00',
        string $end = '2026-10-15 18:00',
        int $timeType = Occurrence::TIME_NORMAL,
    ): Event {
        $event = new Event($this->fresh($calendar), $owner === null ? null : $this->fresh($owner));
        $event->setSubject($subject);
        new Occurrence($event, new DateTimeImmutable($start), new DateTimeImmutable($end), $timeType);
        $this->em()->persist($event);
        $this->em()->flush();
        return $event;
    }

    protected function grant(Calendar $calendar, User $user, string ...$permissions): UserPermissions
    {
        $row = new UserPermissions($this->fresh($calendar), $this->fresh($user));
        foreach ($permissions as $permission) {
            $row->{'set' . ucfirst($permission)}(true);
        }
        $this->em()->persist($row);
        $this->em()->flush();
        return $row;
    }

    /**
     * Submits the form containing the button with the given label on the current page
     */
    protected function submit(string $button, array $values = []): void
    {
        $this->client->submitForm($button, $values);
    }

    /**
     * Posts to $url with a valid CSRF token for $tokenId, taken from the page at $pageUrl
     */
    protected function postWithToken(string $pageUrl, string $actionUrl, array $data = []): void
    {
        $crawler = $this->client->request('GET', $pageUrl);
        $form = $crawler->filter(sprintf('form[action="%s"]', $actionUrl));
        $this->assertGreaterThan(0, $form->count(), "No form posting to $actionUrl on $pageUrl");
        $token = $form->filter('input[name="_token"]')->attr('value');
        $this->client->request('POST', $actionUrl, $data + ['_token' => $token]);
    }
}

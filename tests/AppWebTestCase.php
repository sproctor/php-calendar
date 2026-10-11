<?php

namespace App\Tests;

use App\Entity\Calendar;
use App\Entity\User;
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
}

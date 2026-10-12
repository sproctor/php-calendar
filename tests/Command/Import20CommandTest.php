<?php

namespace App\Tests\Command;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Entity\UserPermissions;
use App\Tests\AppWebTestCase;
use App\Tests\Import\Legacy20Fixture;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class Import20CommandTest extends AppWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Legacy20Fixture::create($this->em()->getConnection());
    }

    protected function tearDown(): void
    {
        Legacy20Fixture::drop($this->em()->getConnection());
        parent::tearDown();
    }

    private function runImport(array $options = []): CommandTester
    {
        $params = $this->em()->getConnection()->getParams();
        $dsn = sprintf('mysql://%s:%s@%s:%d/%s', $params['user'], $params['password'], $params['host'], $params['port'] ?? 3306, $params['dbname']);
        $tester = new CommandTester((new Application(self::$kernel))->find('app:import-2.0'));
        $tester->execute(['dsn' => $dsn, '--prefix' => 'old_'] + $options);

        return $tester;
    }

    public function testImport(): void
    {
        $tester = $this->runImport();
        $tester->assertCommandIsSuccessful();
        $em = $this->em();
        $em->clear();

        $calendar = $em->getRepository(Calendar::class)->findOneBy(['title' => 'Club']);
        $this->assertSame(Calendar::ANON_NONE, $calendar->getAnonPermission());
        $this->assertSame('America/Chicago', $calendar->getTimezone());
        $this->assertSame(4, $calendar->getEventsMax());

        $boss = $em->getRepository(User::class)->findOneBy(['username' => 'boss']);
        $pat = $em->getRepository(User::class)->findOneBy(['username' => 'pat']);
        $this->assertTrue($boss->isAdmin());
        $this->assertSame('fr', $boss->getLocale());
        $this->assertNull($pat->getTimezone());

        $permissions = $em->getRepository(UserPermissions::class)->findOneBy(['user' => $pat]);
        $this->assertTrue($permissions->canRead() && $permissions->canCreate() && $permissions->canModerate());
        $this->assertFalse($permissions->canUpdate() || $permissions->canAdmin());

        $roles = $em->getRepository(CalendarRole::class)->findAll();
        $this->assertCount(1, $roles);
        $this->assertSame(['pat'], $roles[0]->getUsers()->map(fn(User $u) => $u->getUsername())->toArray());

        $boardOnly = $em->getRepository(Category::class)->findOneBy(['name' => 'Board only']);
        $this->assertSame('Board', $boardOnly->getRole()->getName());
        $this->assertSame('#003366', $boardOnly->getColor());
        $this->assertSame('#ffffff', $em->getRepository(Category::class)->findOneBy(['name' => 'Social'])->getColor());

        $meeting = $em->getRepository(Event::class)->findOneBy(['subject' => 'Meeting']);
        $this->assertTrue($meeting->isReadonly());
        $this->assertSame('pat', $meeting->getOwner()->getUsername());
        $this->assertSame('2020-01-02 03:04:05', $meeting->getCreated()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
        date_default_timezone_set('America/Chicago');
        $this->assertSame('2020-03-01 17:00', $meeting->getOccurrences()->first()->getStart()->format('Y-m-d H:i'));

        $picnic = $em->getRepository(Event::class)->findOneBy(['subject' => 'Picnic']);
        $this->assertNull($picnic->getOwner());
        $this->assertSame(
            [[Occurrence::TIME_FULL_DAY, '2020-06-15', '2020-06-16'], [Occurrence::TIME_TBA, '2020-07-04', '2020-07-04']],
            $picnic->getOccurrences()->map(fn(Occurrence $o) => [
                $o->getTimeType(), $o->getStart()->format('Y-m-d'), $o->getEnd()->format('Y-m-d'),
            ])->toArray(),
        );

        // Events 2.0 left without occurrences aren't imported
        $this->assertNull($em->getRepository(Event::class)->findOneBy(['subject' => 'Orphaned']));
    }

    public function testImportedPasswordsWork(): void
    {
        $this->runImport()->assertCommandIsSuccessful();

        foreach ([['boss', 'bcrypt secret'], ['pat', "it's md5"]] as [$username, $password]) {
            $this->client->request('GET', '/login');
            $this->client->submitForm('Log in', ['_username' => $username, '_password' => $password]);
            $this->assertResponseRedirects();
            $this->assertStringNotContainsString('/login', $this->client->getResponse()->headers->get('Location'), $username);
            $this->client->request('GET', '/logout');
        }

        // The MD5 hash was replaced on login
        $this->em()->clear();
        $pat = $this->em()->getRepository(User::class)->findOneBy(['username' => 'pat']);
        $this->assertStringStartsWith('$', $pat->getPassword());
    }

    public function testDryRunSavesNothing(): void
    {
        $tester = $this->runImport(['--dry-run' => true]);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Dry run', $tester->getDisplay());
        $this->assertSame(0, $this->em()->getRepository(User::class)->count());
    }

    public function testRefusesNonEmptyDatabase(): void
    {
        $this->createUser('existing');
        $this->assertSame(1, $this->runImport()->getStatusCode());
    }
}

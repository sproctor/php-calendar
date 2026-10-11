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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class Import20CommandTest extends AppWebTestCase
{
    /** The 2.0 schema, from install.php's create_tables() */
    private const SCHEMA = [
        'events' => "`eid` int(11) unsigned NOT NULL auto_increment, `cid` int(11) unsigned NOT NULL,
            `owner` int(11) unsigned NOT NULL default 0, `subject` varchar(255) NOT NULL, `description` text NOT NULL,
            `readonly` tinyint(1) NOT NULL default 0, `catid` int(11) unsigned default NULL,
            `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `mtime` timestamp NULL DEFAULT NULL, PRIMARY KEY (`eid`)",
        'occurrences' => "`oid` int(11) unsigned NOT NULL auto_increment, `eid` int(11) unsigned NOT NULL,
            `start_date` date default NULL, `end_date` date default NULL, `start_ts` timestamp NULL default NULL,
            `end_ts` timestamp NULL default NULL, `time_type` tinyint(4) NOT NULL default '0', PRIMARY KEY (`oid`)",
        'users' => "`uid` int(11) unsigned NOT NULL auto_increment, `username` varchar(32) NOT NULL,
            `password` varchar(255) NOT NULL default '', `admin` tinyint(4) NOT NULL DEFAULT '0',
            `password_editable` tinyint(1) NOT NULL DEFAULT '1', `timezone` varchar(255) DEFAULT NULL,
            `language` varchar(255) DEFAULT NULL, `gid` int(11), PRIMARY KEY (`uid`), UNIQUE KEY `username` (`username`)",
        'groups' => "`gid` int(11) NOT NULL AUTO_INCREMENT, `cid` int(11), `name` varchar(255) DEFAULT NULL, PRIMARY KEY (`gid`)",
        'user_groups' => "`gid` int(11), `uid` int(11)",
        'permissions' => "`cid` int(11) unsigned NOT NULL, `uid` int(11) unsigned NOT NULL, `read` tinyint(1) NOT NULL,
            `write` tinyint(1) NOT NULL, `readonly` tinyint(1) NOT NULL, `modify` tinyint(1) NOT NULL,
            `admin` tinyint(1) NOT NULL, UNIQUE KEY `cid` (`cid`,`uid`)",
        'calendars' => "`cid` int(11) unsigned NOT NULL auto_increment, `hours_24` tinyint(1) NOT NULL DEFAULT 0,
            `date_format` tinyint(1) NOT NULL DEFAULT 0, `week_start` tinyint(1) NOT NULL DEFAULT 0,
            `subject_max` smallint(5) unsigned NOT NULL DEFAULT 50, `events_max` tinyint(4) unsigned NOT NULL DEFAULT 8,
            `title` varchar(255) NOT NULL DEFAULT 'PHP-Calendar', `anon_permission` tinyint(1) NOT NULL DEFAULT 1,
            `timezone` varchar(255) DEFAULT NULL, `language` varchar(255) DEFAULT NULL, `theme` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`cid`)",
        'categories' => "`catid` int(11) unsigned NOT NULL auto_increment, `cid` int(11) unsigned NOT NULL,
            `gid` int(11) unsigned DEFAULT NULL, `name` varchar(255) NOT NULL, `text_color` varchar(255) default NULL,
            `bg_color` varchar(255) default NULL, PRIMARY KEY (`catid`)",
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $connection = $this->em()->getConnection();
        $connection->executeStatement("SET time_zone = '+00:00'");
        foreach (self::SCHEMA as $table => $columns) {
            $connection->executeStatement("DROP TABLE IF EXISTS `old_$table`");
            $connection->executeStatement("CREATE TABLE `old_$table` ($columns)");
        }
        $bcrypt = password_hash('bcrypt secret', PASSWORD_BCRYPT);
        // 2.0 hashed MD5 passwords after SQL escaping
        $md5 = md5(addslashes("it's md5"));
        $connection->executeStatement("INSERT INTO old_users (uid, username, password, admin, timezone, language) VALUES
            (1, 'boss', ?, 1, 'Europe/Paris', 'fr'), (2, 'pat', ?, 0, 'Not/AZone', 'de_DE')", [$bcrypt, $md5]);
        $connection->executeStatement("INSERT INTO old_calendars (cid, title, anon_permission, timezone, events_max) VALUES
            (1, 'Club', 0, 'America/Chicago', 4)");
        $connection->executeStatement("INSERT INTO old_permissions VALUES (1, 2, 1, 1, 1, 0, 0)");
        $connection->executeStatement("INSERT INTO old_groups (gid, cid, name) VALUES (1, 1, 'Board'), (2, NULL, 'Orphan')");
        $connection->executeStatement("INSERT INTO old_user_groups VALUES (1, 2), (1, 2), (1, 99)");
        $connection->executeStatement("INSERT INTO old_categories (catid, cid, gid, name, text_color, bg_color) VALUES
            (1, 1, 1, 'Board only', '#FFFFFF', '#003366'), (2, 1, 0, 'Social', NULL, 'red')");
        $connection->executeStatement("INSERT INTO old_events (eid, cid, owner, subject, description, readonly, catid, ctime, mtime) VALUES
            (1, 1, 2, 'Meeting', 'Agenda', 1, 1, '2020-01-02 03:04:05', '2020-02-03 04:05:06'),
            (2, 1, 0, 'Picnic', '', 0, 2, '2020-01-01 00:00:00', NULL),
            (3, 1, 2, 'Orphaned', '', 0, NULL, '2020-01-01 00:00:00', NULL)");
        $connection->executeStatement("INSERT INTO old_occurrences (eid, start_date, end_date, start_ts, end_ts, time_type) VALUES
            (1, NULL, NULL, '2020-03-01 23:00:00', '2020-03-02 01:00:00', 0),
            (2, '2020-06-15', '2020-06-16', NULL, NULL, 1),
            (2, '2020-07-04', '2020-07-04', NULL, NULL, 2)");
    }

    protected function tearDown(): void
    {
        $connection = $this->em()->getConnection();
        foreach (array_keys(self::SCHEMA) as $table) {
            $connection->executeStatement("DROP TABLE IF EXISTS `old_$table`");
        }
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

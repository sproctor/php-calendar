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

namespace App\Command;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Entity\UserPermissions;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Copies a PHP-Calendar 2.0 database into this one: users (keeping their passwords), calendars,
 * per-user permissions, groups (as roles), categories, events and occurrences.
 */
#[AsCommand(
    name: 'app:import-2.0',
    description: 'Import the calendars, users and events of a PHP-Calendar 2.0 database',
)]
class Import20Command extends Command
{
    private Connection $source;
    private string $prefix;

    /** @var array<int, User> 2.0 uid => user */
    private array $users = [];
    /** @var array<int, Calendar> 2.0 cid => calendar */
    private array $calendars = [];
    /** @var array<int, CalendarRole> 2.0 gid => role */
    private array $roles = [];
    /** @var array<int, Category> 2.0 catid => category */
    private array $categories = [];
    /** @var array<int, Event> 2.0 eid => event */
    private array $events = [];
    private int $occurrences = 0;
    /** @var string[] */
    private array $warnings = [];

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('dsn', InputArgument::REQUIRED, 'The 2.0 database, e.g. mysql://user:password@localhost/calendar')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'The 2.0 table prefix (SQL_PREFIX in its config.php)', 'phpc_')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Read and convert everything, then roll back')
            ->setHelp(<<<'HELP'
                Imports a PHP-Calendar 2.0 database into this, empty, 3.0 database. The 2.0 database isn't changed.

                  <info>php bin/console app:import-2.0 "mysql://user:password@localhost/oldcalendar" --prefix=phpc_</info>

                Passwords keep working: 2.0's bcrypt hashes are used as they are, and old MD5 hashes are
                re-hashed the next time each user logs in. 2.0 groups become roles. Calendar settings that 3.0
                doesn't have (date format, 24-hour time, week start, language, theme) aren't imported; dates are
                formatted for each visitor's locale instead.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->prefix = $input->getOption('prefix');
        if (!preg_match('/^\w*$/', $this->prefix)) {
            $io->error('The prefix may only contain letters, digits and underscores.');

            return Command::INVALID;
        }

        $parser = new DsnParser(['mysql' => 'pdo_mysql', 'mariadb' => 'pdo_mysql']);
        $this->source = DriverManager::getConnection($parser->parse($input->getArgument('dsn')));
        $this->source->executeStatement("SET time_zone = '+00:00'");

        foreach (['users', 'calendars', 'events', 'occurrences', 'permissions', 'categories', 'groups', 'user_groups'] as $table) {
            if (!$this->source->createSchemaManager()->tablesExist([$this->table($table)])) {
                $io->error("Table {$this->table($table)} not found. Is this a PHP-Calendar 2.0 database, and is the prefix right?");

                return Command::FAILURE;
            }
        }
        $target = $this->entityManager->getConnection();
        if ((int) $target->fetchOne('SELECT COUNT(*) FROM users') > 0 || (int) $target->fetchOne('SELECT COUNT(*) FROM calendars') > 0) {
            $io->error('This database already has users or calendars. Import into a new, empty 3.0 database.');

            return Command::FAILURE;
        }

        $target->beginTransaction();
        try {
            $this->importUsers();
            $this->importCalendars();
            $this->importPermissions();
            $this->importGroups();
            $this->importCategories();
            $this->entityManager->flush();
            $this->importEvents();
            $this->entityManager->flush();
            if ($input->getOption('dry-run')) {
                $target->rollBack();
            } else {
                $target->commit();
            }
        } catch (\Throwable $e) {
            $target->rollBack();
            throw $e;
        }

        foreach ($this->warnings as $warning) {
            $io->warning($warning);
        }
        $io->table(['Imported', 'Count'], [
            ['Users', count($this->users)],
            ['Calendars', count($this->calendars)],
            ['Roles (2.0 groups)', count($this->roles)],
            ['Categories', count($this->categories)],
            ['Events', count($this->events)],
            ['Occurrences', $this->occurrences],
        ]);
        $io->success($input->getOption('dry-run') ? 'Dry run finished; nothing was saved.' : 'Import finished.');

        return Command::SUCCESS;
    }

    private function table(string $name): string
    {
        return $this->prefix . $name;
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function rows(string $sql): iterable
    {
        return $this->source->iterateAssociative(str_replace('{prefix}', $this->prefix, $sql));
    }

    private function importUsers(): void
    {
        foreach ($this->rows('SELECT * FROM `{prefix}users` ORDER BY uid') as $row) {
            if ($row['password'] === '') {
                $this->warnings[] = "User {$row['username']} has no password and can't log in until an admin sets one.";
            }
            $user = new User();
            $user->setUsername($row['username']);
            $user->setHash($row['password']);
            $user->setIsAdmin((bool) $row['admin']);
            $user->setPasswordIsEditable((bool) $row['password_editable']);
            $user->setTimezone(self::validTimezone($row['timezone']));
            $user->setLocale(self::validLocale($row['language']));
            $this->entityManager->persist($user);
            $this->users[(int) $row['uid']] = $user;
        }
    }

    private function importCalendars(): void
    {
        foreach ($this->rows('SELECT * FROM `{prefix}calendars` ORDER BY cid') as $row) {
            $calendar = new Calendar();
            $calendar->setTitle($row['title'] !== '' ? $row['title'] : 'PHP-Calendar');
            $calendar->setSubjectMax(max(1, min(255, (int) $row['subject_max'])));
            $calendar->setEventsMax(max(1, min(255, (int) $row['events_max'])));
            $calendar->setAnonPermission(max(Calendar::ANON_NONE, min(Calendar::ANON_MODIFY, (int) $row['anon_permission'])));
            $calendar->setTimezone(self::validTimezone($row['timezone']) ?? 'UTC');
            $this->entityManager->persist($calendar);
            $this->calendars[(int) $row['cid']] = $calendar;
        }
    }

    private function importPermissions(): void
    {
        foreach ($this->rows('SELECT * FROM `{prefix}permissions`') as $row) {
            $calendar = $this->calendars[(int) $row['cid']] ?? null;
            $user = $this->users[(int) $row['uid']] ?? null;
            if ($calendar === null || $user === null) {
                continue;
            }
            $permissions = (new UserPermissions($calendar, $user))
                ->setRead((bool) $row['read'])
                ->setCreate((bool) $row['write'])
                ->setUpdate((bool) $row['modify'])
                ->setModerate((bool) $row['readonly'])
                ->setAdmin((bool) $row['admin']);
            $this->entityManager->persist($permissions);
        }
    }

    private function importGroups(): void
    {
        foreach ($this->rows('SELECT * FROM `{prefix}groups` ORDER BY gid') as $row) {
            $calendar = $this->calendars[(int) $row['cid']] ?? null;
            if ($calendar === null) {
                $this->warnings[] = "Skipped group \"{$row['name']}\", which doesn't belong to a calendar.";
                continue;
            }
            $role = (new CalendarRole($calendar))->setName(($row['name'] ?? '') !== '' ? $row['name'] : 'Group ' . $row['gid']);
            $this->entityManager->persist($role);
            $this->roles[(int) $row['gid']] = $role;
        }
        foreach ($this->rows('SELECT DISTINCT gid, uid FROM `{prefix}user_groups`') as $row) {
            $role = $this->roles[(int) $row['gid']] ?? null;
            $user = $this->users[(int) $row['uid']] ?? null;
            if ($role !== null && $user !== null) {
                $role->addUser($user);
            }
        }
    }

    private function importCategories(): void
    {
        foreach ($this->rows('SELECT * FROM `{prefix}categories` ORDER BY catid') as $row) {
            $calendar = $this->calendars[(int) $row['cid']] ?? null;
            if ($calendar === null) {
                $this->warnings[] = "Skipped category \"{$row['name']}\", which doesn't belong to a calendar.";
                continue;
            }
            $color = $row['bg_color'];
            if (!is_string($color) || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $color = '#ffffff';
            }
            $category = (new Category($calendar))->setName($row['name'])->setColor(strtolower($color));
            $role = $this->roles[(int) $row['gid']] ?? null;
            if ($role !== null && $role->getCalendar() === $calendar) {
                $category->setRole($role);
            }
            $this->entityManager->persist($category);
            $this->categories[(int) $row['catid']] = $category;
        }
    }

    private function importEvents(): void
    {
        $utc = new DateTimeZone('UTC');
        $events = $this->rows(
            'SELECT eid, cid, owner, subject, description, readonly, catid,'
            . ' UNIX_TIMESTAMP(ctime) AS ctime, UNIX_TIMESTAMP(mtime) AS mtime FROM `{prefix}events` ORDER BY eid',
        );
        foreach ($events as $row) {
            $calendar = $this->calendars[(int) $row['cid']] ?? null;
            if ($calendar === null) {
                continue;
            }
            $event = new Event($calendar, $this->users[(int) $row['owner']] ?? null);
            $event->setSubject(mb_substr($row['subject'], 0, 255));
            $event->setDescription($row['description']);
            $event->setReadonly((bool) $row['readonly']);
            $category = $this->categories[(int) $row['catid']] ?? null;
            if ($category !== null && $category->getCalendar() === $calendar) {
                $event->setCategory($category);
            }
            $event->setTimestamps(
                $row['ctime'] ? new DateTimeImmutable('@' . $row['ctime']) : new DateTimeImmutable(),
                $row['mtime'] ? new DateTimeImmutable('@' . $row['mtime']) : null,
            );
            $this->entityManager->persist($event);
            $this->events[(int) $row['eid']] = $event;
        }

        $occurrences = $this->rows(
            'SELECT oid, eid, time_type, start_date, end_date,'
            . ' UNIX_TIMESTAMP(start_ts) AS start_ts, UNIX_TIMESTAMP(end_ts) AS end_ts FROM `{prefix}occurrences` ORDER BY oid',
        );
        foreach ($occurrences as $row) {
            $event = $this->events[(int) $row['eid']] ?? null;
            if ($event === null) {
                continue;
            }
            $timeType = (int) $row['time_type'];
            if ($timeType === Occurrence::TIME_NORMAL && $row['start_ts'] !== null) {
                $start = new DateTimeImmutable('@' . $row['start_ts']);
                $end = $row['end_ts'] !== null ? new DateTimeImmutable('@' . $row['end_ts']) : $start;
            } elseif ($row['start_date'] !== null) {
                $timeType = in_array($timeType, [Occurrence::TIME_FULL_DAY, Occurrence::TIME_TBA, Occurrence::TIME_NONE], true)
                    ? $timeType
                    : Occurrence::TIME_FULL_DAY;
                $start = new DateTimeImmutable($row['start_date'], $utc);
                $end = $row['end_date'] !== null ? new DateTimeImmutable($row['end_date'], $utc) : $start;
            } else {
                $this->warnings[] = "Skipped occurrence {$row['oid']} of \"{$event->getSubject()}\", which has no date.";
                continue;
            }
            new Occurrence($event, $start, max($start, $end), $timeType);
            $this->occurrences++;
        }

        foreach ($this->events as $eid => $event) {
            if ($event->getOccurrences()->isEmpty()) {
                // 2.0 left events behind when their last occurrence was deleted; they can't be seen
                $this->entityManager->remove($event);
                unset($this->events[$eid]);
            }
        }
    }

    private static function validTimezone(?string $timezone): ?string
    {
        return $timezone !== null && in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : null;
    }

    private static function validLocale(?string $language): ?string
    {
        return $language !== null && preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $language) ? $language : null;
    }
}

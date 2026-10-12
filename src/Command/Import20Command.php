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

use App\Import\Import20;
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
 * Imports a PHP-Calendar 2.0 database from the command line; see Import20
 */
#[AsCommand(
    name: 'app:import-2.0',
    description: 'Import the calendars, users and events of a PHP-Calendar 2.0 database',
)]
class Import20Command extends Command
{
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
        $prefix = $input->getOption('prefix');
        if (!preg_match('/^\w*$/', $prefix)) {
            $io->error('The prefix may only contain letters, digits and underscores.');

            return Command::INVALID;
        }

        $parser = new DsnParser(['mysql' => 'pdo_mysql', 'mariadb' => 'pdo_mysql']);
        $import = new Import20($this->entityManager, DriverManager::getConnection($parser->parse($input->getArgument('dsn'))), $prefix);

        $missing = $import->missingTables();
        if ($missing !== []) {
            $io->error('Table ' . $missing[0] . ' not found. Is this a PHP-Calendar 2.0 database, and is the prefix right?');

            return Command::FAILURE;
        }
        if ($import->targetHasData()) {
            $io->error('This database already has users or calendars. Import into a new, empty 3.0 database.');

            return Command::FAILURE;
        }

        $counts = $import->run((bool) $input->getOption('dry-run'));

        foreach ($import->getWarnings() as $warning) {
            $io->warning($warning);
        }
        $io->table(['Imported', 'Count'], [
            ['Users', $counts['users']],
            ['Calendars', $counts['calendars']],
            ['Roles (2.0 groups)', $counts['roles']],
            ['Categories', $counts['categories']],
            ['Events', $counts['events']],
            ['Occurrences', $counts['occurrences']],
        ]);
        $io->success($input->getOption('dry-run') ? 'Dry run finished; nothing was saved.' : 'Import finished.');

        return Command::SUCCESS;
    }
}

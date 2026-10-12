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

namespace App\Install;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * What the web installer needs to know: whether the app has a database configured, whether the schema
 * is up to date, and how to write the configuration and run migrations.
 */
class InstallState
{
    public function __construct(
        #[Autowire('%app.local_env_file%')]
        private string $localEnvFile,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        #[Autowire(service: 'doctrine.migrations.dependency_factory')]
        private DependencyFactory $migrations,
        #[Autowire(service: 'cache.app')]
        private CacheInterface $cache,
        private KernelInterface $kernel,
    ) {
    }

    /**
     * Whether a database connection is configured, by .env.local.php or the environment
     */
    public function isConfigured(): bool
    {
        $url = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

        return is_string($url) && $url !== '';
    }

    /**
     * Checks for the things the installer needs, as label => problem (null when it's fine)
     *
     * @return array<string, string|null>
     */
    public function requirements(): array
    {
        $configDir = dirname($this->localEnvFile);
        // var/ is created on first use when it doesn't exist
        $varDir = is_dir($this->projectDir . '/var') ? $this->projectDir . '/var' : $this->projectDir;

        return [
            'requirement-config-writable' => is_writable($configDir) ? null : $configDir,
            'requirement-var-writable' => is_writable($varDir) ? null : $varDir,
        ];
    }

    /**
     * Saves the production configuration in .env.local.php, which Symfony loads instead of the .env files.
     * Refuses to replace an existing file, so the installer can't be used to change a working setup.
     */
    public function writeConfig(string $databaseUrl, string $defaultUri): void
    {
        if (file_exists($this->localEnvFile)) {
            throw new \RuntimeException('The configuration file already exists: ' . $this->localEnvFile);
        }
        $env = [
            'APP_ENV' => 'prod',
            'APP_SECRET' => bin2hex(random_bytes(32)),
            'APP_SHARE_DIR' => 'var/share',
            'DATABASE_URL' => $databaseUrl,
            'DEFAULT_URI' => $defaultUri,
            'MAILER_DSN' => 'null://null',
            'MESSENGER_TRANSPORT_DSN' => 'doctrine://default?auto_setup=0',
        ];
        $content = "<?php\n\n// Written by the PHP-Calendar installer. Keep this file private.\n\nreturn "
            . var_export($env, true) . ";\n";

        $temporary = $this->localEnvFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($temporary, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write ' . $this->localEnvFile);
        }
        chmod($temporary, 0640);
        rename($temporary, $this->localEnvFile);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->localEnvFile, true);
        }
    }

    /**
     * Whether the database is missing migrations. Once it's up to date, that's remembered until the
     * set of migration files changes, so normal requests don't query the database for it.
     */
    public function hasPendingMigrations(): bool
    {
        $files = glob($this->projectDir . '/migrations/*.php') ?: [];
        $key = 'migrations_current_' . md5(implode('|', array_map('basename', $files)));

        return !$this->cache->get($key, function (ItemInterface $item): bool {
            $current = $this->countPendingMigrations() === 0;
            if (!$current) {
                // Check again on the next request
                $item->expiresAfter(0);
            }

            return $current;
        });
    }

    private function countPendingMigrations(): int
    {
        try {
            return count($this->migrations->getMigrationStatusCalculator()->getNewMigrations());
        } catch (DBALException) {
            return PHP_INT_MAX;
        }
    }

    /**
     * Brings the database schema up to date
     *
     * @return string the migration output
     */
    public function migrate(): string
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $status = $application->run(new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
            '--allow-no-migration' => true,
        ]), $output);
        if ($status !== 0) {
            throw new \RuntimeException("Updating the database failed:\n" . $output->fetch());
        }

        return $output->fetch();
    }
}

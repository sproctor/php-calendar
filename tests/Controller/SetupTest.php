<?php

namespace App\Tests\Controller;

use App\Install\InstallState;
use App\Tests\AppWebTestCase;
use App\Tests\Import\Legacy20Fixture;
use Doctrine\DBAL\DriverManager;

class SetupTest extends AppWebTestCase
{
    private ?string $databaseUrl = null;
    private string $configFile;
    private bool $legacyTables = false;
    private ?array $connectionParams = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configFile = static::getContainer()->getParameter('app.local_env_file');
        @unlink($this->configFile);
        @mkdir(dirname($this->configFile), 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->databaseUrl !== null) {
            $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $this->databaseUrl;
            putenv('DATABASE_URL=' . $this->databaseUrl);
        }
        @unlink($this->configFile);
        try {
            if ($this->legacyTables) {
                // The app's own connection has no database when a test unconfigured it
                $connection = $this->connectionParams !== null
                    ? DriverManager::getConnection($this->connectionParams)
                    : $this->em()->getConnection();
                Legacy20Fixture::drop($connection);
            }
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Makes the app behave as if no database were configured yet
     */
    private function unconfigure(): void
    {
        // The test database's (with Doctrine's _test suffix), for cleaning up afterwards
        $this->connectionParams = $this->em()->getConnection()->getParams();
        $this->databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = '';
        putenv('DATABASE_URL=');
    }

    public function testDatabaseStep(): void
    {
        $this->unconfigure();
        $this->client->request('GET', '/en/calendar/anything/');
        $this->assertResponseRedirects('/setup');

        $this->client->request('GET', '/setup');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="database_form[host]"]');

        $values = [
            'database_form[host]' => 'database',
            'database_form[port]' => '3306',
            'database_form[name]' => 'app_test',
            'database_form[user]' => 'app',
            'database_form[password]' => 'wrong password',
        ];
        $this->submit('Connect', $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.alert-danger, .invalid-feedback', "Couldn't connect to the database");
        $this->assertFileDoesNotExist($this->configFile);

        $this->submit('Connect', ['database_form[password]' => 'app'] + $values);
        $this->assertResponseRedirects('/setup');
        $config = include $this->configFile;
        $this->assertSame('prod', $config['APP_ENV']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $config['APP_SECRET']);
        $this->assertMatchesRegularExpression(
            '#^mysql://app:app@database:3306/app_test\?serverVersion=\d+\.\d+\.\d+&charset=utf8mb4$#',
            $config['DATABASE_URL'],
        );
        $this->assertSame('http://localhost', $config['DEFAULT_URI']);
        $this->assertSame('phpc3_', $config['TABLE_PREFIX']);
    }

    public function testTablePrefix(): void
    {
        // A 2.0 installation in the same database, made while the app can still reach it
        Legacy20Fixture::create($this->em()->getConnection());
        $this->legacyTables = true;
        $this->unconfigure();
        $values = [
            'database_form[host]' => 'database',
            'database_form[port]' => '3306',
            'database_form[name]' => 'app_test',
            'database_form[user]' => 'app',
            'database_form[password]' => 'app',
        ];
        $this->client->request('GET', '/setup');
        $this->assertSelectorExists('input[name="database_form[prefix]"][value="phpc3_"]');

        $this->submit('Connect', ['database_form[prefix]' => 'Bad-Prefix'] + $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'lowercase letters');

        // Not one the 2.0 installation uses
        $this->submit('Connect', ['database_form[prefix]' => 'old_'] + $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'PHP-Calendar 2.0 already uses tables starting with "old_"');
        $this->assertFileDoesNotExist($this->configFile);

        // Or none at all
        $this->submit('Connect', ['database_form[prefix]' => ''] + $values);
        $this->assertResponseRedirects('/setup');
        $this->assertSame('', (include $this->configFile)['TABLE_PREFIX']);
    }

    public function testFirstCalendarAndAdmin(): void
    {
        $this->client->request('GET', '/setup');
        $this->assertSelectorNotExists('form[action="/setup/import"]');
        $this->submit('Save', [
            'form[calendar][title]' => 'Club Events',
            'form[user][username]' => 'founder',
            'form[user][password][first]' => 'correct horse',
            'form[user][password][second]' => 'correct horse',
        ]);
        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertResponseRedirects('/en/calendar/club-events/');

        // Setup closes once an account exists
        $this->client->request('GET', '/setup');
        $this->assertResponseRedirects('/');
    }

    public function testImportFrom20(): void
    {
        Legacy20Fixture::create($this->em()->getConnection());
        $this->legacyTables = true;
        $crawler = $this->client->request('GET', '/setup');
        $this->assertSelectorExists('form[action="/setup/import"] input[name="prefix"][value="old_"]');

        $this->client->submit($crawler->filter('form[action="/setup/import"]')->form());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main', 'Users: 2');
        $this->assertSelectorTextContains('main', 'Calendars: 1');

        $this->client->request('GET', '/setup');
        $this->assertResponseRedirects('/');
    }

    public function testMaintenanceWhileMigrationsArePending(): void
    {
        $admin = $this->createUser('admin', admin: true);
        $calendar = $this->createCalendar();
        $state = $this->createStub(InstallState::class);
        $state->method('isConfigured')->willReturn(true);
        $state->method('hasPendingMigrations')->willReturn(true);
        $state->method('migrate')->willReturn('');
        static::getContainer()->set(InstallState::class, $state);
        $this->client->disableReboot();

        $this->client->request('GET', "/en/calendar/{$calendar->getSlug()}/");
        $this->assertResponseStatusCodeSame(503);
        $this->assertSelectorTextContains('h1', 'Down for maintenance');

        // Visitors are sent to log in; a site admin gets the update button
        $this->client->request('GET', '/setup');
        $this->assertResponseRedirects('/login?target_path=/setup');
        $this->login($admin);
        $this->client->request('GET', '/setup');
        $this->assertSelectorExists('form[action="/setup/update"]');
        $this->submit('Update the database');
        $this->assertResponseRedirects('/');
    }
}

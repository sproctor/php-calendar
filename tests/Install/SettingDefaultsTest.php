<?php

namespace App\Tests\Install;

use App\Doctrine\TablePrefixListener;
use App\Entity\Calendar;
use App\Mail\Mailing;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;

/**
 * Sites keep the .env.local.php their installer wrote, and Symfony reads it instead of .env, so settings
 * added since the first installer must have defaults (config/services.yaml).
 */
class SettingDefaultsTest extends KernelTestCase
{
    /**
     * What the first installer (Install from a release zip, before 3.0.0-alpha.1) wrote
     */
    private const FIRST_INSTALLER_SETTINGS = ['APP_ENV', 'APP_SECRET', 'APP_SHARE_DIR', 'DATABASE_URL', 'DEFAULT_URI',
        'MAILER_DSN'];

    /** @var array<string, array{mixed, mixed, string|false}> */
    private array $removed = [];

    protected function tearDown(): void
    {
        foreach ($this->removed as $name => [$server, $env, $getenv]) {
            if ($server !== null) {
                $_SERVER[$name] = $server;
            }
            if ($env !== null) {
                $_ENV[$name] = $env;
            }
            if ($getenv !== false) {
                putenv("$name=$getenv");
            }
        }
        $this->removed = [];
        parent::tearDown();
    }

    private function removeSetting(string $name): void
    {
        $this->removed[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);
    }

    public function testNewerSettingsHaveDefaults(): void
    {
        $container = static::getContainer();
        $files = Finder::create()->files()->in([__DIR__ . '/../../config', __DIR__ . '/../../src'])
            ->name(['*.yaml', '*.php'])->notName('reference.php');
        $names = [];
        foreach ($files as $file) {
            // %env(NAME)% or %env(processor:NAME)%; the default:: processor brings its own default
            preg_match_all('/%env\((?!.*default::)(?:[\w-]+:)*(\w+)\)%/', $file->getContents(), $matches);
            $names = array_merge($names, $matches[1]);
        }
        // Development only (config/packages/debug.yaml)
        $names = array_diff(array_unique($names), self::FIRST_INSTALLER_SETTINGS, ['VAR_DUMPER_SERVER']);

        $this->assertContains('TABLE_PREFIX', $names);
        foreach ($names as $name) {
            $this->assertTrue($container->hasParameter("env($name)"), "$name needs a default in config/services.yaml");
        }
    }

    public function testSiteInstalledByTheFirstInstallerStillWorks(): void
    {
        $this->removeSetting('MAILER_FROM');
        $this->removeSetting('TABLE_PREFIX');
        $container = static::getContainer();

        $this->assertSame('', $container->get(Mailing::class)->getFrom());

        $metadata = new ClassMetadata(Calendar::class);
        $metadata->setPrimaryTable(['name' => 'calendar']);
        $container->get(TablePrefixListener::class)->loadClassMetadata(
            new LoadClassMetadataEventArgs($metadata, $container->get(EntityManagerInterface::class)));
        $this->assertSame('calendar', $metadata->getTableName());
    }
}

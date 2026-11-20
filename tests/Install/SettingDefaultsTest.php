<?php

namespace App\Tests\Install;

use App\Install\SettingDefaults;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Sites keep the .env.local.php their installer wrote, and Symfony reads it instead of .env, so settings
 * added since then come from .env (SettingDefaults).
 */
class SettingDefaultsTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/../..';

    /**
     * What the first installer (Install from a release zip, before 3.0.0-alpha.1) wrote
     */
    private const FIRST_INSTALLER_SETTINGS = ['APP_ENV', 'APP_SECRET', 'APP_SHARE_DIR', 'DATABASE_URL', 'DEFAULT_URI',
        'MAILER_DSN'];

    private string $dir;
    /** @var array<string, mixed> */
    private array $server;
    /** @var array<string, mixed> */
    private array $env;
    /** @var array<string, string|false> */
    private array $putenv = [];

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->env = $_ENV;
        $this->dir = sys_get_temp_dir() . '/phpc-settings-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        copy(self::PROJECT_DIR . '/.env', "$this->dir/.env");
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_ENV = $this->env;
        foreach ($this->putenv as $name => $value) {
            putenv($value === false ? $name : "$name=$value");
        }
        (new Filesystem())->remove($this->dir);
    }

    /**
     * @return array<string, string>
     */
    private static function dotenvDefaults(): array
    {
        return (new Dotenv())->parse((string) file_get_contents(self::PROJECT_DIR . '/.env'));
    }

    private function setSetting(string $name, ?string $value): void
    {
        $this->putenv[$name] ??= getenv($name);
        putenv($name);
        unset($_SERVER[$name], $_ENV[$name]);
        if ($value !== null) {
            $_SERVER[$name] = $_ENV[$name] = $value;
        }
    }

    public function testFillsInSettingsAnOlderInstallerDidNotWrite(): void
    {
        file_put_contents("$this->dir/.env.local.php", "<?php\n\nreturn [];\n");
        $defaults = self::dotenvDefaults();
        $added = array_diff(array_keys($defaults), self::FIRST_INSTALLER_SETTINGS);
        $this->assertContains('TABLE_PREFIX', $added);
        foreach (self::FIRST_INSTALLER_SETTINGS as $name) {
            $this->setSetting($name, "installed $name");
        }
        foreach ($added as $name) {
            $this->setSetting($name, null);
        }

        SettingDefaults::apply($this->dir);

        foreach (self::FIRST_INSTALLER_SETTINGS as $name) {
            $this->assertSame("installed $name", $_SERVER[$name], "$name keeps the installed value");
        }
        foreach ($added as $name) {
            $this->assertSame($defaults[$name], $_SERVER[$name], "$name gets its default");
            $this->assertSame($defaults[$name], $_ENV[$name]);
        }
    }

    public function testDoesNothingWithoutAConfigurationFile(): void
    {
        $this->setSetting('TABLE_PREFIX', null);
        SettingDefaults::apply($this->dir);
        $this->assertArrayNotHasKey('TABLE_PREFIX', $_SERVER);
    }

    /**
     * Every setting the app reads, in %env()% placeholders or from $_SERVER, $_ENV or getenv(), has a
     * default in .env
     */
    public function testEverySettingHasADefault(): void
    {
        $sources = [];
        foreach (Finder::create()->files()->in(self::PROJECT_DIR . '/config')->name('*.yaml') as $file) {
            // Development and test configuration doesn't run on installed sites
            $config = Yaml::parseFile($file->getPathname(), Yaml::PARSE_CUSTOM_TAGS) ?? [];
            unset($config['when@dev'], $config['when@test']);
            $sources[$file->getRelativePathname()] = var_export($config, true);
        }
        $php = Finder::create()->files()->in([self::PROJECT_DIR . '/config', self::PROJECT_DIR . '/src'])
            ->name('*.php')->notName('reference.php');
        foreach ($php as $file) {
            $sources[$file->getRelativePathname()] = $file->getContents();
        }

        $names = [];
        foreach ($sources as $source) {
            $names = array_merge($names, self::settingNames($source));
        }
        $names = array_unique($names);

        $this->assertContains('TABLE_PREFIX', $names);
        $this->assertContains('APP_SECRET', $names);
        $this->assertNotContains('VAR_DUMPER_SERVER', $names);
        $this->assertEmpty(array_diff($names, array_keys(self::dotenvDefaults())), 'Settings missing from .env');
    }

    public function testSettingNames(): void
    {
        $this->assertSame(['NEW_HOST', 'MAX', 'PREFIX', 'URL', 'FROM'], self::settingNames(
            "a: '%env(NEW_HOST)%/%env(default::SUFFIX)%'\nb: '%env(default:app.from:FROM_DEFAULTED)%'\n"
            . "c: '%env(int:MAX)%'\n\$_SERVER['PREFIX'] ?? \$_ENV['URL'] ?? getenv('FROM')"));
    }

    /**
     * The settings $source reads: %env()% placeholders, except default::NAME and default:parameter:NAME,
     * which bring their own default, and $_SERVER, $_ENV and getenv() with a literal name
     *
     * @return list<string>
     */
    private static function settingNames(string $source): array
    {
        $names = [];
        preg_match_all('/%env\(([\w:.-]+)\)%/', $source, $matches);
        foreach ($matches[1] as $placeholder) {
            $parts = explode(':', $placeholder);
            $name = array_pop($parts);
            if (!in_array('default', $parts, true)) {
                $names[] = $name;
            }
        }
        preg_match_all('/(?:\$_SERVER\[|\$_ENV\[|getenv\()\s*[\'"]([A-Z][A-Z0-9_]*)[\'"]/', $source, $matches);

        return array_merge($names, $matches[1]);
    }
}

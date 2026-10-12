<?php

namespace App\Tests\Theme;

use App\Entity\Calendar;
use App\Repository\SettingRepository;
use App\Theme\Theme;
use App\Theme\ThemeRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;

class ThemeRegistryTest extends KernelTestCase
{
    private string $public;

    protected function setUp(): void
    {
        // The real Bootstrap and Bootswatch files, plus a themes folder of our own
        $real = dirname(__DIR__, 2) . '/public';
        $this->public = sys_get_temp_dir() . '/phpc-themes-' . bin2hex(random_bytes(4));
        $fs = new Filesystem();
        $fs->mkdir("$this->public/static");
        $fs->symlink("$real/static/lib", "$this->public/static/lib");
        $fs->dumpFile("$this->public/themes/club.css", '@import url("../static/lib/bootstrap/bootstrap.min.css");');
        $fs->dumpFile("$this->public/themes/club.json", '{"name": "Club colors"}');
        $fs->dumpFile("$this->public/themes/night/bootstrap.min.css", ':root,[data-bs-theme=light]{--bs-blue:#0d6efd;--bs-body-bg:#111;}');
        $fs->dumpFile("$this->public/themes/night/bootstrap.rtl.min.css", ':root{--bs-body-bg:#111}');
        $fs->dumpFile("$this->public/themes/bad name.css", '');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->public);
    }

    private function registry(?string $siteDefault = null): ThemeRegistry
    {
        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getValue')->willReturn($siteDefault);

        return new ThemeRegistry($this->public, $settings, new ArrayAdapter());
    }

    public function testListsBootstrapBootswatchAndCustomThemes(): void
    {
        $themes = $this->registry()->all();
        $this->assertSame(ThemeRegistry::DEFAULT, array_key_first($themes));
        $this->assertSame('Flatly', $themes['bootswatch:flatly']->name);
        $this->assertSame(Theme::GROUP_BOOTSWATCH, $themes['bootswatch:flatly']->group);
        $this->assertSame('static/lib/bootswatch/flatly/bootstrap.rtl.min.css', $themes['bootswatch:flatly']->stylesheetFor(true));
        $this->assertSame('Club colors', $themes['custom:club']->name);
        $this->assertSame('themes/club.css', $themes['custom:club']->stylesheetFor(true));
        $this->assertSame('themes/night/bootstrap.rtl.min.css', $themes['custom:night']->stylesheetFor(true));
        $this->assertArrayNotHasKey('custom:bad name', $themes);
    }

    public function testDarkDesignedThemes(): void
    {
        $dark = array_keys(array_filter($this->registry()->all(), fn(Theme $theme) => $theme->darkOnly));
        $this->assertEqualsCanonicalizing([
            'bootswatch:cyborg', 'bootswatch:darkly', 'bootswatch:quartz', 'bootswatch:slate', 'bootswatch:solar',
            'bootswatch:superhero', 'bootswatch:vapor', 'custom:night',
        ], $dark);
    }

    public function testCalendarThemeThenSiteDefaultThenBootstrap(): void
    {
        $calendar = (new Calendar())->setTheme('bootswatch:minty');
        $this->assertSame('bootswatch:minty', $this->registry('bootswatch:lux')->forCalendar($calendar)->id);
        $this->assertSame('bootswatch:lux', $this->registry('bootswatch:lux')->forCalendar(new Calendar())->id);
        $this->assertSame('bootswatch:lux', $this->registry('bootswatch:lux')->forCalendar(null)->id);
        $this->assertSame(ThemeRegistry::DEFAULT, $this->registry()->forCalendar(null)->id);
        // A theme that was removed
        $this->assertSame(ThemeRegistry::DEFAULT, $this->registry('custom:gone')->forCalendar($calendar->setTheme('custom:gone'))->id);
    }
}

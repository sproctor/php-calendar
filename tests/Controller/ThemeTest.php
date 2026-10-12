<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Repository\SettingRepository;
use App\Tests\AppWebTestCase;

class ThemeTest extends AppWebTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar('Club');
    }

    private function stylesheet(): string
    {
        return $this->client->getCrawler()->filter('link#phpc-theme')->attr('href');
    }

    public function testBootstrapByDefault(): void
    {
        $this->client->request('GET', '/en/calendar/club/');
        $this->assertStringContainsString('static/lib/bootstrap/bootstrap.min.css', $this->stylesheet());
        $this->assertSelectorExists('.phpc-color-mode');
    }

    public function testCalendarAdminChoosesATheme(): void
    {
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', '/en/calendar/club/admin');
        $this->assertSelectorExists('select[name="calendar_form[theme]"] optgroup[label="Bootswatch"] option[value="bootswatch:flatly"]');
        $this->assertSelectorTextContains('select[name="calendar_form[theme]"] option[value=""]', 'Site default (Bootstrap)');
        $this->submit('Save', ['calendar_form[theme]' => 'bootswatch:flatly']);
        $this->assertSame('bootswatch:flatly', $this->fresh($this->calendar)->getTheme());

        $this->client->request('GET', '/en/calendar/club/');
        $this->assertStringContainsString('static/lib/bootswatch/flatly/bootstrap.min.css', $this->stylesheet());
        // Right to left
        $this->client->request('GET', '/he/calendar/club/');
        $this->assertStringContainsString('static/lib/bootswatch/flatly/bootstrap.rtl.min.css', $this->stylesheet());
    }

    public function testDarkThemesAreAlwaysDark(): void
    {
        $this->calendar->setTheme('bootswatch:darkly');
        $this->em()->flush();

        $this->client->request('GET', '/en/calendar/club/', server: ['HTTP_COOKIE' => 'phpc_color_mode=light']);
        $this->assertSelectorExists('html[data-bs-theme="dark"][data-phpc-color-mode="dark"]');
        $this->assertSelectorNotExists('.phpc-color-mode');
    }

    public function testSiteDefault(): void
    {
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', '/en/admin/appearance');
        $this->assertSelectorTextContains('.nav-tabs .nav-link.active[aria-current="page"]', 'Appearance');
        $this->submit('Save', ['form[theme]' => 'bootswatch:lux']);
        $this->assertResponseRedirects('/en/admin/appearance');
        $this->assertSame('bootswatch:lux', static::getContainer()->get(SettingRepository::class)->getValue(SettingRepository::DEFAULT_THEME));

        // Calendars without a theme, and other pages
        $this->client->request('GET', '/en/calendar/club/');
        $this->assertStringContainsString('bootswatch/lux/', $this->stylesheet());
        $this->client->request('GET', '/en/settings');
        $this->assertStringContainsString('bootswatch/lux/', $this->stylesheet());

        // A calendar's own theme wins
        $this->fresh($this->calendar)->setTheme('bootswatch:minty');
        $this->em()->flush();
        $this->client->request('GET', '/en/calendar/club/');
        $this->assertStringContainsString('bootswatch/minty/', $this->stylesheet());

        // Choosing Bootstrap again clears the setting
        $this->client->request('GET', '/en/admin/appearance');
        $this->submit('Save', ['form[theme]' => 'bootstrap']);
        $this->assertNull(static::getContainer()->get(SettingRepository::class)->getValue(SettingRepository::DEFAULT_THEME));
    }

    public function testRemovedThemeFallsBack(): void
    {
        $this->calendar->setTheme('custom:deleted');
        $this->em()->flush();

        $this->client->request('GET', '/en/calendar/club/');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('static/lib/bootstrap/bootstrap.min.css', $this->stylesheet());
    }
}

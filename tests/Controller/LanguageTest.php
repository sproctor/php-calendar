<?php

namespace App\Tests\Controller;

use App\Tests\AppWebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class LanguageTest extends AppWebTestCase
{
    private const FRENCH = ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9'];
    private const ENGLISH = ['HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9'];

    public function testBrowserLanguageWithoutACalendarDefault(): void
    {
        $this->createCalendar('Club');
        $this->client->request('GET', '/', server: self::FRENCH);
        $this->assertResponseRedirects('/fr/calendar/club/');
    }

    public function testCalendarDefaultBeatsTheBrowser(): void
    {
        $calendar = $this->createCalendar('Club');
        $calendar->setLocale('fr');
        $this->em()->flush();

        $this->client->request('GET', '/', server: self::ENGLISH);
        $this->assertResponseRedirects('/fr/calendar/club/');
        // Links without a language, e.g. shared ones, too
        $this->client->request('GET', '/calendar/club', server: self::ENGLISH);
        $this->assertResponseRedirects('/fr/calendar/club/');
        // A link naming a language keeps it
        $this->client->request('GET', '/en/calendar/club/', server: self::ENGLISH);
        $this->assertSelectorTextContains('h1', 'October');
    }

    public function testMenuChoiceIsRemembered(): void
    {
        $calendar = $this->createCalendar('Club');
        $calendar->setLocale('fr');
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/fr/calendar/club/month/2026/10');
        $link = $crawler->filter('.dropdown-menu a:contains("English")')->link();
        $this->client->click($link);
        $this->assertResponseRedirects('/en/calendar/club/month/2026/10');
        $this->assertSame('en', $this->client->getCookieJar()->get('phpc_locale')->getValue());

        // Beats both the calendar's default and the browser
        $this->client->request('GET', '/', server: self::FRENCH);
        $this->assertResponseRedirects('/en/calendar/club/');
        // Pages without a language in the URL use it too
        $this->client->request('GET', '/login', server: self::FRENCH);
        $this->assertSelectorExists('html[lang="en"]');
    }

    public function testUserSettingBeatsTheCookieAndMenuUpdatesIt(): void
    {
        $this->createCalendar('Club');
        $user = $this->createUser('alice');
        $user->setLocale('fr_CA');
        $this->em()->flush();
        $this->login($user);
        $this->client->getCookieJar()->set(new Cookie('phpc_locale', 'en'));

        $this->client->request('GET', '/', server: self::ENGLISH);
        $this->assertResponseRedirects('/fr/calendar/club/');

        // Picking French again keeps the region; picking English replaces it
        $this->client->request('GET', '/language/fr?to=/fr/calendar/club/');
        $this->assertSame('fr_CA', $this->fresh($user)->getLocale());
        $this->client->request('GET', '/language/en?to=/en/calendar/club/');
        $this->em()->clear();
        $this->assertSame('en', $this->fresh($user)->getLocale());
    }

    public function testOnlyRedirectsWithinTheSite(): void
    {
        $this->client->request('GET', '/language/fr?to=//evil.example/');
        $this->assertResponseRedirects('/');
        $this->client->request('GET', '/language/fr?to=https://evil.example/');
        $this->assertResponseRedirects('/');
        $this->client->request('GET', '/language/xx?to=/');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCalendarAdminCanSetTheDefaultLanguage(): void
    {
        $calendar = $this->createCalendar('Club');
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', '/en/calendar/club/admin');
        $this->submit('Save', ['calendar_form[locale]' => 'fr']);
        $this->assertSame('fr', $this->fresh($calendar)->getLocale());
    }
}

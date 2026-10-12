<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Tests\AppWebTestCase;

class RememberMeTest extends AppWebTestCase
{
    private function submitLogin(bool $remember): void
    {
        $this->client->request('GET', '/login');
        $form = $this->client->getCrawler()->selectButton('Log in')->form([
            '_username' => 'pat',
            '_password' => 'password',
        ]);
        if (!$remember) {
            $form['_remember_me']->untick();
        }
        $this->client->submit($form);
        $this->assertResponseRedirects();
    }

    /**
     * Drops the session, as when the browser is closed and opened again
     */
    private function newBrowserSession(): void
    {
        foreach ($this->client->getCookieJar()->all() as $cookie) {
            if ($cookie->getName() !== 'REMEMBERME') {
                $this->client->getCookieJar()->expire($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
        }
    }

    private function assertLoggedIn(bool $expected): void
    {
        $this->client->request('GET', '/en/settings');
        if ($expected) {
            $this->assertResponseIsSuccessful();
        } else {
            $this->assertResponseRedirects('/login');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCalendar();
        $this->createUser('pat');
    }

    public function testStaysLoggedInWhenTicked(): void
    {
        $this->submitLogin(remember: true);
        $cookie = $this->client->getCookieJar()->get('REMEMBERME');
        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(time() + 30 * 24 * 3600, (int) $cookie->getExpiresTime(), 60);
        $this->assertTrue($cookie->isHttpOnly());

        $this->newBrowserSession();
        $this->assertLoggedIn(true);
    }

    public function testForgottenWhenNotTicked(): void
    {
        $this->submitLogin(remember: false);
        $this->assertNull($this->client->getCookieJar()->get('REMEMBERME'));
        $this->newBrowserSession();
        $this->assertLoggedIn(false);
    }

    public function testPasswordChangeEndsRememberedLogins(): void
    {
        $this->submitLogin(remember: true);
        $user = $this->em()->getRepository(User::class)->findOneBy(['username' => 'pat']);
        $user->setHash(password_hash('something else', PASSWORD_BCRYPT));
        $this->em()->flush();

        $this->newBrowserSession();
        $this->assertLoggedIn(false);
    }

    public function testDisablingEndsRememberedLogins(): void
    {
        $this->submitLogin(remember: true);
        $this->em()->getRepository(User::class)->findOneBy(['username' => 'pat'])->setIsDisabled(true);
        $this->em()->flush();

        $this->newBrowserSession();
        $this->assertLoggedIn(false);
    }

    public function testLogoutForgetsTheLogin(): void
    {
        $this->submitLogin(remember: true);
        $crawler = $this->client->request('GET', '/en/settings');
        $this->client->click($crawler->filter('a[href^="/logout?_csrf_token="]')->link());
        $this->assertNull($this->client->getCookieJar()->get('REMEMBERME'));
        $this->assertLoggedIn(false);
    }
}

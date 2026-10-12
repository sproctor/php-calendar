<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\AppWebTestCase;
use Symfony\Component\Mime\Email;

class AccountTest extends AppWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // So each test starts with no "forgot password" requests counted against it
        static::getContainer()->get('cache.rate_limiter.test')->clear();
    }

    private function logInWith(string $identifier, string $password): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Log in', ['_username' => $identifier, '_password' => $password]);
    }

    private function assertLoggedInAs(?string $username): void
    {
        $this->client->request('GET', '/en/settings');
        if ($username === null) {
            $this->assertResponseRedirects();
        } else {
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('nav', $username);
        }
    }

    /**
     * The first link in the last email sent
     */
    private function linkInEmail(): string
    {
        $email = $this->getMailerMessage();
        $this->assertInstanceOf(Email::class, $email);
        $this->assertTrue((bool) preg_match('~https?://\S+~', $email->getTextBody(), $m), 'No link in the email');

        return html_entity_decode($m[0]);
    }

    private function createUserWithEmail(string $username, string $email): User
    {
        $user = $this->createUser($username);
        $user->setEmail($email);
        $this->em()->flush();

        return $user;
    }

    public function testLogInWithUsernameOrEmail(): void
    {
        $this->createUserWithEmail('pat', 'Pat@Example.com');

        $this->logInWith('pat@example.com', 'password');
        $this->assertResponseRedirects();
        $this->assertLoggedInAs('pat');

        $this->client->restart();
        $this->logInWith('pat', 'password');
        $this->assertLoggedInAs('pat');

        $this->client->restart();
        $this->logInWith('pat@example.com', 'wrong');
        $this->assertLoggedInAs(null);
    }

    public function testEmailCantMatchAnotherAccountsUsernameOrEmail(): void
    {
        $this->createUserWithEmail('pat', 'pat@example.com');
        $this->createUser('sam@example.com');
        $this->login($this->createUser('admin', admin: true));

        foreach (['PAT@example.com', 'sam@example.com'] as $taken) {
            $this->client->request('GET', '/en/admin/user/create');
            $this->submit('Save', [
                'user[username]' => 'new',
                'user[email]' => $taken,
                'user[password][first]' => 'password1',
                'user[password][second]' => 'password1',
            ]);
            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorTextContains('form', 'Another account already uses this email address.');
        }
    }

    public function testResetPassword(): void
    {
        $this->createUserWithEmail('pat', 'pat@example.com');

        $this->client->request('GET', '/login');
        $this->client->clickLink('Forgot your password?');
        $this->submit('Send link', ['form[identifier]' => 'pat@example.com']);
        $this->assertResponseRedirects('/login');
        $this->assertEmailCount(1);
        $this->assertEmailAddressContains($this->getMailerMessage(), 'To', 'pat@example.com');
        $link = $this->linkInEmail();

        $this->client->request('GET', $link);
        $this->assertResponseIsSuccessful();
        $this->submit('Save password', ['form[password][first]' => 'new password', 'form[password][second]' => 'new password']);
        $this->assertResponseRedirects('/login');

        $this->logInWith('pat', 'new password');
        $this->assertLoggedInAs('pat');

        // Used once, the link stops working
        $this->client->restart();
        $this->client->request('GET', $link);
        $this->assertResponseStatusCodeSame(410);
    }

    public function testResetLinkMustBeSigned(): void
    {
        $user = $this->createUserWithEmail('pat', 'pat@example.com');
        $this->client->request('GET', '/reset-password');
        $this->submit('Send link', ['form[identifier]' => 'pat']);
        $link = $this->linkInEmail();

        $this->client->request('GET', preg_replace('~/reset-password/\d+~', '/reset-password/' . ($user->getUid() + 1), $link));
        $this->assertResponseStatusCodeSame(410);
        $this->client->request('GET', preg_replace('~([?&]h=)[0-9a-f]~', '${1}0', $link));
        $this->assertResponseStatusCodeSame(410);
    }

    public function testResetSaysTheSameForUnknownAccounts(): void
    {
        $this->createUser('nomail');
        foreach (['nobody', 'nomail'] as $identifier) {
            $this->client->request('GET', '/reset-password');
            $this->submit('Send link', ['form[identifier]' => $identifier]);
            $this->assertResponseRedirects('/login');
            $this->assertEmailCount(0);
            $this->client->followRedirect();
            $this->assertSelectorTextContains('.alert-success', 'If that account has an email address');
        }
    }

    public function testResetRequestsAreLimited(): void
    {
        $this->createUserWithEmail('pat', 'pat@example.com');
        $this->client->disableReboot();
        $sent = 0;
        for ($i = 0; $i < 6; $i++) {
            $this->client->request('GET', '/reset-password');
            $this->submit('Send link', ['form[identifier]' => 'pat']);
            $sent += count($this->getMailerMessages());
        }
        // Three per account an hour
        $this->assertSame(3, $sent);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Too many requests');
    }

    public function testChangingEmailNeedsConfirmation(): void
    {
        $user = $this->createUserWithEmail('pat', 'old@example.com');
        $this->login($user);

        $this->client->request('GET', '/en/settings');
        $this->submit('Save', ['user_settings[email]' => 'new@example.com']);
        $this->assertResponseRedirects('/en/settings');
        $this->assertEmailAddressContains($this->getMailerMessage(), 'To', 'new@example.com');
        $link = $this->linkInEmail();
        $this->em()->clear();
        $this->assertSame('old@example.com', $this->fresh($user)->getEmail());

        $this->client->request('GET', $link);
        $this->assertResponseRedirects('/en/settings');
        $this->em()->clear();
        $this->assertSame('new@example.com', $this->em()->find(User::class, $user->getUid())->getEmail());

        // Removing it needs no confirmation
        $this->client->request('GET', '/en/settings');
        $this->submit('Save', ['user_settings[email]' => '']);
        $this->em()->clear();
        $this->assertNull($this->em()->find(User::class, $user->getUid())->getEmail());
        $this->assertEmailCount(0);
    }
}

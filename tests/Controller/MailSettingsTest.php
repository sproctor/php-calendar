<?php

namespace App\Tests\Controller;

use App\Tests\AppWebTestCase;

class MailSettingsTest extends AppWebTestCase
{
    private string $configFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configFile = static::getContainer()->getParameter('app.local_env_file');
        @unlink($this->configFile);
        $admin = $this->createUser('admin', admin: true);
        $admin->setEmail('admin@example.com');
        $this->em()->flush();
        $this->login($admin);
    }

    protected function tearDown(): void
    {
        @unlink($this->configFile);
        parent::tearDown();
    }

    private function writeConfig(array $env): void
    {
        @mkdir(dirname($this->configFile), 0777, true);
        file_put_contents($this->configFile, '<?php return ' . var_export($env, true) . ';');
    }

    public function testSavesSmtpSettings(): void
    {
        $this->writeConfig(['DATABASE_URL' => 'mysql://kept', 'MAILER_DSN' => 'null://null', 'MAILER_FROM' => '']);

        $this->client->request('GET', '/en/admin/mail/');
        $this->submit('Save', [
            'form[transport]' => 'smtp',
            'form[host]' => 'mail.example.com',
            'form[port]' => '587',
            'form[username]' => 'me@example.com',
            'form[password]' => 'p@ss:word',
            'form[from]' => 'calendar@example.com',
        ]);
        $this->assertResponseRedirects('/en/admin/mail/');
        $env = require $this->configFile;
        $this->assertSame('mysql://kept', $env['DATABASE_URL']);
        $this->assertSame('smtp://me%40example.com:p%40ss%3Aword@mail.example.com:587', $env['MAILER_DSN']);
        $this->assertSame('calendar@example.com', $env['MAILER_FROM']);
    }

    public function testBlankPasswordKeepsTheSavedOne(): void
    {
        $this->writeConfig(['MAILER_DSN' => 'smtps://me:secret@mail.example.com', 'MAILER_FROM' => 'calendar@example.com']);

        $this->client->request('GET', '/en/admin/mail/');
        $this->assertSelectorNotExists('input[value="secret"]');
        $this->submit('Save', [
            'form[transport]' => 'smtp',
            'form[host]' => 'mail.example.com',
            'form[tls]' => '1',
            'form[username]' => 'me',
            'form[password]' => '',
            'form[from]' => 'calendar@example.com',
        ]);
        $env = require $this->configFile;
        $this->assertSame('smtps://me:secret@mail.example.com', $env['MAILER_DSN']);
    }

    public function testNeedsAFromAddress(): void
    {
        $this->writeConfig(['MAILER_DSN' => 'null://null', 'MAILER_FROM' => '']);
        $this->client->request('GET', '/en/admin/mail/');
        $this->submit('Save', ['form[transport]' => 'sendmail', 'form[from]' => '']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('form', 'Enter the address emails come from.');
    }

    public function testReadOnlyWithoutAConfigFile(): void
    {
        $this->client->request('GET', '/en/admin/mail/');
        $this->assertSelectorTextContains('.nav-tabs .nav-link.active[aria-current="page"]', 'Email');
        $this->assertSelectorTextContains('.alert-info', 'environment variables');
        $this->assertSelectorExists('input[name="form[host]"][disabled]');
    }

    public function testSendsATestEmail(): void
    {
        $this->client->request('GET', '/en/admin/mail/');
        $this->submit('Send test email');
        $this->assertResponseRedirects('/en/admin/mail/');
        $this->assertEmailCount(1);
        $this->assertEmailAddressContains($this->getMailerMessage(), 'To', 'admin@example.com');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'accepted the test email');
    }

    public function testOnlyForAdmins(): void
    {
        $this->login($this->createUser('pat'));
        $this->client->request('GET', '/en/admin/mail/');
        $this->assertResponseStatusCodeSame(403);
    }
}

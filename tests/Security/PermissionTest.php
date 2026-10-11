<?php

namespace App\Tests\Security;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Tests\AppWebTestCase;
use DateTimeImmutable;

class PermissionTest extends AppWebTestCase
{
    private function month(Calendar $calendar): string
    {
        return "/en/calendar/{$calendar->getCid()}/month/2026/10";
    }

    public function testAnonymousAccessFollowsCalendarSetting(): void
    {
        $calendar = $this->createCalendar();
        $calendar->setAnonPermission(Calendar::ANON_NONE);
        $this->em()->flush();
        $this->client->request('GET', $this->month($calendar));
        $this->assertResponseRedirects('/login');

        $this->fresh($calendar)->setAnonPermission(Calendar::ANON_READ);
        $this->em()->flush();
        $this->client->request('GET', $this->month($calendar));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.phpc-add');
        $this->client->request('GET', "/en/calendar/{$calendar->getCid()}/event/new");
        $this->assertResponseRedirects('/login');

        $this->fresh($calendar)->setAnonPermission(Calendar::ANON_CREATE);
        $this->em()->flush();
        $this->client->request('GET', "/en/calendar/{$calendar->getCid()}/event/new");
        $this->assertResponseIsSuccessful();
    }

    public function testUserWithoutPermissionIsForbidden(): void
    {
        $calendar = $this->createCalendar();
        $calendar->setAnonPermission(Calendar::ANON_NONE);
        $this->em()->flush();
        $user = $this->createUser('user');
        $this->login($user);
        $this->client->request('GET', $this->month($calendar));
        $this->assertResponseStatusCodeSame(403);

        $this->grant($calendar, $user, 'read');
        $this->client->request('GET', $this->month($calendar));
        $this->assertResponseIsSuccessful();
    }

    public function testRolePermissionsApplyToMembers(): void
    {
        $calendar = $this->createCalendar();
        $calendar->setAnonPermission(Calendar::ANON_NONE);
        $user = $this->createUser('member');
        $role = (new CalendarRole($calendar))->setName('Staff')->setRead(true)->setCreate(true)->addUser($user);
        $this->em()->persist($role);
        $this->em()->flush();

        $this->login($user);
        $this->client->request('GET', "/en/calendar/{$calendar->getCid()}/event/new");
        $this->assertResponseIsSuccessful();
    }

    public function testEditingRules(): void
    {
        $calendar = $this->createCalendar();
        $owner = $this->createUser('owner');
        $editor = $this->createUser('editor');
        $reader = $this->createUser('reader');
        $this->grant($calendar, $owner, 'create');
        $this->grant($calendar, $editor, 'update');
        $event = $this->createEvent($calendar, $owner);
        $edit = "/en/event/{$event->getEid()}/edit";

        foreach ([[$owner, 200], [$editor, 200], [$reader, 403]] as [$user, $status]) {
            $this->login($user);
            $this->client->request('GET', $edit);
            $this->assertResponseStatusCodeSame($status, $user->getUsername());
        }

        $this->fresh($event)->setReadonly(true);
        $this->em()->flush();
        foreach ([[$owner, 200], [$editor, 403]] as [$user, $status]) {
            $this->login($user);
            $this->client->request('GET', $edit);
            $this->assertResponseStatusCodeSame($status, 'read-only: ' . $user->getUsername());
        }
        $this->login($this->createUser('admin', admin: true));
        $this->client->request('GET', $edit);
        $this->assertResponseIsSuccessful();
    }

    public function testAnonymousEventsAreNotOwnedByAnonymousVisitors(): void
    {
        $calendar = $this->createCalendar();
        $calendar->setAnonPermission(Calendar::ANON_CREATE);
        $this->em()->flush();
        $event = $this->createEvent($calendar, null);
        $this->client->request('GET', "/en/event/{$event->getEid()}/edit");
        $this->assertResponseRedirects('/login');
    }

    public function testRoleLimitedCategoryHidesEvents(): void
    {
        $calendar = $this->createCalendar();
        $member = $this->createUser('member');
        $outsider = $this->createUser('outsider');
        $role = (new CalendarRole($calendar))->setName('Board')->addUser($member);
        $category = (new Category($calendar))->setName('Board only')->setColor('#336699')->setRole($role);
        $this->em()->persist($role);
        $this->em()->persist($category);
        $this->em()->flush();
        $event = $this->createEvent($calendar, null, 'Secret meeting');
        $event->setCategory($category);
        $this->em()->flush();

        $this->login($outsider);
        $this->client->request('GET', $this->month($calendar));
        $this->assertSelectorTextNotContains('.phpc-month-view', 'Secret meeting');
        $this->client->request('GET', "/en/event/{$event->getEid()}");
        $this->assertResponseStatusCodeSame(403);

        $this->login($member);
        $this->client->request('GET', $this->month($calendar));
        $this->assertSelectorTextContains('.phpc-month-view', 'Secret meeting');
    }

    public function testUnpublishedEventsOnlyShownToEditors(): void
    {
        $calendar = $this->createCalendar();
        $owner = $this->createUser('owner');
        $event = $this->createEvent($calendar, $owner, 'Surprise');
        $event->setPubtime(new DateTimeImmutable('+1 year'));
        $this->em()->flush();

        $this->client->request('GET', $this->month($calendar));
        $this->assertSelectorTextNotContains('.phpc-month-view', 'Surprise');

        $this->login($owner);
        $this->client->request('GET', $this->month($calendar));
        $this->assertSelectorTextContains('.phpc-month-view', 'Surprise');
    }

    public function testDeletingNeedsPostAndToken(): void
    {
        $calendar = $this->createCalendar();
        $owner = $this->createUser('owner');
        $event = $this->createEvent($calendar, $owner);
        $eid = $event->getEid();
        $this->login($owner);

        $this->client->request('GET', "/en/event/$eid/delete");
        $this->assertResponseStatusCodeSame(405);
        $this->client->request('POST', "/en/event/$eid/delete", ['_token' => 'wrong']);
        $this->assertResponseStatusCodeSame(403);

        $this->postWithToken("/en/event/$eid", "/en/event/$eid/delete");
        $this->assertResponseRedirects("/en/calendar/{$calendar->getCid()}/");
        $this->em()->clear();
        $this->assertNull($this->em()->find(\App\Entity\Event::class, $eid));
    }

    public function testBulkDeleteRejectsEventsTheUserCantEdit(): void
    {
        $calendar = $this->createCalendar();
        $owner = $this->createUser('owner');
        $other = $this->createUser('other');
        $mine = $this->createEvent($calendar, $owner, 'Mine');
        $theirs = $this->createEvent($calendar, $other, 'Theirs');
        $this->login($owner);

        $crawler = $this->client->request('GET', "/en/calendar/{$calendar->getCid()}/day/2026/10/15");
        $token = $crawler->filter('#delete-events-form input[name="_token"]')->attr('value');
        $this->client->request('POST', "/en/calendar/{$calendar->getCid()}/events/delete", [
            '_token' => $token,
            'eid' => [$mine->getEid(), $theirs->getEid()],
        ]);
        $this->assertResponseStatusCodeSame(403);
        $this->em()->clear();
        $this->assertNotNull($this->em()->find(\App\Entity\Event::class, $mine->getEid()));

        $this->client->request('POST', "/en/calendar/{$calendar->getCid()}/events/delete", [
            '_token' => $token,
            'eid' => [$mine->getEid()],
        ]);
        $this->assertResponseRedirects();
        $this->em()->clear();
        $this->assertNull($this->em()->find(\App\Entity\Event::class, $mine->getEid()));
    }

    public function testCalendarAdminNeedsAdminPermission(): void
    {
        $calendar = $this->createCalendar();
        $user = $this->createUser('user');
        $this->login($user);
        $this->client->request('GET', "/en/calendar/{$calendar->getCid()}/admin");
        $this->assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/en/admin/');
        $this->assertResponseStatusCodeSame(403);

        $this->grant($calendar, $user, 'admin');
        $this->client->request('GET', "/en/calendar/{$calendar->getCid()}/admin");
        $this->assertResponseIsSuccessful();
    }

    public function testDisabledUserCannotLogIn(): void
    {
        $user = $this->createUser('sleepy');
        $user->setIsDisabled(true);
        $this->em()->flush();
        $this->createCalendar();

        $this->client->request('GET', '/login');
        $this->client->submitForm('Log in', ['_username' => 'sleepy', '_password' => 'password']);
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
        $this->assertNull(static::getContainer()->get('security.token_storage')->getToken());
    }

    public function testSetupIsClosedOnceUsersExist(): void
    {
        $this->createUser('admin', admin: true);
        $this->client->request('GET', '/setup');
        $this->assertResponseRedirects('/');
    }
}

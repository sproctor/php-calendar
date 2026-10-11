<?php

namespace App\Tests\Controller;

use App\Entity\Calendar;
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\FieldDefinition;
use App\Entity\User;
use App\Entity\UserPermissions;
use App\Tests\AppWebTestCase;

class AdminTest extends AppWebTestCase
{
    public function testCalendarSettings(): void
    {
        $calendar = $this->createCalendar();
        $this->login($this->createUser('admin', admin: true));
        $cid = $calendar->getCid();

        $this->client->request('GET', "/en/calendar/$cid/admin");
        $this->submit('Save', [
            'calendar_form[title]' => 'Club calendar',
            'calendar_form[anon_permission]' => Calendar::ANON_NONE,
            'calendar_form[timezone]' => 'Europe/Paris',
        ]);
        $this->assertResponseRedirects("/en/calendar/$cid/admin");
        $this->em()->clear();
        $calendar = $this->em()->find(Calendar::class, $cid);
        $this->assertSame('Club calendar', $calendar->getTitle());
        $this->assertSame(Calendar::ANON_NONE, $calendar->getAnonPermission());
        $this->assertSame('Europe/Paris', $calendar->getTimezone());
    }

    public function testUserPermissionsTable(): void
    {
        $calendar = $this->createCalendar();
        $admin = $this->createUser('admin', admin: true);
        $user = $this->createUser('member');
        $this->grant($calendar, $user, 'admin');
        $this->login($admin);
        $cid = $calendar->getCid();

        $this->client->request('GET', "/en/calendar/$cid/admin");
        $this->submit('Update permissions', [
            "perm[{$user->getUid()}][admin]" => false,
            "perm[{$user->getUid()}][read]" => true,
            "perm[{$user->getUid()}][create]" => true,
        ]);
        $this->assertResponseRedirects("/en/calendar/$cid/admin#users");
        $this->em()->clear();
        $row = $this->em()->getRepository(UserPermissions::class)->findOneBy(['calendar' => $cid, 'user' => $user->getUid()]);
        $this->assertTrue($row->canRead());
        $this->assertTrue($row->canCreate());
        $this->assertFalse($row->canAdmin());
        // Users without any permission don't get a row
        $this->assertNull($this->em()->getRepository(UserPermissions::class)->findOneBy(['user' => $admin->getUid()]));
    }

    public function testRolesCategoriesAndFields(): void
    {
        $calendar = $this->createCalendar();
        $admin = $this->createUser('admin', admin: true);
        $member = $this->createUser('member');
        $this->login($admin);
        $cid = $calendar->getCid();

        $crawler = $this->client->request('GET', "/en/calendar/$cid/admin/role/new");
        $form = $crawler->selectButton('Save')->form([
            'calendar_role_form[name]' => 'Volunteers',
            'calendar_role_form[create]' => true,
        ]);
        $form['calendar_role_form[users]'][1]->tick();
        $this->client->submit($form);
        $this->assertResponseRedirects("/en/calendar/$cid/admin#roles");
        $role = $this->em()->getRepository(CalendarRole::class)->findOneBy(['name' => 'Volunteers']);
        $this->assertTrue($role->canCreate());
        $this->assertSame(['member'], $role->getUsers()->map(fn(User $u) => $u->getUsername())->toArray());

        $this->client->request('GET', "/en/calendar/$cid/admin/category/new");
        $this->submit('Save', [
            'category_form[name]' => 'Workshops',
            'category_form[color]' => '#123456',
            'category_form[role]' => $role->getRid(),
        ]);
        $this->assertResponseRedirects("/en/calendar/$cid/admin#categories");
        $category = $this->em()->getRepository(Category::class)->findOneBy(['name' => 'Workshops']);
        $this->assertSame('Volunteers', $category->getRole()->getName());
        $this->assertSame('#ffffff', $category->getTextColor());

        $this->client->request('GET', "/en/calendar/$cid/admin/field/new");
        $this->submit('Save', ['field_definition_form[name]' => 'Room', 'field_definition_form[format]' => '(']);
        $this->assertResponseStatusCodeSame(422);
        $this->submit('Save', ['field_definition_form[name]' => 'Room', 'field_definition_form[format]' => '\d+']);
        $this->assertResponseRedirects("/en/calendar/$cid/admin#fields");

        // Deleting the role keeps the category, visible to everyone
        $this->postWithToken("/en/calendar/$cid/admin", "/en/calendar/$cid/admin/role/{$role->getRid()}/delete");
        $this->assertResponseRedirects("/en/calendar/$cid/admin#roles");
        $this->em()->clear();
        $this->assertNull($this->em()->find(Category::class, $category->getCatid())->getRole());
    }

    public function testCannotEditAnotherCalendarsItems(): void
    {
        $mine = $this->createCalendar('Mine');
        $other = $this->createCalendar('Other');
        $field = (new FieldDefinition($this->fresh($other)))->setName('Secret');
        $this->em()->persist($field);
        $this->em()->flush();
        $user = $this->createUser('user');
        $this->grant($mine, $user, 'admin');
        $this->login($user);

        $this->client->request('GET', "/en/calendar/{$mine->getCid()}/admin/field/{$field->getFid()}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSiteAdminManagesUsers(): void
    {
        $this->createCalendar();
        $admin = $this->createUser('admin', admin: true);
        $this->login($admin);

        $this->client->request('GET', '/en/admin/user/create');
        $this->assertResponseIsSuccessful();
        $this->submit('Save', [
            'user[username]' => 'newbie',
            'user[password][first]' => 'correct horse',
            'user[password][second]' => 'correct horse',
        ]);
        $this->assertResponseRedirects('/en/admin/#users');
        $newbie = $this->em()->getRepository(User::class)->findOneBy(['username' => 'newbie']);
        $hash = $newbie->getPassword();

        // Leaving the password blank keeps it
        $this->client->request('GET', "/en/admin/user/{$newbie->getUid()}");
        $this->submit('Save', ['user[username]' => 'renamed']);
        $this->em()->clear();
        $renamed = $this->em()->find(User::class, $newbie->getUid());
        $this->assertSame('renamed', $renamed->getUsername());
        $this->assertSame($hash, $renamed->getPassword());

        $this->postWithToken('/en/admin/', "/en/admin/user/{$newbie->getUid()}/disable");
        $this->em()->clear();
        $this->assertTrue($this->em()->find(User::class, $newbie->getUid())->isDisabled());

        $this->postWithToken('/en/admin/', "/en/admin/user/{$newbie->getUid()}/delete");
        $this->em()->clear();
        $this->assertNull($this->em()->find(User::class, $newbie->getUid()));

        // No delete button for yourself, and the route refuses
        $crawler = $this->client->request('GET', '/en/admin/');
        $this->assertCount(0, $crawler->filter(sprintf('form[action="/en/admin/user/%d/delete"]', $admin->getUid())));
    }

    public function testDeletingCalendarRemovesItsEvents(): void
    {
        $calendar = $this->createCalendar();
        $admin = $this->createUser('admin', admin: true);
        $event = $this->createEvent($calendar, $admin);
        $this->login($admin);

        $this->postWithToken('/en/admin/', "/en/admin/calendar/{$calendar->getCid()}/delete");
        $this->assertResponseRedirects('/en/admin/#calendars');
        $this->em()->clear();
        $this->assertNull($this->em()->find(Calendar::class, $calendar->getCid()));
        $this->assertNull($this->em()->find(\App\Entity\Event::class, $event->getEid()));
    }
}

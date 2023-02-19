<?php
/*
 * Copyright Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CalendarRepository::class)
 * @ORM\Table("calendars")
 */
class Calendar
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $cid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $title;

    private $user_perms = array();
    private $categories;

    /**
     * @ORM\Column(type="integer")
     */
    private int $subject_max = 50;

    /**
     * @ORM\Column(type="integer")
     */
    private int $events_max = 5;

    // TODO: See https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/mysql-enums.html
    private $anon_permission;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $timezone = "America/New_York";

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $locale = "en_US";

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $theme;

    private $groups;
    private $fields;

    /**
     * @return string
     */
    public function getTitle()
    {
        /* TODO: require title on creation/modification
        if (empty($this->title)) {
            return __('calendar-no-title');
        }
        */

        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @return string|null
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return int
     */
    public function getMaxSubjectLength()
    {
        return $this->subject_max;
    }

    /**
     * @param int $uid
     * @param string $perm
     * @return bool
     */
    public function getUserPermission($uid, $perm)
    {
        if (!isset($this->user_perms[$uid])) {
            $this->user_perms[$uid] = $this->db->getPermissions($this->cid, $uid);
        }

        return !empty($this->user_perms[$uid][$perm]);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canRead(User $user)
    {
        if ($this->anon_permission >= 1) {
            return true;
        }

        if (!$user->isUser()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPermission($user->getUID(), 'read');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canWrite(User $user): bool
    {
        if ($this->anon_permission >= 2) {
            return true;
        }

        if ($user->isAnonymous()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPermission($user->getUID(), 'write');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canAdmin(User $user): bool
    {
        if ($user->isAnonymous()) {
            return false;
        }

        return $user->isAdmin() || $this->getUserPermission($user->getUID(), 'admin');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canModify(User $user): bool
    {
        if ($this->anon_permission >= 3) {
            return true;
        }

        if ($user->isAnonymous()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPermission($user->getUID(), 'modify');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canCreateReadonly(User $user): bool
    {
        if ($user->isAnonymous()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPermission($user->getUID(), 'readonly');
    }

    /**
     * @return int
     */
    public function getMaxDisplayEvents(): int
    {
        return $this->events_max;
    }

    /**
     * @param int $uid
     * @return array
     */
    public function getVisibleCategories(int $uid): array
    {
        return $this->db->getVisibleCategories($uid, $this->cid);
    }
    
    /**
     * @return array
     */
    public function getCategories(): array
    {
        if (!isset($this->categories)) {
            $this->categories = $this->db->getCategoriesForCalendar($this->cid);
        }
        return $this->categories;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        if (!isset($this->groups)) {
            $this->groups = $this->db->getGroupsForCalendar($this->cid);
        }
        return $this->groups;
    }

    /**
     * @param int $fid
     * @return string[]
     */
    public function getField(int $fid): array
    {
        if (!isset($this->fields)) {
            $this->fields = $this->db->getFields($this->cid);
        }
        return $this->fields[$fid];
    }

    /**
     * @return int
     */
    public function getAnonPermission(): int
    {
        return $this->anon_permission;
    }


}

<?php
/*
 * Copyright 2017 Sean Proctor
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

namespace PhpCalendar;

class Calendar
{
    private $cid;
    private $title;
    private $user_perms = array();
    private $categories;
    private $hours_24;
    private $date_format;
    private $week_start;
    private $subject_max;
    private $events_max;
    private $anon_permission;
    private $timezone;
    private $language;
    private $theme;
    private $groups;
    private $fields;
    private $db;

    private function __construct(Database $db)
    {
        $this->db = $db;
    }

    public static function createFromMap(Database $db, $result)
    {
        $calendar = new Calendar($db);

        $calendar->cid = intval($result['cid']);
        $calendar->title = $result['title'];
        $calendar->hours_24 = boolval($result['hours_24']);
        $calendar->date_format = intval($result['date_format']);
        $calendar->week_start = intval($result['week_start']);
        $calendar->subject_max = intval($result['subject_max']);
        $calendar->events_max = intval($result['events_max']);
        $calendar->anon_permission = $result['anon_permission'];
        $calendar->timezone = $result['timezone'];
        $calendar->language = $result['language'];
        $calendar->theme = $result['theme'];

        return $calendar;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if (empty($this->title)) {
            return __('(No title)');
        }

        return htmlspecialchars($this->title);
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
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return int
     */
    public function getSubjectMax()
    {
        return $this->subject_max;
    }

    /**
     * @return string
     */
    public function getWeekStart()
    {
        return $this->week_start;
    }

    /**
     * @param int $uid
     * @param string $perm
     * @return bool
     */
    public function getUserPerm($uid, $perm)
    {
        if (!isset($this->user_perms[$uid])) {
            $this->user_perms[$uid] = $this->db->get_permissions($this->cid, $uid);
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

        return $this->canAdmin($user) || $this->getUserPerm($user->getUID(), 'read');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canWrite(User $user)
    {
        if ($this->anon_permission >= 2) {
            return true;
        }

        if (!$user->isUser()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPerm($user->getUID(), 'write');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canAdmin(User $user)
    {
        if (!$user->isUser()) {
            return false;
        }

        return $user->isAdmin() || $this->getUserPerm($user->getUID(), 'admin');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canModify(User $user)
    {
        if ($this->anon_permission >= 3) {
            return true;
        }

        if (!$user->isUser()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPerm($user->getUID(), 'modify');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canCreateReadonly(User $user)
    {
        if (!$user->isUser()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPerm($user->getUID(), 'readonly');
    }

    /**
     * @return int
     */
    public function getMaxDisplayEvents()
    {
        return $this->events_max;
    }

    /**
     * @return array
     */
    public function getVisibleCategories($uid)
    {
        return $this->db->get_visible_categories($uid, $this->cid);
    }
    
    /**
     * @return array
     */
    public function getCategories()
    {
        if (!isset($this->categories)) {
            $this->categories = $this->db->get_categories($this->cid);
        }
        return $this->categories;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        if (!isset($this->groups)) {
            $this->groups = $this->db->get_groups($this->cid);
        }
        return $this->groups;
    }

    /**
     * @param int $fid
     * @return string[]
     */
    public function getField($fid)
    {
        if (!isset($this->fields)) {
            $this->fields = $this->db->get_fields($this->cid);
        }
        return $this->fields[$fid];
    }

    /**
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return Occurrence[]
     */
    public function getOccurrencesByDateRange(\DateTimeInterface $from, \DateTimeInterface $to)
    {
        return $this->db->getOccurrencesByDateRange($this->cid, $from, $to);
    }

    /**
     * @return int
     */
    public function getDateFormat()
    {
        return $this->date_format;
    }
    
    /**
     * @return bool
     */
    public function is24Hour()
    {
        return $this->hours_24;
    }

    /**
     * @return int
     */
    public function getAnonPermission()
    {
        return $this->anon_permission;
    }
}

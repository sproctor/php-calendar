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

namespace PhpCalendar\Entity;

/**
 * @Entity
 * @Table("calendars")
 */
class Calendar
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $cid;

    /**
     * @Column(type="string", length=255)
     */
    private $name;

    /**
     * @Column(type="string", length=255)
     */
    private $title;

    private $user_perms = array();
    private $categories;

    /**
     * @Column(type="integer")
     */
    private $subject_max;

    /**
     * @Column(type="integer")
     */
    private $events_max;

    // TODO: See https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/mysql-enums.html
    private $anon_permission;

    /**
     * @Column(type="string", length=255)
     */
    private $timezone;

    /**
     * @Column(type="string", length=255)
     */
    private $locale;

    /**
     * @Column(type="string", length=255)
     */
    private $theme;

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
    public function canWrite(User $user)
    {
        if ($this->anon_permission >= 2) {
            return true;
        }

        if (!$user->isUser()) {
            return false;
        }

        return $this->canAdmin($user) || $this->getUserPermission($user->getUID(), 'write');
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

        return $user->isAdmin() || $this->getUserPermission($user->getUID(), 'admin');
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

        return $this->canAdmin($user) || $this->getUserPermission($user->getUID(), 'modify');
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

        return $this->canAdmin($user) || $this->getUserPermission($user->getUID(), 'readonly');
    }

    /**
     * @return int
     */
    public function getMaxDisplayEvents()
    {
        return $this->events_max;
    }

    /**
     * @param int $uid
     * @return array
     */
    public function getVisibleCategories($uid)
    {
        return $this->db->getVisibleCategories($uid, $this->cid);
    }
    
    /**
     * @return array
     */
    public function getCategories()
    {
        if (!isset($this->categories)) {
            $this->categories = $this->db->getCategoriesForCalendar($this->cid);
        }
        return $this->categories;
    }

    /**
     * @return array
     */
    public function getGroups()
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
    public function getField($fid)
    {
        if (!isset($this->fields)) {
            $this->fields = $this->db->getFields($this->cid);
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
    public function getAnonPermission()
    {
        return $this->anon_permission;
    }

    /**
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param User               $user
     * @return array
     */
    public function getOccurrencesByDay(\DateTimeInterface $from, \DateTimeInterface $to, User $user)
    {
        $all_occurrences = $this->getOccurrencesByDateRange($from, $to);
        $occurrences_by_day = array();

        foreach ($all_occurrences as $occurrence) {
            if (!$occurrence->canRead($user)) {
                continue;
            }

            $end = $occurrence->getEnd();
            $start = $occurrence->getStart();

            if ($start > $from) {
                $diff = new \DateInterval("P0D");
            } else { // the event started before the range we're showing
                $diff = $from->diff($start);
            }

            // put the event in every day until the end
            for ($date = $start->add($diff); $date < $to && $date <= $end;
                    $date = $date->add(new \DateInterval("P1D"))) {
                $key = date_index($date);
                if (!isset($occurrences_by_day[$key])) {
                    $occurrences_by_day[$key] = array();
                }
                if (sizeof($occurrences_by_day[$key]) == $this->getMaxDisplayEvents()) {
                    $occurrences_by_day[$key][] = null;
                }
                if (sizeof($occurrences_by_day[$key]) > $this->getMaxDisplayEvents()) {
                    continue;
                }
                $occurrences_by_day[$key][] = $occurrence;
            }
        }
        return $occurrences_by_day;
    }
}

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

namespace PhpCalendar;

/**
 * Event represents an event that may have multiple occurrences.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
class Event
{
    private $eid;
    private $owner_uid;
    private $author;
    private $subject;
    private $desc;
    private $category;
    private $bg_color;
    private $text_color;
    private $catid;
    private $gid;
    private $ctime;
    private $mtime;
    protected $calendar;
    private $fields;
    private $db;

    /**
     * @param Database $db
     * @param string[] $event
     */
    public function __construct(Database $db, $event)
    {
        $this->db = $db;
        $this->eid = intval($event['eid']);
        $this->owner_uid = $event['owner'];
        if (empty($event['owner'])) {
            $this->author = __('anonymous-username');
        } else {
            $this->author = $event['username'];
        }
        $this->subject = $event['subject'];
        $this->desc = $event['description'];
        $this->category = $event['name'];
        $this->bg_color = $event['bg_color'];
        $this->text_color = $event['text_color'];
        $this->catid = $event['catid'];
        $this->gid = $event['gid'];
        $this->ctime = datetime_from_timestamp($event['ctime']);
        $this->mtime = empty($event['mtime']) ? null : datetime_from_timestamp($event['mtime']);
        $this->calendar = $db->getCalendar($event['cid']);
    }

    /**
     * @return string
     */
    public function getRawSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        if (empty($this->subject)) {
            return __('nonexistent-subject');
        }

        return $this->subject;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->db->getUser($this->owner_uid);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->desc;
    }

    /**
     * @return int
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * @return Occurrence[]
     */
    public function getOccurrences()
    {
        return $this->db->getOccurrences($this->eid);
    }

    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * @return null|string
     */
    public function getTextColor()
    {
        return $this->text_color;
    }

    /**
     * @return null|string
     */
    public function getBgColor()
    {
        return $this->bg_color;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        if (empty($this->category)) {
            return $this->category;
        }
        return $this->category;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isOwner(User $user)
    {
        return $user->getUid() == $this->owner_uid;
    }

    /**
     * Returns whether or not the current user can modify $event
     *
     * @param User $user
     * @return bool
     */
    public function canModify(User $user)
    {
        return $this->calendar->canAdmin($user) || $this->isOwner($user)
            || ($this->calendar->canModify($user));
    }

    /**
     * Returns whether or not the user can read this event
     *
     * @param User $user
     * @return bool
     */
    public function canRead(User $user)
    {
        $visible_category = empty($this->gid) || !isset($this->catid)
            || $this->db->isCategoryVisible($user, $this->catid);
        return $this->calendar->canRead($user) && $visible_category;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        if (!isset($this->fields)) {
            $this->fields = $this->db->getEventFields($this->eid);
        }

        return $this->fields;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreated()
    {
        return $this->ctime;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getModified()
    {
        return $this->mtime;
    }
}

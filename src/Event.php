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

/**
 * Event represents an event that may have multiple occurrences.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
class Event
{
    private $eid;
    private $cid;
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
    protected $cal;
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
        $this->cid = $event['cid'];
        $this->owner_uid = $event['owner'];
        if (empty($event['owner'])) {
            $this->author = __('anonymous');
        } elseif (empty($event['username'])) {
            $this->author = __('unknown');
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
        $this->ctime = fromTimestampImmutable($event['ctime']);
        $this->mtime = empty($event['mtime']) ? null : fromTimestampImmutable($event['mtime']);
        $this->cal = $db->getCalendar($this->cid);
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
            return __('(No subject)');
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
        return $this->db->get_user($this->owner);
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
        return $this->db->get_occurrences_by_eid($this->eid);
    }

    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->db->getCalendar($this->cid);
    }

    /**
     * @return NULL|string
     */
    public function getTextColor()
    {
        if (empty($this->text_color)) {
            return null;
        }
        return htmlspecialchars($this->text_color, ENT_COMPAT, "UTF-8");
    }

    /**
     * @return null|string
     */
    public function getBgColor()
    {
        if (empty($this->bg_color)) {
            return null;
        }
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
        return $user->getUID() == $this->owner_uid;
    }

    /**
     * Returns whether or not the current user can modify $event
     *
     * @param User $user
     * @return bool
     */
    public function canModify(User $user)
    {
        return $this->cal->canAdmin($user) || $this->isOwner($user)
        || ($this->cal->canModify($user));
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
        || $this->db->is_cat_visible($user->getUID(), $this->catid);
        return $this->cal->canRead($user) && $visible_category;
    }

    /**
     * @return string
     */
    public function getCtimeString()
    {
        return format_datetime(
            $this->ctime,
            $this->cal->getDateFormat(),
            $this->cal->is24Hour()
        );
    }

    /**
     * @return string|null
     */
    public function getMtimeString()
    {
        if (empty($this->mtime)) {
            return null;
        }
            
        return format_datetime(
            $this->mtime,
            $this->cal->getDateFormat(),
            $this->cal->is24Hour()
        );
    }

    /**
     * @return array
     */
    public function getFields()
    {
        if (!isset($this->fields)) {
            $this->fields = $this->db->get_event_fields($this->eid);
        }

        return $this->fields;
    }
}

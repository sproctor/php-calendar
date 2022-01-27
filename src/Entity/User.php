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
 * @Entity
 * @Table("users")
 */
class User
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $uid;

    /**
     * @Column(type="string", length=255)
     */
    private $username;

    /**
     * @Column(type="string", length=255)
     */
    private $hash;

    /**
     * @Column(type="boolean")
     */
    private $is_admin;

    /**
     * @Column(type="boolean")
     */
    private $password_is_editable;

    /**
     * @ManyToOne(targetEntity="Calendar")
     * @JoinColumn(name="default_cid", referencedColumnName="cid")
     */
    private $default_calendar;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $timezone;

    /**
     * @Column(type="string", length=255)
     */
    private $locale;

    // TODO: implement
    private $groups;

    /**
     * @Column(type="boolean")
     */
    private $is_disabled;


    /**
     * @param Context $context
     * @return User
     */
    public static function createAnonymous(Context $context)
    {
        $user = new User();

        $user->uid = 0;
        $user->username = 'anonymous';
        $user->admin = false;
        $user->password_editable = false;
        $user->timezone = User::getAnonymousTimezone($context);
        $user->locale = User::getAnonymousLocale($context);
        $user->disabled = false;

        return $user;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getPasswordHash()
    {
        return $this->hash;
    }

    /**
     * @return bool
     */
    public function hasEditablePassword()
    {
        return $this->password_is_editable;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getLocale()
    {
        return $this->locale;
    }
    
    public function getGroups()
    {
        if (!isset($this->groups)) {
            $this->groups = $this->db->getGroupsForUser($this->uid);
        }

        return $this->groups;
    }

    public function isDisabled()
    {
        return $this->is_disabled;
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function defaultCalendar()
    {
        return $this->default_calendar;
    }

    public function isAnonymous()
    {
        return $this->uid == 0;
    }

    /**
     * @param Context $context
     * @return string|null
     */
    private static function getAnonymousTimezone(Context $context)
    {
        $tz = $context->request->get('tz');
        // If we have a timezone, make sure it's valid
        if (in_array($tz, timezone_identifiers_list())) {
            return $tz;
        }
    
        return null;
    }
    
    /**
     * @param Context $context
     * @return string|null
     */
    private static function getAnonymousLocale(Context $context)
    {
        if ($context->request->get('lang') !== null) {
            $lang = $context->request->get('lang');
            $context->session->set('_locale', $lang);
            return $lang;
        } // else
        if ($context->session->get('_locale') !== null) {
            return $context->session->get('_locale');
        } // else
        return null;
    }
}

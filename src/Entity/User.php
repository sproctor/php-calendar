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

use App\Context;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("users")
 */
class User
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $uid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $hash;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $is_admin = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $password_is_editable = true;

    /**
     * @ORM\ManyToOne(targetEntity="Calendar")
     * @ORM\JoinColumn(name="default_cid", referencedColumnName="cid")
     */
    private $default_calendar;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $timezone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $locale;

    // TODO: implement
    private $groups;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $is_disabled = false;


    /**
     * @param Context $context
     * @return User
     */
    public static function createAnonymous(Context $context): User
    {
        $user = new User();

        $user->uid = 0;
        $user->username = 'anonymous';
        $user->is_admin = false;
        $user->password_is_editable = false;
        $user->timezone = User::getAnonymousTimezone($context);
        $user->locale = User::getAnonymousLocale($context);
        $user->is_disabled = false;

        return $user;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getPasswordHash(): string
    {
        return $this->hash;
    }

    /**
     * @return bool
     */
    public function hasEditablePassword(): bool
    {
        return $this->password_is_editable;
    }

    /**
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getLocale(): ?string
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

    public function isDisabled(): bool
    {
        return $this->is_disabled;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function defaultCalendar()
    {
        return $this->default_calendar;
    }

    public function isAnonymous(): bool
    {
        return $this->uid == 0;
    }

    /**
     * @param Context $context
     * @return string|null
     */
    private static function getAnonymousTimezone(Context $context): ?string
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
    private static function getAnonymousLocale(Context $context): ?string
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

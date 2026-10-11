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

use App\Repository\UserPermissionsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Permissions granted to one user on one calendar
 */
#[ORM\Entity(repositoryClass: UserPermissionsRepository::class)]
#[ORM\Table('user_permissions')]
class UserPermissions
{
    #[ORM\Column(name: '`read`', type: 'boolean')]
    private bool $read = false;

    #[ORM\Column(name: '`create`', type: 'boolean')]
    private bool $create = false;

    #[ORM\Column(name: '`update`', type: 'boolean')]
    private bool $update = false;

    #[ORM\Column(name: '`moderate`', type: 'boolean')]
    private bool $moderate = false;

    #[ORM\Column(name: '`admin`', type: 'boolean')]
    private bool $admin = false;

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Calendar::class)]
        #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid', onDelete: 'CASCADE')]
        private Calendar $calendar,
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(name: 'uid', referencedColumnName: 'uid', onDelete: 'CASCADE')]
        private User $user,
    ) {
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function canRead(): bool
    {
        return $this->read;
    }

    public function setRead(bool $read): self
    {
        $this->read = $read;

        return $this;
    }

    public function canCreate(): bool
    {
        return $this->create;
    }

    public function setCreate(bool $create): self
    {
        $this->create = $create;

        return $this;
    }

    public function canUpdate(): bool
    {
        return $this->update;
    }

    public function setUpdate(bool $update): self
    {
        $this->update = $update;

        return $this;
    }

    public function canModerate(): bool
    {
        return $this->moderate;
    }

    public function setModerate(bool $moderate): self
    {
        $this->moderate = $moderate;

        return $this;
    }

    public function canAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }
}

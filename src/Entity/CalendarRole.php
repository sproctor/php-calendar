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

use App\Repository\CalendarRoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A named group of users on a calendar. Members get the role's permissions, and categories can be
 * limited to the members of a role. Replaces 2.0's groups.
 */
#[ORM\Entity(repositoryClass: CalendarRoleRepository::class)]
#[ORM\Table(name: 'roles')]
class CalendarRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $rid;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'roles')]
    #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid', nullable: false, onDelete: 'CASCADE')]
    private Calendar $calendar;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[ORM\Column]
    private bool $can_read = false;

    #[ORM\Column]
    private bool $can_create = false;

    #[ORM\Column]
    private bool $can_update = false;

    #[ORM\Column]
    private bool $can_moderate = false;

    #[ORM\Column]
    private bool $can_admin = false;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'role_users')]
    #[ORM\JoinColumn(name: 'rid', referencedColumnName: 'rid', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'uid', referencedColumnName: 'uid', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['username' => 'ASC'])]
    private Collection $users;

    public function __construct(Calendar $calendar)
    {
        $this->calendar = $calendar;
        $calendar->getRoles()->add($this);
        $this->users = new ArrayCollection();
    }

    public function getRid(): int
    {
        return $this->rid;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function canRead(): bool
    {
        return $this->can_read;
    }

    public function setRead(bool $can_read): self
    {
        $this->can_read = $can_read;

        return $this;
    }

    public function canCreate(): bool
    {
        return $this->can_create;
    }

    public function setCreate(bool $can_create): self
    {
        $this->can_create = $can_create;

        return $this;
    }

    public function canUpdate(): bool
    {
        return $this->can_update;
    }

    public function setUpdate(bool $can_update): self
    {
        $this->can_update = $can_update;

        return $this;
    }

    public function canModerate(): bool
    {
        return $this->can_moderate;
    }

    public function setModerate(bool $can_moderate): self
    {
        $this->can_moderate = $can_moderate;

        return $this;
    }

    public function canAdmin(): bool
    {
        return $this->can_admin;
    }

    public function setAdmin(bool $can_admin): self
    {
        $this->can_admin = $can_admin;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function hasUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        foreach ($this->users as $member) {
            if ($member->getUid() === $user->getUid()) {
                return true;
            }
        }

        return false;
    }

    public function addUser(User $user): self
    {
        if (!$this->hasUser($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        foreach ($this->users as $member) {
            if ($member->getUid() === $user->getUid()) {
                $this->users->removeElement($member);
            }
        }

        return $this;
    }
}

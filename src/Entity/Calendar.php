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

use App\Repository\CalendarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CalendarRepository::class)]
#[ORM\Table("calendars")]
class Calendar
{
    /** Anonymous users (and everyone else) can't see the calendar */
    public const ANON_NONE = 0;
    /** Everyone can read events */
    public const ANON_READ = 1;
    /** Everyone can read and create events */
    public const ANON_CREATE = 2;
    /** Everyone can read, create and modify events */
    public const ANON_MODIFY = 3;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $cid;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 255)]
    private int $subject_max = 50;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 255)]
    private int $events_max = 8;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Timezone]
    private string $timezone = 'UTC';

    /**
     * Minimum permission level everyone has on this calendar, logged in or not (ANON_* constants)
     */
    #[ORM\Column(type: 'smallint', options: ['default' => self::ANON_READ])]
    #[Assert\Choice(choices: [self::ANON_NONE, self::ANON_READ, self::ANON_CREATE, self::ANON_MODIFY])]
    private int $anon_permission = self::ANON_READ;

    /** @var Collection<int, Category> */
    #[ORM\OneToMany(mappedBy: 'calendar', targetEntity: Category::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $categories;

    /** @var Collection<int, CalendarRole> */
    #[ORM\OneToMany(mappedBy: 'calendar', targetEntity: CalendarRole::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $roles;

    /** @var Collection<int, FieldDefinition> */
    #[ORM\OneToMany(mappedBy: 'calendar', targetEntity: FieldDefinition::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $field_definitions;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->field_definitions = new ArrayCollection();
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSubjectMax(): int
    {
        return $this->subject_max;
    }

    public function setSubjectMax(int $subject_max): self
    {
        $this->subject_max = $subject_max;

        return $this;
    }

    public function getEventsMax(): int
    {
        return $this->events_max;
    }

    public function setEventsMax(int $events_max): self
    {
        $this->events_max = $events_max;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getAnonPermission(): int
    {
        return $this->anon_permission;
    }

    public function setAnonPermission(int $anon_permission): self
    {
        $this->anon_permission = $anon_permission;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @return Collection<int, CalendarRole>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @return Collection<int, FieldDefinition>
     */
    public function getFieldDefinitions(): Collection
    {
        return $this->field_definitions;
    }
}

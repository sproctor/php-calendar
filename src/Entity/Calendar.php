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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CalendarRepository::class)]
#[ORM\Table("calendars")]
#[ORM\UniqueConstraint(name: 'slug', columns: ['slug'])]
#[UniqueEntity(fields: ['slug'], message: 'slug-taken-error')]
class Calendar implements EventSource
{
    /** What a slug may look like, for validation and route requirements */
    public const SLUG_PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';
    public const SLUG_MAX_LENGTH = 100;

    /** Anonymous users (and everyone else) can't see the calendar */
    public const ANON_NONE = 0;
    /** Everyone can read events */
    public const ANON_READ = 1;
    /** Everyone can read and create events */
    public const ANON_CREATE = 2;
    /** Everyone can read, create and modify events */
    public const ANON_MODIFY = 3;

    /** Events may be at the same time as others */
    public const OVERLAP_ALLOW = 0;
    /** Saving an event at the same time as another asks for confirmation */
    public const OVERLAP_WARN = 1;
    /** Events can't be at the same time as others, e.g. bookings of a room */
    public const OVERLAP_DISALLOW = 2;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $cid = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $title = '';

    /**
     * Identifies the calendar in URLs. CalendarSlugListener fills it in from the title when it's left empty.
     */
    #[ORM\Column(type: 'string', length: self::SLUG_MAX_LENGTH)]
    #[Assert\Length(max: self::SLUG_MAX_LENGTH)]
    #[Assert\Regex(pattern: '/^' . self::SLUG_PATTERN . '$/', message: 'invalid-slug-error')]
    private string $slug = '';

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
     * Language to show the calendar in when a visitor hasn't picked one; null uses the browser's
     */
    #[ORM\Column(type: 'string', length: 35, nullable: true)]
    private ?string $locale = null;

    /**
     * Theme ID (see ThemeRegistry); null uses the site's default theme
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $theme = null;

    /**
     * Whether events may overlap (OVERLAP_* constants); see OverlapChecker
     */
    #[ORM\Column(type: 'smallint', options: ['default' => self::OVERLAP_ALLOW])]
    #[Assert\Choice(choices: [self::OVERLAP_ALLOW, self::OVERLAP_WARN, self::OVERLAP_DISALLOW])]
    private int $overlap_policy = self::OVERLAP_ALLOW;

    /**
     * Minimum permission level everyone has on this calendar, logged in or not (ANON_* constants)
     */
    #[ORM\Column(type: 'smallint', options: ['default' => self::ANON_READ])]
    #[Assert\Choice(choices: [self::ANON_NONE, self::ANON_READ, self::ANON_CREATE, self::ANON_MODIFY])]
    private int $anon_permission = self::ANON_READ;

    /** @var Collection<int, Category> */
    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'calendar')]
    #[ORM\OrderBy(['name' => \SortDirection::Ascending])]
    private Collection $categories;

    /** @var Collection<int, CalendarRole> */
    #[ORM\OneToMany(targetEntity: CalendarRole::class, mappedBy: 'calendar')]
    #[ORM\OrderBy(['name' => \SortDirection::Ascending])]
    private Collection $roles;

    /** @var Collection<int, FieldDefinition> */
    #[ORM\OneToMany(targetEntity: FieldDefinition::class, mappedBy: 'calendar')]
    #[ORM\OrderBy(['name' => \SortDirection::Ascending])]
    private Collection $field_definitions;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->field_definitions = new ArrayCollection();
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    public function getCalendars(): array
    {
        return [$this];
    }

    public function isView(): bool
    {
        return false;
    }

    public function getCalendarColor(Calendar $calendar): ?string
    {
        return null;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug ?? '';

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

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme === '' ? null : $theme;

        return $this;
    }

    public function getOverlapPolicy(): int
    {
        return $this->overlap_policy;
    }

    public function setOverlapPolicy(int $overlap_policy): self
    {
        $this->overlap_policy = $overlap_policy;

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

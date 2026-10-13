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

use App\Repository\ViewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Several calendars shown together, each marked with its own color. A view has no events of its own:
 * visitors see the events of its calendars that they could see on those calendars.
 */
#[ORM\Entity(repositoryClass: ViewRepository::class)]
#[ORM\Table('views')]
#[ORM\UniqueConstraint(name: 'slug', columns: ['slug'])]
#[UniqueEntity(fields: ['slug'], message: 'slug-taken-error')]
class View implements EventSource
{
    /** The URL name of the built-in view of every calendar (see AllCalendarsView) */
    public const ALL = 'all';

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $vid = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Regex(pattern: '/^' . Calendar::SLUG_PATTERN . '$/', message: 'invalid-slug-error')]
    #[Assert\NotEqualTo(value: self::ALL, message: 'slug-taken-error')]
    private string $slug = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Timezone]
    private string $timezone = 'UTC';

    #[ORM\Column(type: 'string', length: 35, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $theme = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 255)]
    private int $events_max = 8;

    /** @var Collection<int, ViewCalendar> */
    #[ORM\OneToMany(targetEntity: ViewCalendar::class, mappedBy: 'view', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getVid(): ?int
    {
        return $this->vid;
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

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

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

    public function getEventsMax(): int
    {
        return $this->events_max;
    }

    public function setEventsMax(int $events_max): self
    {
        $this->events_max = $events_max;

        return $this;
    }

    /**
     * @return Collection<int, ViewCalendar>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * Makes the view's calendars exactly these, in this order, keeping existing memberships
     *
     * @param array<int, array{0: Calendar, 1: string}> $calendars calendar and color pairs
     */
    public function setCalendars(array $calendars): self
    {
        $existing = [];
        foreach ($this->members as $member) {
            $existing[$member->getCalendar()->getCid() ?? spl_object_id($member->getCalendar())] = $member;
        }
        $kept = [];
        foreach (array_values($calendars) as $position => [$calendar, $color]) {
            $key = $calendar->getCid() ?? spl_object_id($calendar);
            $member = $existing[$key] ?? new ViewCalendar($this, $calendar);
            $member->setColor($color)->setPosition($position);
            if (!$this->members->contains($member)) {
                $this->members->add($member);
            }
            $kept[$key] = true;
        }
        foreach ($existing as $key => $member) {
            if (!isset($kept[$key])) {
                $this->members->removeElement($member);
            }
        }

        return $this;
    }

    public function getCalendars(): array
    {
        return $this->members->map(fn(ViewCalendar $member) => $member->getCalendar())->getValues();
    }

    public function isView(): bool
    {
        return true;
    }

    public function getCalendarColor(Calendar $calendar): ?string
    {
        foreach ($this->members as $member) {
            if ($member->getCalendar() === $calendar) {
                return $member->getColor();
            }
        }

        return null;
    }
}

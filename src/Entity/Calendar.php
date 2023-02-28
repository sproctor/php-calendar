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
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendarRepository::class)]
#[ORM\Table("calendars")]
class Calendar
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $cid;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    private array $user_perms = [];
    private array $categories;

    #[ORM\Column(type: 'integer')]
    private int $subject_max = 50;

    #[ORM\Column(type: 'integer')]
    private int $events_max = 5;

    // TODO: See https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/mysql-enums.html
    private $anon_permission;

    #[ORM\Column(type: 'string', length: 255)]
    private string $timezone = "America/New_York";

    #[ORM\Column(type: 'string', length: 255)]
    private string $locale = "en_US";

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $theme;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getTimezone(): string|null
    {
        return $this->timezone;
    }

    public function getLocale(): string|null
    {
        return $this->locale;
    }

    public function getMaxSubjectLength(): int
    {
        return $this->subject_max;
    }

    public function getMaxDisplayEvents(): int
    {
        return $this->events_max;
    }

    public function getVisibleCategories(int $uid): array
    {
        return $this->db->getVisibleCategories($uid, $this->cid);
    }
    
    public function getCategories(): array
    {
        if (!isset($this->categories)) {
            $this->categories = $this->db->getCategoriesForCalendar($this->cid);
        }
        return $this->categories;
    }

    public function getGroups(): array
    {
        if (!isset($this->groups)) {
            $this->groups = $this->db->getGroupsForCalendar($this->cid);
        }
        return $this->groups;
    }

    /**
     * @return string[]
     */
    public function getField(int $fid): array
    {
        if (!isset($this->fields)) {
            $this->fields = $this->db->getFields($this->cid);
        }
        return $this->fields[$fid];
    }

    public function getAnonPermission(): int
    {
        return $this->anon_permission;
    }

    public function getSubjectMax(): ?int
    {
        return $this->subject_max;
    }

    public function setSubjectMax(int $subject_max): self
    {
        $this->subject_max = $subject_max;

        return $this;
    }

    public function getEventsMax(): ?int
    {
        return $this->events_max;
    }

    public function setEventsMax(int $events_max): self
    {
        $this->events_max = $events_max;

        return $this;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function setLocale(string $locale): self
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
        $this->theme = $theme;

        return $this;
    }


}

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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table('categories')]
class Category
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $catid;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid', nullable: false, onDelete: 'CASCADE')]
    private Calendar $calendar;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    /**
     * Background color as #rrggbb; the text color is picked for contrast
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Regex('/^#[0-9a-fA-F]{6}$/')]
    private string $color = '#ffffff';

    /**
     * When set, only members of this role (and calendar admins) can see the category's events
     */
    #[ORM\ManyToOne(targetEntity: CalendarRole::class)]
    #[ORM\JoinColumn(name: 'rid', referencedColumnName: 'rid', nullable: true, onDelete: 'SET NULL')]
    private ?CalendarRole $role = null;

    public function __construct(Calendar $calendar)
    {
        $this->calendar = $calendar;
        $calendar->getCategories()->add($this);
    }

    public function getCatid(): int
    {
        return $this->catid;
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

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Black or white, whichever contrasts better with the background color
     */
    public function getTextColor(): string
    {
        [$r, $g, $b] = sscanf($this->color, '#%02x%02x%02x');
        $luminance = 0.2126 * self::linearize($r) + 0.7152 * self::linearize($g) + 0.0722 * self::linearize($b);

        return $luminance > 0.179 ? '#000000' : '#ffffff';
    }

    private static function linearize(int $channel): float
    {
        $c = $channel / 255.0;

        return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    public function getRole(): ?CalendarRole
    {
        return $this->role;
    }

    public function setRole(?CalendarRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

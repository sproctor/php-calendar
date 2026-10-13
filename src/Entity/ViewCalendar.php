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

/**
 * A calendar in a view, and the color its events are marked with there, if any
 */
#[ORM\Entity]
#[ORM\Table('view_calendars')]
class ViewCalendar
{
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex('/^#[0-9a-fA-F]{6}$/')]
    private ?string $color = null;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: View::class, inversedBy: 'members')]
        #[ORM\JoinColumn(name: 'vid', referencedColumnName: 'vid', nullable: false, onDelete: 'CASCADE')]
        private View $view,
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Calendar::class)]
        #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid', nullable: false, onDelete: 'CASCADE')]
        private Calendar $calendar,
    ) {
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}

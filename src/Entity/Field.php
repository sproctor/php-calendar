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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * User defined field on an actual event.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('fields')]
class Field
{
    #[ORM\ManyToOne(targetEntity: 'FieldDefinition')]
    #[ORM\JoinColumn(name: 'fid', referencedColumnName: 'fid')]
    #[ORM\Id]
    private ?\App\Entity\FieldDefinition $definition = null;

    #[ORM\ManyToOne(targetEntity: 'Event', inversedBy: 'fields')]
    #[ORM\JoinColumn(name: 'eid', referencedColumnName: 'eid')]
    #[ORM\Id]
    private ?\App\Entity\Event $event = null;

    #[ORM\Column(type: 'text')]
    private ?string $value = null;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDefinition(): ?FieldDefinition
    {
        return $this->definition;
    }

    public function setDefinition(?FieldDefinition $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }
}

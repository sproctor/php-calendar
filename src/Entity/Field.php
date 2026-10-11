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

/**
 * An event's value for a custom field
 */
#[ORM\Entity]
#[ORM\Table('fields')]
class Field
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: FieldDefinition::class)]
        #[ORM\JoinColumn(name: 'fid', referencedColumnName: 'fid', nullable: false, onDelete: 'CASCADE')]
        private FieldDefinition $definition,
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'fields')]
        #[ORM\JoinColumn(name: 'eid', referencedColumnName: 'eid', nullable: false, onDelete: 'CASCADE')]
        private Event $event,
        #[ORM\Column(type: 'text')]
        private string $value,
    ) {
    }

    public function getDefinition(): FieldDefinition
    {
        return $this->definition;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}

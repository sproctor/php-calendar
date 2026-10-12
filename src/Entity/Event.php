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

use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table('events')]
class Event
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $eid = null;

    #[ORM\ManyToOne(targetEntity: Calendar::class)]
    #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid', nullable: false, onDelete: 'CASCADE')]
    private Calendar $calendar;

    /**
     * Null for events created anonymously or whose owner was deleted
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_uid', referencedColumnName: 'uid', nullable: true, onDelete: 'SET NULL')]
    private ?User $owner;

    /** @var Collection<int, Occurrence> */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Occurrence::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['start' => \SortDirection::Ascending])]
    private Collection $occurrences;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $ctime;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $mtime = null;

    /**
     * When set, the event is hidden from people who can't edit it until this time
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $pubtime = null;

    /** @var Collection<int, Field> */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Field::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $fields;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'catid', referencedColumnName: 'catid', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    /**
     * Read-only events can only be changed by their owner and calendar admins,
     * not by other users who can modify events
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $readonly = false;

    public function __construct(Calendar $calendar, ?User $owner)
    {
        $this->calendar = $calendar;
        $this->owner = $owner;
        $this->ctime = new DateTimeImmutable();
        $this->occurrences = new ArrayCollection();
        $this->fields = new ArrayCollection();
    }

    public function getEid(): ?int
    {
        return $this->eid;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * Anonymous users never own events
     */
    public function isOwner(?User $user): bool
    {
        return $user !== null && $this->owner !== null && $user->isSameUser($this->owner);
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description ?? '';

        return $this;
    }

    /**
     * @return Collection<int, Occurrence>
     */
    public function getOccurrences(): Collection
    {
        return $this->occurrences;
    }

    public function addOccurrence(Occurrence $occurrence): self
    {
        if (!$this->occurrences->contains($occurrence)) {
            $this->occurrences->add($occurrence);
        }

        return $this;
    }

    public function removeOccurrence(Occurrence $occurrence): self
    {
        $this->occurrences->removeElement($occurrence);

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->category?->getColor();
    }

    public function getTextColor(): ?string
    {
        return $this->category?->getTextColor();
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function setReadonly(bool $readonly): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->ctime;
    }

    public function getModified(): ?DateTimeImmutable
    {
        return $this->mtime;
    }

    /**
     * Sets the creation and modification times, for imported events
     */
    public function setTimestamps(DateTimeImmutable $created, ?DateTimeImmutable $modified): self
    {
        $this->ctime = $created;
        $this->mtime = $modified;

        return $this;
    }

    public function markModified(): self
    {
        $this->mtime = new DateTimeImmutable();

        return $this;
    }

    public function getPubtime(): ?DateTimeImmutable
    {
        return $this->pubtime;
    }

    public function setPubtime(?DateTimeImmutable $pubtime): self
    {
        $this->pubtime = $pubtime;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->pubtime === null || $this->pubtime <= new DateTimeImmutable();
    }

    /**
     * @return Collection<int, Field>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getFieldValue(FieldDefinition $definition): ?string
    {
        foreach ($this->fields as $field) {
            if ($field->getDefinition() === $definition) {
                return $field->getValue();
            }
        }

        return null;
    }

    /**
     * Sets a custom field's value; null or an empty string removes it
     */
    public function setFieldValue(FieldDefinition $definition, ?string $value): self
    {
        foreach ($this->fields as $field) {
            if ($field->getDefinition() === $definition) {
                if ($value === null || $value === '') {
                    $this->fields->removeElement($field);
                } else {
                    $field->setValue($value);
                }

                return $this;
            }
        }
        if ($value !== null && $value !== '') {
            $this->fields->add(new Field($definition, $this, $value));
        }

        return $this;
    }
}

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

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Event represents an event that may have multiple occurrences.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
#[ORM\Entity(repositoryClass: 'App\Repository\EventRepository')]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $eid;

    // TODO: do we really want author and owner separated?
//    #[ORM\ManyToOne(targetEntity: User::class)]
//    #[ORM\JoinColumn(name: 'author_uid', referencedColumnName: 'uid')]
//    private int $author;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Occurrence::class)]
    private Collection $occurrences;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $ctime;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $mtime;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $pubtime = null;

    /**
     * One Event can have many Fields.
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: 'Field')]
    private Collection $fields;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\ManyToOne(targetEntity: 'Category')]
    #[ORM\JoinColumn(name: 'catid', referencedColumnName: 'catid')]
    private ?Category $category;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: 'Calendar')]
        #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid')]
        private Calendar $calendar,
        #[ORM\ManyToOne(targetEntity: 'User')]
        #[ORM\JoinColumn(name: 'owner_uid', referencedColumnName: 'uid')]
        private ?User    $owner,
    )
    {
        $this->ctime = new DateTimeImmutable();
        $this->occurrences = new ArrayCollection();
        $this->fields = new ArrayCollection();
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getEid(): int
    {
        return $this->eid;
    }

    /**
     * @return Occurrence[]
     */
    public function getOccurrences(): Collection
    {
        return $this->occurrences;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getColor(): ?string
    {
        return $this->category?->getColor();
    }

    public function getTextColor(): ?string {
        $color = $this->getColor();

        if ($color === null) return null;

        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
        $l = $this->computeLuminosity($r, $g, $b);

        return $l > 0.179 ? '#000000' : '#ffffff';
    }

    private function computeLuminosity(int $r, int $g, int $b): float {
        return 0.2126 * $this->intermediateLuminosity($r)
            + 0.7152 * $this->intermediateLuminosity($g)
            + 0.0722 * $this->intermediateLuminosity($b);
    }

    private function intermediateLuminosity(int $c): float {
        $sc = ($c / 255.0);
        return $sc <= 0.03928 ? $sc / 12.92 : (($sc + 0.055) / 1.055) ^ 2.4;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function isOwner(?User $user): bool
    {
        if ($this->owner === null) return true;
        return $user?->getUid() == $this->owner->getUid();
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->ctime;
    }

    public function getModified(): ?DateTimeInterface
    {
        return $this->mtime;
    }

    public function getPublishDate(): ?DateTimeInterface
    {
        return $this->pubtime;
    }

    public function isPublished(): bool
    {
        return $this->pubtime == null || $this->pubtime <= new DateTimeImmutable();
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCtime(): ?DateTimeInterface
    {
        return $this->ctime;
    }

    public function setCtime(DateTimeInterface $ctime): self
    {
        $this->ctime = $ctime;

        return $this;
    }

    public function getMtime(): ?DateTimeInterface
    {
        return $this->mtime;
    }

    public function setMtime(DateTimeInterface $mtime): self
    {
        $this->mtime = $mtime;

        return $this;
    }

    public function getPubtime(): ?DateTimeInterface
    {
        return $this->pubtime;
    }

    public function setPubtime(?DateTimeInterface $pubtime): self
    {
        $this->pubtime = $pubtime;

        return $this;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function addOccurrence(Occurrence $occurrence): self
    {
        if (!$this->occurrences->contains($occurrence)) {
            $this->occurrences->add($occurrence);
            $occurrence->setEvent($this);
        }

        return $this;
    }

    public function removeOccurrence(Occurrence $occurrence): self
    {
        if ($this->occurrences->removeElement($occurrence)) {
            // set the owning side to null (unless already changed)
            if ($occurrence->getEvent() === $this) {
                $occurrence->setEvent(null);
            }
        }

        return $this;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function setCalendar(?Calendar $calendar): self
    {
        $this->calendar = $calendar;

        return $this;
    }

    public function addField(Field $field): self
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setEvent($this);
        }

        return $this;
    }

    public function removeField(Field $field): self
    {
        if ($this->fields->removeElement($field)) {
            // set the owning side to null (unless already changed)
            if ($field->getEvent() === $this) {
                $field->setEvent(null);
            }
        }

        return $this;
    }
}

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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Occurrence;

/**
 * Event represents an event that may have multiple occurrences.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 * @ORM\Entity
 * @ORM\Table(name="events")
 */
class Event
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $eid;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="owner_uid", referencedColumnName="uid")
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="author_uid", referencedColumnName="uid")
     */
    private $author;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Occurrence", mappedBy="event")
     * @var Occurrence[] An ArrayCollection of Occurrence objects.
     */
    private $occurrences;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="catid", referencedColumnName="catid")
     */
    private $category;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ctime;

    /**
     * @ORM\Column(type="datetime")
     */
    private $mtime;

    /**
     * @ORM\Column(type="datetime")
     */
    private $pubtime;

    /**
     * @ORM\ManyToOne(targetEntity="Calendar")
     * @ORM\JoinColumn(name="cid", referencedColumnName="cid")
     */
    private $calendar;

    /**
     * One Event can have many Fields.
     * @ORM\OneToMany(targetEntity="Field", mappedBy="event")
     */
    private $fields;

    /**
     * @param Calendar      $calendar
     * @param User          $user
     * @param string        $subject
     * @param string        $description
     * @param Category|null $catid
     * @param \DateTimeInterface|null $publish_date
     * @return int
     */
    public function __construct(
        Calendar $calendar,
        User $user,
        string $subject,
        string $description,
        Category $category,
        DateTimeInterface $publish_date
    ) {
        $this->calendar = $calendar;
        $this->owner = $user;
        $this->author = $author;
        $this->subject = $subject;
        $this->description = $description;
        $this->category = $category;
        $this->pubtime = $publish_date;
        $this->occurrences = new ArrayCollection();
        $this->fields = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getRawSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        if (empty($this->subject)) {
            return __('nonexistent-subject');
        }

        return $this->subject;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * @return Occurrence[]
     */
    public function getOccurrences()
    {
        return $this->db->getOccurrences($this->eid);
    }

    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * @return null|string
     */
    public function getTextColor()
    {
        return $this->text_color;
    }

    /**
     * @return null|string
     */
    public function getBgColor()
    {
        return $this->bg_color;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        if (empty($this->category)) {
            return $this->category;
        }
        return $this->category;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isOwner(User $user)
    {
        return $user->getUid() == $this->owner_uid;
    }

    /**
     * Returns whether or not the current user can modify $event
     *
     * @param User $user
     * @return bool
     */
    public function canModify(User $user)
    {
        return $this->calendar->canAdmin($user) || $this->isOwner($user)
            || ($this->calendar->canModify($user));
    }

    /**
     * Returns whether or not the user can read this event
     *
     * @param User $user
     * @return bool
     */
    public function canRead(User $user)
    {
        $visible_category = empty($this->gid) || !isset($this->catid)
            || $this->db->isCategoryVisible($user, $this->catid);
        return ($this->isPublished() || $this->isOwner($user)) && $this->calendar->canRead($user) && $visible_category;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        if (!isset($this->fields)) {
            $this->fields = $this->db->getEventFields($this->eid);
        }

        return $this->fields;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreated()
    {
        return $this->ctime;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getModified()
    {
        return $this->mtime;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPublishDate()
    {
        return $this->pubtime;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->pubtime == null || $this->pubtime <= new \DateTime();
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

    public function getCtime(): ?\DateTimeInterface
    {
        return $this->ctime;
    }

    public function setCtime(\DateTimeInterface $ctime): self
    {
        $this->ctime = $ctime;

        return $this;
    }

    public function getMtime(): ?\DateTimeInterface
    {
        return $this->mtime;
    }

    public function setMtime(\DateTimeInterface $mtime): self
    {
        $this->mtime = $mtime;

        return $this;
    }

    public function getPubtime(): ?\DateTimeInterface
    {
        return $this->pubtime;
    }

    public function setPubtime(\DateTimeInterface $pubtime): self
    {
        $this->pubtime = $pubtime;

        return $this;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

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

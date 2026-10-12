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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A custom field that events on a calendar can fill in
 */
#[ORM\Entity]
#[ORM\Table('field_definitions')]
class FieldDefinition
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $fid = null;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'field_definitions')]
    #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid', nullable: false, onDelete: 'CASCADE')]
    private Calendar $calendar;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[ORM\Column(type: 'boolean')]
    private bool $is_required = false;

    /**
     * Optional regular expression (without delimiters) that values must match
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Callback([self::class, 'validateFormat'])]
    private ?string $format = null;

    public function __construct(Calendar $calendar)
    {
        $this->calendar = $calendar;
        $calendar->getFieldDefinitions()->add($this);
    }

    public function getFid(): ?int
    {
        return $this->fid;
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

    public function isRequired(): bool
    {
        return $this->is_required;
    }

    public function setIsRequired(bool $is_required): self
    {
        $this->is_required = $is_required;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format === '' ? null : $format;

        return $this;
    }

    /**
     * The format as a PCRE pattern matching the whole value, or null when any value is allowed
     */
    public function getPattern(): ?string
    {
        if ($this->format === null) {
            return null;
        }

        return '/^(?:' . str_replace('/', '\/', $this->format) . ')$/u';
    }

    public static function validateFormat(?string $format, ExecutionContextInterface $context): void
    {
        if ($format === null || $format === '') {
            return;
        }
        $pattern = '/^(?:' . str_replace('/', '\/', $format) . ')$/u';
        if (@preg_match($pattern, '') === false) {
            $context->buildViolation('invalid-field-format-error')->addViolation();
        }
    }
}

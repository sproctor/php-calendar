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

use App\Repository\OccurrenceRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use IntlDateFormatter;
use Locale;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

#[ORM\Entity(repositoryClass: OccurrenceRepository::class)]
#[ORM\Table(name: 'occurrences')]
class Occurrence
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $oid;

    /**
     * Occurrence constructor.
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'occurrences')]
        #[ORM\JoinColumn(name: 'eid', referencedColumnName: 'eid', onDelete: 'CASCADE')]
        private Event $event,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $start,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $end,
        #[ORM\Column(type: 'integer')]
        private int $time_type
    )
    {
    }

    /**
     * Formats the start and end time according to time_type and locale
     */
    public function getTimespanString(): ?TranslatableInterface
    {
        switch ($this->time_type) {
            default:
                $formatter = new IntlDateFormatter(
                    Locale::getDefault(),
                    IntlDateFormatter::NONE,
                    IntlDateFormatter::SHORT
                );
                return new TranslatableMessage(
                    'time-range',
                    ['%start%' => $formatter->format($this->start), '%end%' => $formatter->format($this->end)]
                );
            case 1: // FULL DAY
            case 3: // None
                return null;
            case 2:
                return new TranslatableMessage('to-be-announced-abbr');
        }
    }
    
    public function getDatetimeString(): TranslatableInterface
    {
        if ($this->time_type != 0 || days_between($this->start, $this->end) == 0) {
            // normal behaviour
            $date_formatter = new IntlDateFormatter(
                Locale::getDefault(),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::NONE
            );
            $date = $date_formatter->format($this->start);
            switch ($this->time_type) {
                case 0:
                    $time_formatter = new IntlDateFormatter(
                        Locale::getDefault(),
                        IntlDateFormatter::NONE,
                        IntlDateFormatter::SHORT
                    );
                    return new TranslatableMessage(
                        'date-at-time-range',
                        [
                            '%date%' => $date,
                            '%start%' => $time_formatter->format($this->start),
                            '%end%' => $time_formatter->format($this->end),
                        ]
                    );
                case 2: // TBA
                    return new TranslatableMessage('date-at-time-to-be-announced-abbr', ['%date%' => $date]);
                default: // FULL DAY or none
                    return new TranslatableMessage('passthrough', ['%value%' => $date]);
            }
        } else {
            // format on multiple days
            $formatter = new IntlDateFormatter(
                Locale::getDefault(),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::SHORT
            );
            return new TranslatableMessage(
                'date-time-range',
                ['%start%' => $formatter->format($this->start), '%end%' => $formatter->format($this->end)]
            );
        }
    }

    public function getTimeString()
    {
        switch ($this->time_type) {
            default:
                $formatter = new IntlDateFormatter(
                    Locale::getDefault(),
                    IntlDateFormatter::NONE,
                    IntlDateFormatter::SHORT
                );
                return $formatter->format($this->start);
            case 1: // FULL DAY
            case 3: // None
                return null;
            case 2:
                return new TranslatableMessage('to-be-announced-abbr');
        }
    }
    public function getOid(): int
    {
        return $this->oid;
    }

    public function getTimeType(): int
    {
        return $this->time_type;
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): DateTimeImmutable
    {
        return $this->end;
    }

    public function setStart(DateTimeImmutable $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function setEnd(DateTimeImmutable $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function setTimeType(int $time_type): self
    {
        $this->time_type = $time_type;

        return $this;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getTextColor(): ?string {
        return null;
    }

    public function getBgColor(): ?string {
        return null;
    }
}

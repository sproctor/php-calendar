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
use Doctrine\DBAL\Types\Types;
use IntlDateFormatter;
use Locale;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OccurrenceRepository::class)]
#[ORM\Table(name: 'occurrences')]
class Occurrence
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $oid;

    /**
     * Occurrence constructor.
     */
    public function __construct(#[ORM\ManyToOne(targetEntity: 'Event', inversedBy: 'occurrences')]
    #[ORM\JoinColumn(name: 'eid', referencedColumnName: 'eid')]
    private Event $event, #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $start, #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $end, #[ORM\Column(type: 'integer')]
    private int $time_type)
    {
    }

    /**
     * Formats the start and end time according to time_type and locale
     */
    public function getTimespanString(): ?string
    {
        switch ($this->time_type) {
            default:
                $formatter = new IntlDateFormatter(
                    Locale::getDefault(),
                    IntlDateFormatter::NONE,
                    IntlDateFormatter::SHORT
                );
                return __(
                    'time-range',
                    ['%start%' => $formatter->format($this->start), '%end%' => $formatter->format($this->end)]
                );
            case 1: // FULL DAY
            case 3: // None
                return null;
            case 2:
                return __('to-be-announced-abbr');
        }
    }
    
    public function getDatetimeString(): string
    {
        if ($this->time_type != 0 || days_between($this->start, $this->end) == 0) {
            // normal behaviour
            $formatter = new IntlDateFormatter(
                Locale::getDefault(),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::NONE
            );
            $event_time = $this->getTimespanString();
            $date = $formatter->format($this->start);
            if ($event_time != null) {
                $str = __('date-time-custom', ['%date%' => $date, '%time%' => $event_time]);
            } else {
                $str = $date;
            }
        } else {
            // format on multiple days
            $formatter = new IntlDateFormatter(
                Locale::getDefault(),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::SHORT
            );
            $str = __(
                'date-time-range',
                ['%start%' => $formatter->format($this->start), '%end%' => $formatter->format($this->end)]
            );
        }
        return $str;
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
                return __('to-be-announced-abbr');
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

    public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    public function getEnd(): DateTimeInterface
    {
        return $this->end;
    }

    /**
     * @param EntityManager $entityManager
     */
    public static function findOccurrences(
        EntityManager $entityManager,
        DateTimeInterface $from,
        DateTimeInterface $to
    ) {
        $qb = $entityManager->createQueryBuilder();

        //$qb->select('e')
            //->from('Event', 'e')
            //->where('e.
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function setTimeType(int $time_type): self
    {
        $this->time_type = $time_type;

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

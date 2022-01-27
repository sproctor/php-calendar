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

namespace PhpCalendar\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use Locale;

/**
 * @Entity @Table(name="occurrences")
 */
class Occurrence
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $oid;

    /**
     * @ManyToOne(targetEntity="Event", inversedBy="occurrences")
     * @JoinColumn(name="eid", referencedColumnName="eid")
     */
    private $event;

    /**
     * @Column(type="datetime")
     */
    private $start;

    /**
     * @Column(type="datetime")
     */
    private $end;

    /**
     * @Column(type="integer")
     */
    private $time_type;

    /**
     * Occurrence constructor.
     *
     * @param Event                 $event
     * @param DateTimeInterface    $start
     * @param DateTimeInterface    $end
     * @param int                   $time_type
     */
    public function __construct(Event $event, DateTimeInterface $start, DateTimeInterface $end, int $time_type)
    {
        $this->event = $event;
        $this->start = $start;
        $this->end = $end;
        $this->time_type = $time_type;
    }

    /**
     * Formats the start and end time according to time_type and locale
     * @return null|string
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
                    array('%start%' => $formatter->format($this->start), '%end%' => $formatter->format($this->end))
                );
            case 1: // FULL DAY
            case 3: // None
                return null;
            case 2:
                return __('to-be-announced-abbr');
        }
    }
    
    /**
     * @return string
     */
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
                $str = __('date-time-custom', array('%date%' => $date, '%time%' => $event_time));
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
                array('%start%' => $formatter->format($this->start), '%end%' => $formatter->format($this->end))
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
    /**
     * @return int
     */
    public function getOid(): int
    {
        return $this->oid;
    }

    /**
     * @return int
     */
    public function getTimeType(): int
    {
        return $this->time_type;
    }

    /**
     * @return DateTimeInterface
     */
    public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    /**
     * @return DateTimeInterface
     */
    public function getEnd(): DateTimeInterface
    {
        return $this->end;
    }

    /**
     * @param EntityManager $entityManager
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
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
}

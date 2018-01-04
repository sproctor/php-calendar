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

namespace PhpCalendar;

class Occurrence extends Event
{
    private $oid;
    private $start;
    private $end;
    private $time_type;

    /**
     * Occurrence constructor.
     *
     * @param Database $db
     * @param string[] $row
     */
    public function __construct(Database $db, $row)
    {
        parent::__construct($db, $row);

        $this->oid = intval($row['oid']);
        $this->start = datetime_from_sql_date($row['start']);
        $this->end = datetime_from_sql_date($row['end']);
        $this->time_type = intval($row['time_type']);
    }

    /**
     * Formats the start and end time according to time_type and locale
     * @return null|string
     */
    public function getTimespanString()
    {
        switch ($this->time_type) {
            default:
                $formatter = new \IntlDateFormatter(
                    \Locale::getDefault(),
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::SHORT
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
    public function getDatetimeString()
    {
        if ($this->time_type != 0 || days_between($this->start, $this->end) == 0) {
            // normal behaviour
            $formatter = new \IntlDateFormatter(
                \Locale::getDefault(),
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::NONE
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
            $formatter = new \IntlDateFormatter(
                \Locale::getDefault(),
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::SHORT
            );
            $str = __(
                'date-time-range',
                array('%start%' => $formatter->format($this->start), '%end%' => $formatter->format($this->end))
            );
        }
        return $str;
    }

    /**
     * @return int
     */
    public function getOid()
    {
        return $this->oid;
    }

    /**
     * @return int
     */
    public function getTimeType()
    {
        return $this->time_type;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEnd()
    {
        return $this->end;
    }
}

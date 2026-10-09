<?php

/*
 * Copyright 2012 Sean Proctor
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

require_once("$phpc_includes_path/phpcevent.class.php");

class PhpcOccurrence extends PhpcEvent
{
    /** @var int */
    public $oid;
    public $start_year;
    public $start_month;
    public $start_day;
    public $end_year;
    public $end_month;
    public $end_day;
    public $time_type;
    public $start_hour = null;
    public $start_minute = null;
    public $end_hour = null;
    public $end_minute = null;
    public $duration;

    public function __construct($event)
    {
        parent::__construct($event);

        $this->oid = intval($event['oid']);

        if (!empty($event['start_ts'])) {
            $start_ts = $event['start_ts'];
            $this->start_year = date('Y', $start_ts);
            $this->start_month = date('n', $start_ts);
            $this->start_day = date('j', $start_ts);
            $this->start_hour = date('H', $start_ts);
            $this->start_minute = date('i', $start_ts);
        }

        if (!empty($event['start_date'])) {
            if (preg_match(
                '/^(\d{4})(\d{2})(\d{2})$/',
                $event['start_date'],
                $start_matches
            ) < 1) {
                soft_error(__('DB returned an invalid date.')
                        . "({$event['start_date']})");
            }
            $this->start_year = $start_matches[1];
            $this->start_month = $start_matches[2];
            $this->start_day = $start_matches[3];
        }

        if (!empty($event['end_ts'])) {
            $end_ts = $event['end_ts'];
            $this->end_year = date('Y', $end_ts);
            $this->end_month = date('n', $end_ts);
            $this->end_day = date('j', $end_ts);
            $this->end_hour = date('H', $end_ts);
            $this->end_minute = date('i', $end_ts);
        }

        if (!empty($event['end_date'])) {
            if (preg_match(
                '/^(\d{4})(\d{2})(\d{2})$/',
                $event['end_date'],
                $end_matches
            ) < 1) {
                soft_error(__('DB returned an invalid date.')
                        . "({$event['start_date']})");
            }
            $this->end_year = $end_matches[1];
            $this->end_month = $end_matches[2];
            $this->end_day = $end_matches[3];
        }

        $this->time_type = $event['time_type'];

        if (!empty($event['end_ts'])) {

            $this->duration = $event['end_ts'] - $event['start_ts'];
        }
    }

    // formats the time according to type
    // returns the formatted string
    public function get_time_string()
    {
        switch ($this->time_type) {
            default:
                return $this->get_start_time();
            case 1: // FULL DAY
            case 3: // None
                return '';
            case 2:
                return __('TBA');
        }
    }

    public function get_time_span_string()
    {
        switch ($this->time_type) {
            default:
                $start_time = $this->get_start_time();
                $end_time = $this->get_end_time();
                return $start_time.' '.__('to').' '.$end_time;
            case 1: // FULL DAY
            case 3: // None
                return '';
            case 2:
                return __('TBA');
        }
    }

    public function get_start_year()
    {
        return $this->start_year;
    }

    public function get_start_month()
    {
        return $this->start_month;
    }

    public function get_start_day()
    {
        return $this->start_day;
    }

    public function get_end_year()
    {
        return $this->end_year;
    }

    public function get_end_month()
    {
        return $this->end_month;
    }

    public function get_end_day()
    {
        return $this->end_day;
    }

    public function get_start_hour()
    {
        return $this->start_hour;
    }

    public function get_start_minute()
    {
        return $this->start_minute;
    }

    public function get_end_hour()
    {
        return $this->end_hour;
    }

    public function get_end_minute()
    {
        return $this->end_minute;
    }

    public function get_start_date()
    {
        return format_date_string(
            $this->start_year,
            $this->start_month,
            $this->start_day,
            $this->cal->date_format
        );
    }

    public function get_short_start_date()
    {
        return format_short_date_string(
            $this->start_year,
            $this->start_month,
            $this->start_day,
            $this->cal->date_format
        );
    }

    public function get_start_time()
    {
        if ($this->start_hour == null || $this->start_minute == null) {
            return null;
        }

        return format_time_string(
            $this->start_hour,
            $this->start_minute,
            $this->cal->hours_24
        );
    }

    public function get_end_date()
    {
        return format_date_string(
            $this->end_year,
            $this->end_month,
            $this->end_day,
            $this->cal->date_format
        );
    }

    public function get_short_end_date()
    {
        return format_short_date_string(
            $this->end_year,
            $this->end_month,
            $this->end_day,
            $this->cal->date_format
        );
    }

    public function get_end_time()
    {
        if ($this->end_hour == null || $this->end_minute == null) {
            return null;
        }

        return format_time_string(
            $this->end_hour,
            $this->end_minute,
            $this->cal->hours_24
        );
    }

    public function get_start_timestamp()
    {
        return mktime(
            0,
            0,
            0,
            $this->get_start_month(),
            $this->get_start_day(),
            $this->get_start_year()
        );
    }

    public function get_end_timestamp()
    {
        $hour = $this->end_hour ? $this->end_hour : 23;
        $minute = $this->end_minute ? $this->end_minute : 59;
        return mktime(
            $hour,
            $minute,
            59,
            $this->get_end_month(),
            $this->get_end_day(),
            $this->get_end_year()
        );
    }

    // takes start and end dates and returns a nice display
    public function get_date_string()
    {
        $start_time = $this->get_start_timestamp();
        $end_time = $this->get_end_timestamp();

        $str = $this->get_start_date();

        if ($start_time != $end_time) {
            $str .= ' - ' . $this->get_end_date();
        }

        return $str;
    }

    public function get_datetime_string()
    {
        if ($this->duration <= 86400 && $this->start_day == $this->end_day) {
            //normal behaviour

            $event_time = $this->get_time_span_string();
            if (!empty($event_time)) {
                $event_time = ' ' . __('at') . " $event_time";
            }

            $str = $this->get_date_string() . $event_time;
        } else {
            //format on multiple days

            $str = ' ' . __('From') . ' ' . $this->get_start_date()
                . ' ' .	__('at') . ' ' . $this->get_start_time()
                . ' ' . __('to') . ' ' . $this->get_end_date()
                . ' ' . __('at') . ' ' . $this->get_end_time();

        }
        return $str;
    }

    public function get_oid()
    {
        return $this->oid;
    }

    public function get_time_type()
    {
        return $this->time_type;
    }

    public function get_start_ts()
    {
        $start_hour = $this->start_hour;
        if ($start_hour == null) {
            $start_hour = 0;
        }
        $start_minute = $this->start_minute;
        if ($start_minute == null);
        $start_minute = 0;
        return mktime(
            $start_hour,
            $start_minute,
            0,
            $this->start_month,
            $this->start_day,
            $this->start_year
        );
    }
}

<?php
/*
 * Copyright 2010 Sean Proctor
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

class PhpcOccurrence extends PhpcEvent{
	var $oid;
	var $start_year;
	var $start_month;
	var $start_day;
	var $end_year;
	var $end_month;
	var $end_day;
	var $time_type;
	var $start_hour;
	var $start_minute;
	var $end_hour;
	var $end_minute;

	function __construct($event)
	{
		parent::__construct($event);

		$this->oid = $event['oid'];

		if(!empty($event['start_ts'])) {
			$start_ts = $event['start_ts'];
			$this->start_year = date('Y', $start_ts);
			$this->start_month = date('n', $start_ts);
			$this->start_day = date('j', $start_ts);
			$this->start_hour = date('H', $start_ts);
			$this->start_minute = date('i', $start_ts);
		}

		if(!empty($event['start_date'])) {
			if(preg_match('/^(\d{4})(\d{2})(\d{2})$/',
						$event['start_date'],
						$start_matches) < 1) {
				soft_error(_('DB returned an invalid date.')
						. "({$event['start_date']})");
			}
			$this->start_year = $start_matches[1];
			$this->start_month = $start_matches[2];
			$this->start_day = $start_matches[3];
			$this->start_hour = 0;
			$this->start_minute = 0;
		}

		if(!empty($event['end_ts'])) {
			$end_ts = $event['end_ts'];
			$this->end_year = date('Y', $end_ts);
			$this->end_month = date('n', $end_ts);
			$this->end_day = date('j', $end_ts);
			$this->end_hour = date('H', $end_ts);
			$this->end_minute = date('i', $end_ts);
		}

		if(!empty($event['end_date'])) {
			if(preg_match('/^(\d{4})(\d{2})(\d{2})$/',
						$event['end_date'],
						$end_matches) < 1) {
				soft_error(_('DB returned an invalid date.')
						. "({$event['start_date']})");
			}
			$this->end_year = $end_matches[1];
			$this->end_month = $end_matches[2];
			$this->end_day = $end_matches[3];
			$this->end_hour = 0;
			$this->end_minute = 0;
		}
		
		$this->time_type = $event['time_type'];

		//echo "<pre>start time: {$event['start_date']}, {$this->start_year} {$this->start_month} {$this->start_day} {$this->start_hour}:{$this->start_minute}, end time: {$event['end_date']}, {$this->end_year} {$this->end_month} {$this->end_day} {$this->end_hour}:{$this->end_minute}, oid: {$this->oid}</pre>";
	}

	// formats the time according to type
	// returns the formatted string
	function get_time_string()
	{
		switch($this->time_type) {
			default:
				return format_time_string($this->start_hour,
						$this->start_minute, get_config(
							$this->cid,
							'hours_24'));
			case 1:
				return _('FULL DAY');
			case 2:
				return _('TBA');
			case 3:
				return '';
		}
	}

	function get_time_span_string()
	{
		switch($this->time_type) {
			default:
				$hour24 = get_config($this->cid, 'hours_24');
				$start_time = format_time_string($this->start_hour,
						$this->start_minute, $hour24);
				$end_time = format_time_string($this->end_hour,
						$this->end_minute, $hour24);
				return $start_time.' '._('to').' '.$end_time;
			case 1:
				return _('FULL DAY');
			case 2:
				return _('TBA');
			case 3:
				return '';
		}
	}
		
	function get_start_year()
	{
		return $this->start_year;
	}

	function get_start_month()
	{
		return $this->start_month;
	}

	function get_start_day()
	{
		return $this->start_day;
	}

	function get_end_year()
	{
		return $this->end_year;
	}

	function get_end_month()
	{
		return $this->end_month;
	}

	function get_end_day()
	{
		return $this->end_day;
	}

	function get_start_hour()
	{
                return $this->start_hour;
	}

	function get_start_minute()
	{
		return $this->start_minute;
	}

	function get_end_hour()
	{
                return $this->end_hour;
	}

	function get_end_minute()
	{
		return $this->end_minute;
	}

	function get_start_time() {
		return mktime(0, 0, 0, $this->get_start_month(),
				$this->get_start_day(),
				$this->get_start_year());
	}

	function get_end_time() {
		return mktime(0, 0, 0, $this->get_end_month(),
				$this->get_end_day(), $this->get_end_year());
	}

	// takes start and end dates and returns a nice display
	function get_date_string()
	{
		global $phpc_datefmt;

		$start_time = $this->get_start_time();
		$end_time = $this->get_end_time();

		$str = sprintf(date($phpc_datefmt, $start_time),
				short_month_name($this->get_start_month()));

		if($start_time != $end_time) {
			$str .= ' - ' . sprintf(date($phpc_datefmt, $end_time),
					short_month_name($this->get_end_month()));
		}

		return $str;
	}

	function get_oid()
	{
		return $this->oid;
	}

	function get_time_type() {
		return $this->time_type;
	}

	function get_start_ts() {
		return mktime($this->start_hour, $this->start_minute, 0,
				$this->start_month, $this->start_day,
				$this->start_year);
	}

	function get_end_ts() {
		return mktime($this->end_hour, $this->end_minute, 0,
				$this->end_month, $this->end_day,
				$this->end_year);
	}
}

?>

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
	var $duration;

	function __construct($event) {
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
				soft_error(__('DB returned an invalid date.')
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
				soft_error(__('DB returned an invalid date.')
						. "({$event['start_date']})");
			}
			$this->end_year = $end_matches[1];
			$this->end_month = $end_matches[2];
			$this->end_day = $end_matches[3];
			$this->end_hour = 0;
			$this->end_minute = 0;
		}
		
		$this->time_type = $event['time_type'];
		
		if(!empty($event['end_ts'])) {
		
		$this->duration=$event['end_ts'] - $event['start_ts'];
		}
	}

	// formats the time according to type
	// returns the formatted string
	function get_time_string()
	{
		switch($this->time_type) {
			default:
				return $this->get_start_time();
			case 1: // FULL DAY
			case 3: // None
				return '';
			case 2:
				return __('TBA');
		}
	}

	function get_time_span_string()
	{
		switch($this->time_type) {
			default:
				$hour24 = $this->cal->hours_24;
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

	function get_start_date() {
		return format_date_string($this->start_year, $this->start_month,
				$this->start_day,
				$this->cal->date_format);
	}

	function get_short_start_date() {
		return format_short_date_string($this->start_year,
				$this->start_month, $this->start_day,
				$this->cal->date_format);
	}

	function get_start_time() {
		return format_time_string($this->start_hour,
				$this->start_minute,
				$this->cal->hours_24);
	}

	function get_end_date() {
		return format_date_string($this->end_year, $this->end_month,
				$this->end_day,
				$this->cal->date_format);
	}

	function get_short_end_date() {
		return format_short_date_string($this->end_year,
				$this->end_month, $this->end_day,
				$this->cal->date_format);
	}

	function get_end_time() {
		return format_time_string($this->end_hour,
				$this->end_minute,
				$this->cal->hours_24);
	}

	function get_start_timestamp() {
		return mktime(0, 0, 0, $this->get_start_month(),
				$this->get_start_day(),
				$this->get_start_year());
	}

	function get_end_timestamp() {
		return mktime(0, 0, 0, $this->get_end_month(),
				$this->get_end_day(), $this->get_end_year());
	}

	// takes start and end dates and returns a nice display
	function get_date_string()
	{
		$start_time = $this->get_start_timestamp();
		$end_time = $this->get_end_timestamp();

		$str = $this->get_start_date();

		if($start_time != $end_time)
			$str .= ' - ' . $this->get_end_date();

		return $str;
	}
	
	function get_datetime_string()
	{
		if ($this->duration <= 86400 && $this->start_day==$this->end_day)
		{
		//normal behaviour
		
		$event_time = $this->get_time_span_string();
		if(!empty($event_time))
			$event_time = ' ' . __('at') . " $event_time";
	
		$str= $this->get_date_string() . $event_time;	
		}
		else
		{
		//format on multiple days
	
			$str = ' ' . __('From') . ' ' . $this->get_start_date()
				. ' ' .	__('at') . ' ' . $this->get_start_time()
				. ' ' . __('to') . ' ' . $this->get_end_date()
				. ' ' . __('at') . ' ' . $this->get_end_time();	
			
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

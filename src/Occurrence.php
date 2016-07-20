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

namespace PhpCalendar;

class Occurrence extends Event
{
	private $oid;
	private $start;
	private $end;
	private $time_type;

	/**
	 * Occurrence constructor.
	 * @param Database $db
	 * @param \string[] $event
	 */
	function __construct(Database $db, $row) {
		parent::__construct($db, $row);

		$this->oid = $event['oid'];
		$this->start = fromSqlDateImmutable($row['start']);
		$this->end = fromSqlDateImmutable($row['end']);
		$this->time_type = $event['time_type'];
	}

	/**
	 * formats the time according to type
	 * @return NULL|string
	 */
	function get_time_string()
	{
		switch($this->time_type) {
			default:
				return format_time($this->start, $this->cal->hours_24);
			case 1: // FULL DAY
			case 3: // None
				return null;
			case 2:
				return __('TBA');
		}
	}

	/**
	 * @return NULL|string
	 */
	function getTimespanString()
	{
		switch($this->time_type) {
			default:
				$hour24 = $this->cal->hours_24;
				$start_time = $this->get_start_time();
				$end_time = $this->get_end_time();
				return $start_time.' '.__('to').' '.$end_time;
			case 1: // FULL DAY
			case 3: // None
				return null;
			case 2:
				return __('TBA');
		}
	}

	// takes start and end dates and returns a nice display
	function getDateString()
	{
		$start_time = $this->get_start_timestamp();
		$end_time = $this->get_end_timestamp();

		$str = $this->get_start_date();

		if($start_time != $end_time)
			$str .= ' - ' . $this->get_end_date();

		return $str;
	}
	
	function getDatetimeString()
	{
		if (days_between($this->start, $this->end) == 0) {
			// normal behaviour
			$str = $this->getDateString();
			$event_time = $this->getTimespanString();
			if (! empty ( $event_time ))
				$str .= ' ' . __ ( 'at' ) . " $event_time";
			
		} else {
			// format on multiple days
			$str = ' ' . __ ( 'From' ) . ' '
					. format_datetime($this->start, $this->cal->date_format, $this->cal->hours_24) . ' '
					. __ ( 'to' ) . ' '
					. format_datetime($this->end, $this->cal->date_format, $this->cal->hours_24);
		}
		return $str;
	}

	function getOid()
	{
		return $this->oid;
	}

	function getTimeType() {
		return $this->time_type;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	function getStart() {
		return $this->start;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	function getEnd() {
		return $this->end;
	}
}

?>

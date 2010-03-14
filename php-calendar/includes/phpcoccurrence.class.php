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

class PhpcOccurrence {
	var $oid;
	var $eid;
	var $cid;
	var $uid;
	var $username;
	var $startyear;
	var $startmonth;
	var $startday;
	var $endyear;
	var $endmonth;
	var $endday;
	var $timetype;
	var $hour;
	var $minute;
	var $end_hour;
	var $end_minute;
	var $subject;
	var $desc;
	var $readonly;
	var $category;
	var $bg_color;
	var $text_color;
	var $catid;

	function PhpcOccurrence($event)
	{
		$this->oid = $event['oid'];
		$this->eid = $event['eid'];
		$this->cid = $event['cid'];
		$this->uid = $event['owner'];
		if(empty($event['owner']))
			$this->username = _('anonymous');
		elseif(empty($event['username']))
			$this->username = _('unknown');
		else
			$this->username = $event['username'];
		$this->subject = $event['subject'];
		$this->desc = $event['description'];
		$this->readonly = $event['readonly'];
		$this->category = $event['name'];
		$this->bg_color = $event['bg_color'];
		$this->text_color = $event['text_color'];
		$this->catid = $event['catid'];

		$this->startyear = $event['startyear'];
		$this->startmonth = $event['startmonth'];
		$this->startday = $event['startday'];
		$this->endyear = $event['endyear'];
		$this->endmonth = $event['endmonth'];
		$this->endday = $event['endday'];

		$this->timetype = $event['timetype'];
		$this->hour = $event['starthour'];
		$this->minute = $event['startminute'];
		$this->end_hour = $event['endhour'];
		$this->end_minute = $event['endminute'];
	}

	function get_subject()
	{
		if(empty($this->subject))
			return _('(No subject)');

		return htmlspecialchars(strip_tags(stripslashes(
						$this->subject)));
	}

	// formats the time according to type
	// returns the formatted string
	function get_time_string()
	{
		switch($this->timetype) {
			default:
				return format_time_string($this->hour,
						$this->minute, get_config(
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
		switch($this->timetype) {
			default:
				$hour24 = get_config($this->cid, 'hours_24');
				$start_time = format_time_string($this->hour,
						$this->minute, $hour24);
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
		
	function get_year()
	{
		return $this->startyear;
	}

	function get_month()
	{
		return $this->startmonth;
	}

	function get_day()
	{
		return $this->startday;
	}

	function get_endyear()
	{
		return $this->endyear;
	}

	function get_endmonth()
	{
		return $this->endmonth;
	}

	function get_endday()
	{
		return $this->endday;
	}

	function get_hour()
	{
                return $this->hour;
	}

	function get_minute()
	{
		return $this->minute;
	}

	function get_start_time() {
		return mktime(0, 0, 0, $this->get_month(), $this->get_day(),
				$this->get_year());
	}

	function get_end_time() {
		return mktime(0, 0, 0, $this->get_endmonth(),
				$this->get_endday(), $this->get_endyear());
	}

	// takes start and end dates and returns a nice display
	function get_date_string()
	{
		global $phpc_datefmt;

		$start_time = $this->get_start_time();
		$end_time = $this->get_end_time();

		$str = sprintf(date($phpc_datefmt, $start_time),
				short_month_name($this->get_month()));

		if($start_time != $end_time) {
			$str .= ' - ' . sprintf(date($phpc_datefmt, $end_time),
					short_month_name($this->get_endmonth()));
		}

		return $str;
	}

	function get_username()
	{
		return $this->username;
	}

	function get_uid()
	{
		return $this->uid;
	}

	function get_desc()
	{
		return $this->desc;
		return parse_desc($this->desc);
	}

	function get_raw_desc()
	{
		return $this->desc;
	}

	function get_oid()
	{
		return $this->oid;
	}

	function get_eid()
	{
		return $this->eid;
	}

	function get_cid()
	{
		return $this->cid;
	}

	function is_readonly()
	{
		return $this->readonly;
	}
}

?>

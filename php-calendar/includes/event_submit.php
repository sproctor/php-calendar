<?php
/*
 * Copyright 2009 Sean Proctor
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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function event_submit()
{
	global $vars, $phpcdb, $phpcid;

	$potential_fields = array(
			"subject",
			"description",
			"eventid",
			"time-type",
			"repeats",
			"readonly",
			);

	$arguments = array();
	foreach($potential_fields as $field) {
		if(isset($vars[$field])) {
			$arguments[$field] = $vars[$field];
		}
	}

	$startdate = get_date("start");
	$enddate = get_date("end");

	$starttime = NULL;
	$endtime = NULL;
	switch($vars["time-type"]) {
		case 'normal':
			$starttime = get_time("start");
			$endtime = get_time("end");
			$timetype = 0;
			break;
		case 'full':
			$timetype = 1;
			break;
		case 'tba':
			$timetype = 2;
			break;
		case 'none':
			$timetype = 3;
			break;
		default:
			soft_error(_("Unrecognized Time Type."));
	}

	if(empty($vars["phpc_token"])
			|| $vars["phpc_token"] != $_SESSION["phpc_token"])
		soft_error(_("Possible request forgery."));

	if(!can_write($phpcid))
		permission_error(_('You do not have permission to write to this calendar.'));

	if(can_create_readonly($phpcid) && !empty($arguments['readonly']))
		$readonly = true;
	else
		$readonly = false;

	$catid = empty($vars['catid']) ? false : $vars['catid'];

	if(!isset($vars['eid'])) {
		$modify = false;
		$eid = $phpcdb->create_event($phpcid, get_uid(),
				$vars["subject"], $vars["description"],
				$readonly, $catid);
	} else {
		$modify = true;
		$eid = $vars['eid'];
		$phpcdb->modify_event($eid, $vars['subject'],
				$vars['description'], $readonly, $catid);
		$phpcdb->delete_occurrences($eid);
	}
		
	$phpcdb->create_occurrence($eid, $startdate, $enddate, $timetype,
			$starttime, $endtime);

	$occurrences = 1;
	switch($vars["repeats"]) {
		case "never":
			break;
		case 'daily':
			if(!isset($vars["every-day"]))
				soft_error(_("Required field \"every-day\" is not set."));
			$ndays = $vars["every-day"];
			if($ndays < 1)
				soft_error(_("every-day must be greater than 1"));

			$daily_until = get_date("daily-until");
			while($occurrences <= 730) {
				$startdate = add_days($startdate, $ndays);
				$enddate = add_days($enddate, $ndays);
				if($startdate > $daily_until)
					break;
				$phpcdb->create_occurrence($eid, $startdate,
						$enddate, $timetype,
						$starttime, $endtime);
				$occurrences++;
			}
			break;

		case 'weekly':
			if(!isset($vars["every-week"]))
				soft_error(_("Required field \"every-week\" is not set."));
			if($vars["every-week"] < 1)
				soft_error(_("every-week must be greater than 1"));
			$ndays = $vars["every-week"] * 7;

			$weekly_until = get_date("weekly-until");
			while($occurrences <= 730) {
				$startdate = add_days($startdate, $ndays);
				$enddate = add_days($enddate, $ndays);
				if($startdate > $weekly_until)
					break;
				$phpcdb->create_occurrence($eid, $startdate,
						$enddate, $timetype,
						$starttime, $endtime);
				$occurrences++;
			}
			break;

		case 'monthly':
			if(!isset($vars["every-month"]))
				soft_error(_("Required field \"every-month\" is not set."));
			if($vars["every-month"] < 1)
				soft_error(_("every-month must be greater than 1"));
			$nmonths = $vars["every-month"];

			$monthly_until = get_date("monthly-until");
			while($occurrences <= 730) {
				$startdate = add_months($startdate, $nmonths);
				$enddate = add_months($enddate, $nmonths);
				if($startdate > $monthly_until)
					break;
				$phpcdb->create_occurrence($eid, $startdate,
						$enddate, $timetype,
						$starttime, $endtime);
				$occurrences++;
			}
			break;

		case 'yearly':
			if(!isset($vars["every-year"]))
				soft_error(_("Required field \"every-year\" is not set."));
			if($vars["every-year"] < 1)
				soft_error(_("every-month must be greater than 1"));
			$nyears = $vars["every-year"];

			$yearly_until = get_date("yearly-until");
			while($occurrences <= 730) {
				$startdate = add_years($startdate, $nyears);
				$enddate = add_years($enddate, $nyears);
				if($startdate > $yearly_until)
					break;
				$phpcdb->create_occurrence($eid, $startdate,
						$enddate, $timetype,
						$starttime, $endtime);
				$occurrences++;
			}
			break;

		default:
			soft_error(_("Invalid event type."));
	}

	// echo "<pre>arguments:\n"; print_r($arguments); echo "</pre>";

	if($modify)
		return tag('div', "Modified event: ", create_event_link($eid,
					'display_event', $eid));
	if($eid != 0)
		return tag('div', "Created event: ", create_event_link($eid,
					'display_event', $eid));
		// $calendar->redirect("action=display&eventid=$eid");
	else
		return tag('div', attributes('class="phpc-error"'),
				_('Error submitting event.'));
}

function get_date($prefix)
{
	global $vars;

	if(!isset($vars["{$prefix}-year"]))
		soft_error(_("Required field {$prefix}-year was not set."));
	else
		$year = $vars["{$prefix}-year"];

	if(!isset($vars["{$prefix}-month"]))
		soft_error(_("Required field {$prefix}-month was not set."));
	else
		$month = $vars["{$prefix}-month"];

	if(!isset($vars["{$prefix}-day"]))
		soft_error(_("Required field {$prefix}-day was not set."));
	else
		$day = $vars["{$prefix}-day"];

	return mktime(0, 0, 0, $month, $day, $year);
}

function get_time($prefix)
{
	global $vars;

	if(!isset($vars["{$prefix}-hour"]))
		soft_error(_("Required field {$prefix}-hour was not set."));
	else
		$hour = $vars["{$prefix}-hour"];

	if(!isset($vars["{$prefix}-minute"]))
		soft_error(_("Required field {$prefix}-minute was not set."));
	else
		$minute = $vars["{$prefix}-minute"];

	if(isset($vars["{$prefix}-meridiem"])) {
		if($vars["{$prefix}-meridiem"] == "pm") {
			if($hour < 12)
				$hour += 12;
		} else {
			if($hour == 12)
				$hour = 0;
		}
	}
		
	return "{$hour}:{$minute}:00";
}

function add_days($stamp, $days)
{
	return mktime(0, 0, 0, date('m', $stamp), date('d', $stamp) + $days,
			date('Y', $stamp));
}

function add_months($stamp, $months)
{
	return mktime(0, 0, 0, date('m', $stamp) + $months, date('d', $stamp),
			date('Y', $stamp));
}

function add_years($stamp, $years)
{
	return mktime(0, 0, 0, date('m', $stamp), date('d', $stamp),
			date('Y', $stamp) + $years);
}
?>

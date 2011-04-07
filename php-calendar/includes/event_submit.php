<?php
/*
 * Copyright 2011 Sean Proctor
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
	global $vars, $phpcdb, $phpcid, $phpc_script;

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

	$start_ts = get_timestamp("start");
	$end_ts = get_timestamp("end");

	switch($vars["time-type"]) {
		case 'normal':
			$time_type = 0;
			break;

		case 'full':
			$time_type = 1;
			break;

		case 'tba':
			$time_type = 2;
			break;

		case 'none':
			$time_type = 3;
			break;

		default:
			soft_error(_("Unrecognized Time Type."));
	}

	$duration = $end_ts - $start_ts;
	if($duration < 0)
		soft_error(_("An event cannot have an end earlier than its start."));

	verify_token();

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
		
	$oid = $phpcdb->create_occurrence($eid, $time_type, $start_ts, $end_ts);

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

			$daily_until = get_timestamp("daily-until");
			while($occurrences <= 730) {
				$start_ts = add_days($start_ts, $ndays);
				$end_ts = add_days($end_ts, $ndays);
				if($start_ts > $daily_until)
					break;
				$phpcdb->create_occurrence($eid, $time_type,
						$start_ts, $end_ts);
				$occurrences++;
			}
			break;

		case 'weekly':
			if(!isset($vars["every-week"]))
				soft_error(_("Required field \"every-week\" is not set."));
			if($vars["every-week"] < 1)
				soft_error(_("every-week must be greater than 1"));
			$ndays = $vars["every-week"] * 7;

			$weekly_until = get_timestamp("weekly-until");
			while($occurrences <= 730) {
				$start_ts = add_days($start_ts, $ndays);
				$end_ts = add_days($end_ts, $ndays);
				if($start_ts > $weekly_until)
					break;
				$phpcdb->create_occurrence($eid, $time_type,
						$start_ts, $end_ts);
				$occurrences++;
			}
			break;

		case 'monthly':
			if(!isset($vars["every-month"]))
				soft_error(_("Required field \"every-month\" is not set."));
			if($vars["every-month"] < 1)
				soft_error(_("every-month must be greater than 1"));
			$nmonths = $vars["every-month"];

			$monthly_until = get_timestamp("monthly-until");
			while($occurrences <= 730) {
				$start_ts = add_months($start_ts, $nmonths);
				$end_ts = add_months($end_ts, $nmonths);
				if($start_ts > $monthly_until)
					break;
				$phpcdb->create_occurrence($eid, $time_type,
						$start_ts, $end_ts);
				$occurrences++;
			}
			break;

		case 'yearly':
			if(!isset($vars["every-year"]))
				soft_error(_("Required field \"every-year\" is not set."));
			if($vars["every-year"] < 1)
				soft_error(_("every-month must be greater than 1"));
			$nyears = $vars["every-year"];

			$yearly_until = get_timestamp("yearly-until");
			while($occurrences <= 730) {
				$start_ts = add_years($start_ts, $nyears);
				$end_ts = add_years($end_ts, $nyears);
				if($start_ts > $yearly_until)
					break;
				$phpcdb->create_occurrence($eid, $time_type,
						$start_ts, $end_ts);
				$occurrences++;
			}
			break;

		default:
			soft_error(_("Invalid event type."));
	}

	if($eid != 0) {
		if($modify)
			$message = _("Modified event: ");
		else
			$message = _("Created event: ");

		return message(tag('', $message, create_event_link($eid,
						'display_event', $eid)),
				"$phpc_script?action=display_event&eid=$eid");
	} else {
		return message(_('Error submitting event.'),
				"$phpc_script?action=display_month");
	}
}

function get_timestamp($prefix)
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

	if(!isset($vars["{$prefix}-hour"]))
		$hour = 0;
	else
		$hour = $vars["{$prefix}-hour"];

	if(!isset($vars["{$prefix}-minute"]))
		$minute = 0;
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
		
	return mktime($hour, $minute, 0, $month, $day, $year);
}

?>

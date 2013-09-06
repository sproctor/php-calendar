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

if(!defined('IN_PHPC')) {
	die("Hacking attempt");
}

require_once("$phpc_includes_path/form.php");

function event_form() {
	global $vars;

	if(empty($vars["submit_form"]))
		return display_form();

	// else
	return process_form();
}

function display_form() {
	global $phpc_script, $year, $month, $day, $vars, $phpcdb, $phpc_cal,
	       $phpc_user, $phpc_token;

	$hour24 = $phpc_cal->hours_24;
	$date_format = $phpc_cal->date_format;
	$form = new Form($phpc_script, __('Event Form'));
	$form->add_part(new FormFreeQuestion('subject', __('Subject'),
				false, $phpc_cal->subject_max, true));
	$form->add_part(new FormLongFreeQuestion('description',
				__('Description')));

	$when_group = new FormGroup(__('When'), 'phpc-when');
	if(isset($vars['eid'])) {
		$when_group->add_part(new FormCheckBoxQuestion('phpc-modify',
					false,
					__('Change the event date and time')));
	}
	$when_group->add_part(new FormDateTimeQuestion('start',
				__('From'), $hour24, $date_format));
	$when_group->add_part(new FormDateTimeQuestion('end', __('To'),
				$hour24, $date_format));

	$time_type = new FormDropDownQuestion('time-type', __('Time Type'));
	$time_type->add_option('normal', __('Normal'));
	$time_type->add_option('full', __('Full Day'));
	$time_type->add_option('tba', __('To Be Announced'));

	$when_group->add_part($time_type);

	$form->add_part($when_group);

	$repeat_type = new FormDropdownQuestion('repeats', __('Repeats'),
			array(), true, 'never');
	$repeat_type->add_option('never', __('Never'));
	$daily_group = new FormGroup();
	$repeat_type->add_option('daily', __('Daily'), NULL, $daily_group);
	$weekly_group = new FormGroup();
	$repeat_type->add_option('weekly', __('Weekly'), NULL, $weekly_group);
	$monthly_group = new FormGroup();
	$repeat_type->add_option('monthly', __('Monthly'), NULL, $monthly_group);
	$yearly_group = new FormGroup();
	$repeat_type->add_option('yearly', __('Yearly'), NULL, $yearly_group);

	$every_day = new FormDropdownQuestion('every-day', __('Every'),
			__('Repeat every how many days?'));
	$every_day->add_options(create_sequence(1, 30));
	$daily_group->add_part($every_day);
	$daily_group->add_part(new FormDateQuestion('daily-until', __('Until'),
				$date_format));

	$every_week = new FormDropdownQuestion('every-week', __('Every'),
			__('Repeat every how many weeks?'));
	$every_week->add_options(create_sequence(1, 30));
	$weekly_group->add_part($every_week);
	$weekly_group->add_part(new FormDateQuestion('weekly-until',
				__('Until'), $date_format));

	$every_month = new FormDropdownQuestion('every-month', __('Every'),
			__('Repeat every how many months?'));
	$every_month->add_options(create_sequence(1, 30));
	$monthly_group->add_part($every_month);
	$monthly_group->add_part(new FormDateQuestion('monthly-until',
				__('Until'), $date_format));

	$every_year = new FormDropdownQuestion('every-year', __('Every'),
			__('Repeat every how many years?'));
	$every_year->add_options(create_sequence(1, 30));
	$yearly_group->add_part($every_year);
	$yearly_group->add_part(new FormDateQuestion('yearly-until',
				__('Until'), $date_format));

	$when_group->add_part($repeat_type);

	if($phpc_cal->can_create_readonly())
		$form->add_part(new FormCheckBoxQuestion('readonly', false,
					__('Read-only')));

	$categories = new FormDropdownQuestion('catid', __('Category'));
	$categories->add_option('', __('None'));
	$have_categories = false;
	foreach($phpc_cal->get_visible_categories($phpc_user->get_uid()) as $category) {
		$categories->add_option($category['catid'], $category['name']);
		$have_categories = true;
	}
	if($have_categories)
		$form->add_part($categories);

	if(isset($vars['phpcid']))
		$form->add_hidden('phpcid', $vars['phpcid']);

	$form->add_hidden('phpc_token', $phpc_token);
	$form->add_hidden('action', 'event_form');
	$form->add_hidden('submit_form', 'submit_form');

	$form->add_part(new FormSubmitButton(__("Submit Event")));

	if(isset($vars['eid'])) {
		$form->add_hidden('eid', $vars['eid']);
		$occs = $phpcdb->get_occurrences_by_eid($vars['eid']);
		$event = $occs[0];

		$defaults = array(
				'subject' => $event->get_raw_subject(),
				'description' => $event->get_raw_desc(),
				'start-date' => $event->get_short_start_date(),
				'end-date' => $event->get_short_end_date(),
				'start-time' => $event->get_start_time(),
				'end-time' => $event->get_end_time(),
				'readonly' => $event->is_readonly(),
				);

		if(!empty($event->catid))
			$defaults['catid'] = $event->catid;

		switch($event->get_time_type()) {
			case 0:
				$defaults['time-type'] = 'normal';
				break;
			case 1:
				$defaults['time-type'] = 'full';
				break;
			case 2:
				$defaults['time-type'] = 'tba';
				break;
		}

		add_repeat_defaults($occs, $defaults);

	} else {
		$hour24 = $phpc_cal->hours_24;
		$datefmt = $phpc_cal->date_format;
		$date_string = format_short_date_string($year, $month, $day,
				$datefmt);
		$defaults = array(
				'start-date' => $date_string,
				'end-date' => $date_string,
				'start-time' => format_time_string(17, 0, $hour24),
				'end-time' => format_time_string(18, 0, $hour24),
				'daily-until-date' => $date_string,
				'weekly-until-date' => $date_string,
				'monthly-until-date' => $date_string,
				'yearly-until-date' => $date_string,
				);
	}
	return $form->get_form($defaults);
}

function add_repeat_defaults($occs, &$defaults) {
	// TODO: Handle unevenly spaced occurrences

	$defaults['repeats'] = 'never';

	if(sizeof($occs) < 2)
		return;

	$event = $occs[0];
	$day = $event->get_start_day();
	$month = $event->get_start_month();
	$year = $event->get_start_year();

	// Test if they repeat every N years
	$nyears = $occs[1]->get_start_year() - $event->get_start_year();
	$repeats_yearly = true;
	$nmonths = ($occs[1]->get_start_year() - $year) * 12
		+ $occs[1]->get_start_month() - $month;
	$repeats_monthly = true;
	$ndays = days_between($event->get_start_ts(), $occs[1]->get_start_ts());
	$repeats_daily = true;

	for($i = 1; $i < sizeof($occs); $i++) {
		$cur_occ = $occs[$i];
		$cur_year = $cur_occ->get_start_year();
		$cur_month = $cur_occ->get_start_month();
		$cur_day = $cur_occ->get_start_day();

		// Check year
		$cur_nyears = $cur_year - $occs[$i - 1]->get_start_year();
		if($cur_day != $day || $cur_month != $month
				|| $cur_nyears != $nyears) {
			$repeats_yearly = false;
		}

		// Check month
		$cur_nmonths = ($cur_year - $occs[$i - 1]->get_start_year())
			* 12 + $cur_month - $occs[$i - 1]->get_start_month();
		if($cur_day != $day || $cur_nmonths != $nmonths) {
			$repeats_monthly = false;
		}

		// Check day
		$cur_ndays = days_between($occs[$i - 1]->get_start_ts(),
				$occs[$i]->get_start_ts());
		if($cur_ndays != $ndays) {
			$repeats_daily = false;
		}
	}

	$defaults['yearly-until-date'] = "$cur_month/$cur_day/$cur_year";
	$defaults['monthly-until-date'] = "$cur_month/$cur_day/$cur_year";
	$defaults['weekly-until-date'] = "$cur_month/$cur_day/$cur_year";
	$defaults['daily-until-date'] = "$cur_month/$cur_day/$cur_year";

	if($repeats_daily) {
		// repeats weekly
		if($ndays % 7 == 0) {
			$defaults['repeats'] = 'weekly';
			$defaults['every-week'] = $ndays / 7;
		} else {
			$defaults['every-week'] = 1;

			// repeats daily
			$defaults['repeats'] = 'daily';
			$defaults['every-day'] = $ndays;
		}

	} else {
		$defaults['every-day'] = 1;
		$defaults['every-week'] = 1;
	}

	if($repeats_monthly) {
		$defaults['repeats'] = 'monthly';
		$defaults['every-month'] = $nmonths;
	} else {
		$defaults['every-month'] = 1;
	}

	if($repeats_yearly) {
		$defaults['repeats'] = 'yearly';
		$defaults['every-year'] = $nyears;
	} else {
		$defaults['every-year'] = 1;
	}
}

function process_form()
{
	global $vars, $phpcdb, $phpc_cal, $phpcid, $phpc_script, $phpc_user;

	// When modifying events, this is the value of the checkbox that
	//   determines if the date should change
	$modify_occur = !isset($vars['eid']) || !empty($vars['phpc-modify']);

	if($modify_occur) {
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

		default:
			soft_error(__("Unrecognized Time Type."));
	}

	$duration = $end_ts - $start_ts;
	if($duration < 0) {
		message(__("An event cannot have an end earlier than its start."));
		return display_form();
	}
	}

	verify_token();

	if(!$phpc_cal->can_write())
		permission_error(__('You do not have permission to write to this calendar.'));

	if($phpc_cal->can_create_readonly() && !empty($vars['readonly']))
		$readonly = true;
	else
		$readonly = false;

	$catid = empty($vars['catid']) ? false : $vars['catid'];

	if(!isset($vars['eid'])) {
		$modify = false;
		$eid = $phpcdb->create_event($phpcid, $phpc_user->get_uid(),
				$vars["subject"], $vars["description"],
				$readonly, $catid);
	} else {
		$modify = true;
		$eid = $vars['eid'];
		$phpcdb->modify_event($eid, $vars['subject'],
				$vars['description'], $readonly, $catid);
		if($modify_occur)
			$phpcdb->delete_occurrences($eid);
	}
	
	if($modify_occur) {
	$oid = $phpcdb->create_occurrence($eid, $time_type, $start_ts, $end_ts);

	$occurrences = 1;
	switch($vars["repeats"]) {
		case "never":
			break;

		case 'daily':
			if(!isset($vars["every-day"]))
				soft_error(__("Required field \"every-day\" is not set."));
			$ndays = $vars["every-day"];
			if($ndays < 1)
				soft_error(__("every-day must be greater than 1"));

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
				soft_error(__("Required field \"every-week\" is not set."));
			if($vars["every-week"] < 1)
				soft_error(__("every-week must be greater than 1"));
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
				soft_error(__("Required field \"every-month\" is not set."));
			if($vars["every-month"] < 1)
				soft_error(__("every-month must be greater than 1"));
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
				soft_error(__("Required field \"every-year\" is not set."));
			if($vars["every-year"] < 1)
				soft_error(__("every-month must be greater than 1"));
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
			soft_error(__("Invalid event type."));
	}
	}

	if($eid != 0) {
		if($modify)
			$message = __("Modified event: ");
		else
			$message = __("Created event: ");

		return message_redirect(tag('', $message,
					create_event_link($eid, 'display_event',
						$eid)),
				"$phpc_script?action=display_event&phpcid=$phpcid&eid=$eid");
	} else {
		return message_redirect(__('Error submitting event.'),
				"$phpc_script?action=display_month&phpcid=$phpcid");
	}
}

?>

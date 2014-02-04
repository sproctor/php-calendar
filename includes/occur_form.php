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

function occur_form() {
	global $vars;

	if(empty($vars["submit_form"]))
		return display_form();

	// else
	return process_form();
}

function display_form() {
	global $phpc_script, $year, $month, $day, $vars, $phpcdb, $phpc_cal,
		$phpc_token;

	$hour24 = $phpc_cal->hours_24;
	$date_format = $phpc_cal->date_format;
	$form = new Form($phpc_script, __('Occurrence Form'));

	$when_group = new FormGroup(__('When'));
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

	if(isset($vars['phpcid']))
		$form->add_hidden('phpcid', $vars['phpcid']);

	if(isset($vars['oid'])) {
		$form->add_hidden('oid', $vars['oid']);

		$occ = $phpcdb->get_occurrence_by_oid($vars['oid']);
		$datefmt = $phpc_cal->date_format;

		$start_date = format_short_date_string($occ->get_start_year(),
				$occ->get_start_month(), $occ->get_start_day(),
				$datefmt);
		$end_date = format_short_date_string($occ->get_end_year(),
				$occ->get_end_month(), $occ->get_end_day(),
				$datefmt);
		$defaults = array(
				'start-date' => $start_date,
				'end-date' => $end_date,
				'start-time' => $occ->get_start_time(),
				'end-time' => $occ->get_end_time(),
				);

		switch($occ->get_time_type()) {
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

	} else {
		$form->add_hidden('eid', $vars['eid']);
		$defaults = array(
				'start-date' => "$month/$day/$year",
				'end-date' => "$month/$day/$year",
				'start-time' => format_time_string(17, 0, $hour24),
				'end-time' => format_time_string(18, 0, $hour24),
				);
	}

	$form->add_hidden('phpc_token', $phpc_token);
	$form->add_hidden('action', 'occur_form');
	$form->add_hidden('submit_form', 'submit_form');

	$form->add_part(new FormSubmitButton(__("Submit Occurrence")));

	return $form->get_form($defaults);
}

function process_form()
{
	global $vars, $phpcdb, $phpc_cal, $phpcid, $phpc_script;

	if(!isset($vars['eid']) && !isset($vars['oid']))
		soft_error(__("Cannot create occurrence."));

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
	if($duration < 0)
		soft_error(__("An event cannot have an end earlier than its start."));

	verify_token();

	if(!$phpc_cal->can_write())
		permission_error(__('You do not have permission to write to this calendar.'));

	if(!isset($vars['oid'])) {
		$modify = false;
		if(!isset($vars["eid"]))
			soft_error(__("EID not set."));
		$oid = $phpcdb->create_occurrence($vars["eid"], $time_type, $start_ts,
				$end_ts);
	} else {
		$modify = true;
		$oid = $vars["oid"];
		$phpcdb->modify_occurrence($oid, $time_type, $start_ts,
				$end_ts);
	}
		
	if($oid != 0) {
		if($modify)
			$message = __("Modified occurence: ");
		else
			$message = __("Created occurence: ");

		return message_redirect(tag('', $message,
					create_event_link($oid, 'display_event',
						$oid)),
				"$phpc_script?action=display_event&phpcid=$phpcid&oid=$oid");
	} else {
		return message_redirect(__('Error submitting occurrence.'),
				"$phpc_script?action=display_month&phpcid=$phpcid");
	}
}

?>

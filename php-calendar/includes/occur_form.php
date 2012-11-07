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
	global $phpc_script, $year, $month, $day, $vars, $phpcdb, $phpc_cal;

	$hour24 = $phpc_cal->get_config('hours_24');
	$form = new Form($phpc_script, _('Occurrence Form'));

	$when_group = new FormGroup(_('When'));
	$when_group->add_part(new FormDateTimeQuestion('start',
				_('From'), $hour24));
	$when_group->add_part(new FormDateTimeQuestion('end', _('To'),
				$hour24));

	$time_type = new FormDropDownQuestion('time-type', _('Time Type'));
	$time_type->add_option('normal', _('Normal'));
	$time_type->add_option('full', _('Full Day'));
	$time_type->add_option('tba', _('To Be Announced'));

	$when_group->add_part($time_type);

	$form->add_part($when_group);

	if(isset($vars['phpcid']))
		$form->add_hidden('phpcid', $vars['phpcid']);

	if(isset($vars['oid'])) {
		$form->add_hidden('oid', $vars['oid']);

		$occ = $phpcdb->get_occurrence_by_oid($vars['oid']);

		$start_date = $occ->get_start_month() . "/"
			. $occ->get_start_day() . "/"
			. $occ->get_start_year();
		$end_date = $occ->get_end_month() . "/"
			. $occ->get_end_day() . "/"
			. $occ->get_end_year();
		$start_time = $occ->get_start_hour() . ":"
			. $occ->get_start_minute();
		$end_time = $occ->get_end_hour() . ":"
			. $occ->get_end_minute();
		$defaults = array(
				'start-date' => $start_date,
				'end-date' => $end_date,
				'start-time' => $start_time,
				'end-time' => $end_time,
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
				'start-time' => "17:00",
				'end-time' => "18:00",
				);
	}

	$form->add_hidden('action', 'occur_form');
	$form->add_hidden('submit_form', 'submit_form');

	$form->add_part(new FormSubmitButton(_("Submit Occurrence")));

	return $form->get_form($defaults);
}

function process_form()
{
	global $vars, $phpcdb, $phpc_cal, $phpcid, $phpc_script;

	if(!isset($vars['eid']) && !isset($vars['oid']))
		soft_error(_("Cannot create occurrence."));

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
			soft_error(_("Unrecognized Time Type."));
	}

	$duration = $end_ts - $start_ts;
	if($duration < 0)
		soft_error(_("An event cannot have an end earlier than its start."));

	verify_token();

	if(!$phpc_cal->can_write())
		permission_error(_('You do not have permission to write to this calendar.'));

	if(!isset($vars['oid'])) {
		$modify = false;
		if(!isset($vars["eid"]))
			soft_error(_("EID not set."));
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
			$message = _("Modified occurence: ");
		else
			$message = _("Created occurence: ");

		return message_redirect(tag('', $message,
					create_event_link($oid, 'display_event',
						$oid)),
				"$phpc_script?action=display_event&oid=$oid");
	} else {
		return message_redirect(_('Error submitting occurrence.'),
				"$phpc_script?action=display_month");
	}
}

function get_timestamp($prefix)
{
	global $vars;

	if(!isset($vars["$prefix-date"]))
		soft_error(sprintf(_("Required field \"%s\" year was not set."),
					$prefix));

	if(!isset($vars["$prefix-time"])) {
		$hour = 0;
		$minute = 0;
	} else {
		if(!preg_match('/(\d+):(\d+)/', $vars["$prefix-time"],
					$time_matches)) {
			soft_error(sprintf(_("Malformed time in \"%s\" time."),
						$prefix));
		}
		$hour = $time_matches[1];
		$minute = $time_matches[2];
	}

	if(!preg_match('/(\d+)\/(\d+)\/(\d+)/', $vars["$prefix-date"],
				$date_matches)) {
		soft_error(sprintf(_("Malformed time in \"%s\" date."),
					$prefix));
	}
	$month = $date_matches[1];
	$day = $date_matches[2];
	$year = $date_matches[3];

	return mktime($hour, $minute, 0, $month, $day, $year);
}
?>

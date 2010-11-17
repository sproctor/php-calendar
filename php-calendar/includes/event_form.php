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

if(!defined('IN_PHPC')) {
	die("Hacking attempt");
}

require_once("$phpc_includes_path/form.php");

function event_form() {
	global $phpc_script, $year, $month, $day, $vars, $phpcdb, $phpcid;

	$hour24 = get_config($phpcid, 'hours_24');
	$form = new Form($phpc_script, _('Event Form'));
	$form->add_part(new FormFreeQuestion('subject', _('Subject'),
				false, get_config($phpcid, 'subject_max'),
				true));
	$form->add_part(new FormLongFreeQuestion('description',
				_('Description')));

	$when_group = new FormGroup(_('When'));
	$when_group->add_part(new FormDateTimeQuestion('start',
				_('From'), $hour24));
	$when_group->add_part(new FormDateTimeQuestion('end', _('To'),
				$hour24));

	$time_type = new FormDropDownQuestion('time-type', _('Time Type'));
	$time_type->add_option('normal', _('Normal'));
	$time_type->add_option('full', _('Full Day'));
	$time_type->add_option('tba', _('To Be Announced'));
	$time_type->add_option('none', _('None'));

	$when_group->add_part($time_type);

	$form->add_part($when_group);

	$repeat_type = new FormDropdownQuestion('repeats', _('Repeats'),
			array(), true, 'never');
	$repeat_type->add_option('never', _('Never'));
	$daily_group = new FormGroup();
	$repeat_type->add_option('daily', _('Daily'), NULL, $daily_group);
	$weekly_group = new FormGroup();
	$repeat_type->add_option('weekly', _('Weekly'), NULL, $weekly_group);
	$monthly_group = new FormGroup();
	$repeat_type->add_option('monthly', _('Monthly'), NULL, $monthly_group);
	$yearly_group = new FormGroup();
	$repeat_type->add_option('yearly', _('Yearly'), NULL, $yearly_group);

	$every_day = new FormDropdownQuestion('every-day', _('Every'),
			_('Repeat every how many days?'));
	$every_day->add_options(create_sequence(1, 30));
	$daily_group->add_part($every_day);
	$daily_group->add_part(new FormDateQuestion('daily-until', _('Until')));

	$every_week = new FormDropdownQuestion('every-week', _('Every'),
			_('Repeat every how many weeks?'));
	$every_week->add_options(create_sequence(1, 30));
	$weekly_group->add_part($every_week);
	$weekly_group->add_part(new FormDateQuestion('weekly-until',
				_('Until')));

	$every_month = new FormDropdownQuestion('every-month', _('Every'),
			_('Repeat every how many months?'));
	$every_month->add_options(create_sequence(1, 30));
	$monthly_group->add_part($every_month);
	$monthly_group->add_part(new FormDateQuestion('monthly-until',
				_('Until')));

	$every_year = new FormDropdownQuestion('every-year', _('Every'),
			_('Repeat every how many years?'));
	$every_year->add_options(create_sequence(1, 30));
	$yearly_group->add_part($every_year);
	$yearly_group->add_part(new FormDateQuestion('yearly-until',
				_('Until')));

	$when_group->add_part($repeat_type);

	if(can_create_readonly($phpcid))
		$form->add_part(new FormCheckBoxQuestion('readonly',
					_('Read-only')));

	$categories = new FormDropdownQuestion('catid', _('Category'));
	$categories->add_option('', _('None'));
	$have_categories = false;
	foreach($phpcdb->get_categories($phpcid) as $category) {
		$categories->add_option($category['catid'], $category['name']);
		$have_categories = true;
	}
	if($have_categories)
		$form->add_part($categories);

	if(isset($vars['phpcid']))
		$form->add_hidden('phpcid', $vars['phpcid']);

	$form->add_hidden('phpc_token', $_SESSION['phpc_token']);

	$form->add_hidden('action', 'event_submit');
	$form->add_part(new FormSubmitButton(_("Submit Event")));

	if(isset($vars['eid'])) {
		$form->add_hidden('eid', $vars['eid']);
		$occs = $phpcdb->get_occurrences_by_eid($vars['eid']);
		$event = $occs[0];

		$defaults = array(
				'subject' => $event->get_raw_subject(),
				'description' => $event->get_raw_desc(),
				'start-year' => $event->get_start_year(),
				'end-year' => $event->get_end_year(),
				'start-month' => $event->get_start_month(),
				'end-month' => $event->get_end_month(),
				'start-day' => $event->get_start_day(),
				'end-day' => $event->get_end_day(),
				'start-hour' => $event->get_start_hour(),
				'start-minute' => $event->get_start_minute(),
				'end-hour' => $event->get_end_hour(),
				'end-minute' => $event->get_end_minute(),
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
			case 3:
				$defaults['time-type'] = 'none';
				break;
		}

		add_repeat_defaults($occs, $defaults);

	} else {
		$defaults = array(
				'start-year' => $year,
				'end-year' => $year,
				'start-month' => $month,
				'end-month' => $month,
				'start-day' => $day,
				'end-day' => $day,
				'start-hour' => 17,
				'start-minute' => 0,
				'end-hour' => 18,
				'end-minute' => 0,
				);
	}
	return $form->get_html($defaults);
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
	for($i = 1; $i < sizeof($occs); $i++) {
		$cur_occ = $occs[$i];
		$cur_year = $cur_occ->get_start_year();
		$cur_month = $cur_occ->get_start_month();
		$cur_day = $cur_occ->get_start_day();
		$cur_nyears = $cur_year - $occs[$i - 1]->get_start_year();
		if($cur_day != $day || $cur_month != $month
				|| $cur_nyears != $nyears) {
			$repeats_yearly = false;
			break;
		}
	}

	if($repeats_yearly) {
		$defaults['repeats'] = 'yearly';
		$defaults['every-year'] = $nyears;
		$defaults['yearly-until-year'] = $cur_year;
		$defaults['yearly-until-month'] = $cur_month;
		$defaults['yearly-until-day'] = $cur_day;
		return;
	}

	$nmonths = ($occs[1]->get_start_year() - $year) * 12
		+ $occs[1]->get_start_month() - $month;
	//echo "day: $day, month: $month, nmonths: $nmonths<br>";
	$repeats_monthly = true;
	for($i = 1; $i < sizeof($occs); $i++) {
		$cur_occ = $occs[$i];
		$cur_year = $cur_occ->get_start_year();
		$cur_month = $cur_occ->get_start_month();
		$cur_day = $cur_occ->get_start_day();
		$cur_nmonths = ($cur_year - $occs[$i - 1]->get_start_year())
			* 12 + $cur_month - $occs[$i - 1]->get_start_month();
		if($cur_day != $day || $cur_nmonths != $nmonths) {
//echo "cur_day: $cur_day, cur_month: $cur_month, cur_nmonths: $cur_nmonths<br>";
			$repeats_monthly = false;
			break;
		}
	}

	if($repeats_monthly) {
		$defaults['repeats'] = 'monthly';
		$defaults['every-month'] = $nmonths;
		$defaults['monthly-until-year'] = $cur_year;
		$defaults['monthly-until-month'] = $cur_month;
		$defaults['monthly-until-day'] = $cur_day;
		return;
	}

	$ndays = days_between($event->get_start_ts(), $occs[1]->get_start_ts());
	$repeats_daily = true;
	for($i = 1; $i < sizeof($occs); $i++) {
		$cur_occ = $occs[$i];
		$cur_year = $cur_occ->get_start_year();
		$cur_month = $cur_occ->get_start_month();
		$cur_day = $cur_occ->get_start_day();
		$cur_ndays = days_between($occs[$i - 1]->get_start_ts(),
				$occs[$i]->get_start_ts());
		if($cur_ndays != $ndays) {
echo "cur_day: $cur_day, cur_month: $cur_month, cur_ndays: $cur_ndays<br>";
			$repeats_daily = false;
			break;
		}
	}

	if($repeats_daily) {
		// repeats weekly
		if($ndays % 7 == 0) {
			$defaults['repeats'] = 'weekly';
			$defaults['every-week'] = $ndays / 7;
			$defaults['weekly-until-year'] = $cur_year;
			$defaults['weekly-until-month'] = $cur_month;
			$defaults['weekly-until-day'] = $cur_day;
			return;
		}

		// repeats daily
		$defaults['repeats'] = 'daily';
		$defaults['every-day'] = $ndays;
		$defaults['daily-until-year'] = $cur_year;
		$defaults['daily-until-month'] = $cur_month;
		$defaults['daily-until-day'] = $cur_day;
		return;
	}
}
?>

<?php
/*
   Copyright 2009 Sean Proctor

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

require_once($phpc_root_path . 'includes/form.php');

function create_form($phpc) {
        $form = new Form($phpc->self, _('Event Form'), 2);
        $form->add_part(new FormFreeQuestion('subject', _('Event Subject'),
				false, 32, true));
        $form->add_part(new FormLongFreeQuestion('description',
                                _('Event Description')));

	$time_type_radio = new FormRadioQuestion('time_type', _('Event Time'),
			array(), true);
	$time_type_radio->add_option('normal', _('Normal'));
	$time_type_radio->add_option('full', _('Full Day'));
	$time_type_radio->add_option('tba', _('To Be Announced'));
	$time_type_radio->add_option('none', _('None'));

	$hour_array = array();
	for($i = 0; $i < 24; $i++) $hour_array[] = $i;
	$minute_array = array();
	for($i = 0; $i < 60; $i++) $minute_array[] = $i;

	$normal_group = new FormGroup();
	$normal_group->add_part(new FormTimeQuestion('start_time',
				_('Start Time')));
	$normal_group->add_part(new FormTimeQuestion('end_time',
				_('End Time')));

        $time_type_compound = new FormCompoundQuestion($time_type_radio);
        $time_type_compound->add_conditional('normal', $normal_group);

        $form->add_part($time_type_compound);
	
        $event_type_radio = new FormRadioQuestion('event_type', _('Event Type'),
			array(), true);
        $event_type_radio->add_option('once', _('One Time'));
        $event_type_radio->add_option('multi', _('Multiple Day'));
        $event_type_radio->add_option('weekly', _('Weekly'));
        $event_type_radio->add_option('monthly', _('Monthly'));
        $event_type_radio->add_option('annual', _('Annual'));

	$once_group = new FormGroup();
	$once_group->add_part(new FormDateQuestion('once_date', _('Date')));

	$multi_group = new FormGroup();
	$multi_group->add_part(new FormDateQuestion('multi_start_date',
				_('Start Date')));
	$multi_group->add_part(new FormDateQuestion('multi_end_date',
				_('End Date')));

        $event_type_compound = new FormCompoundQuestion($event_type_radio);
        $event_type_compound->add_conditional('once', $once_group);
        $event_type_compound->add_conditional('multi', $multi_group);

        $form->add_part($event_type_compound);

	$form->add_part(new FormHiddenField('action', 'event_submit'));
	$form->add_part(new FormSubmitButton("Submit Event"));

        return $form;
}

function event_form($calendar)
{
	global $month_names, $event_types;

        $form = create_form($calendar);
        return $form->get_html();
}

?>

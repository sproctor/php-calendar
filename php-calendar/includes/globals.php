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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

$month_names = array(
                1 => _('January'),
                _('February'),
                _('March'),
                _('April'),
                _('May'),
                _('June'),
                _('July'),
                _('August'),
                _('September'),
                _('October'),
                _('November'),
                _('December'),
                );

$day_names = array(
                _('Sunday'),
		_('Monday'),
		_('Tuesday'),
		_('Wednesday'),
		_('Thursday'),
		_('Friday'),
		_('Saturday'),
                );

$short_month_names = array(
		1 => _('Jan'),
		_('Feb'),
		_('Mar'),
		_('Apr'),
		_('May'),
		_('Jun'),
		_('Jul'),
		_('Aug'),
		_('Sep'),
		_('Oct'),
		_('Nov'),
		_('Dec'),
                );

// config stuff
define('PHPC_CHECK', 1);
define('PHPC_TEXT', 2);
define('PHPC_DROPDOWN', 3);

$sort_options = array(
                'start_date' => _('Start Date'),
                'subject' => _('Subject')
                );

$order_options = array(
                'ASC' => _('Ascending'),
                'DESC' => _('Decending')
                );

?>

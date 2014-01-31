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
                1 => __('January'),
                __('February'),
                __('March'),
                __('April'),
                __('May'),
                __('June'),
                __('July'),
                __('August'),
                __('September'),
                __('October'),
                __('November'),
                __('December'),
                );

$day_names = array(
                __('Sunday'),
		__('Monday'),
		__('Tuesday'),
		__('Wednesday'),
		__('Thursday'),
		__('Friday'),
		__('Saturday'),
                );

$short_month_names = array(
		1 => __('Jan'),
		__('Feb'),
		__('Mar'),
		__('Apr'),
		__('May'),
		__('Jun'),
		__('Jul'),
		__('Aug'),
		__('Sep'),
		__('Oct'),
		__('Nov'),
		__('Dec'),
                );

// config stuff
define('PHPC_CHECK', 1);
define('PHPC_TEXT', 2);
define('PHPC_DROPDOWN', 3);
define('PHPC_MULTI_DROPDOWN', 4);

$sort_options = array(
                'start_date' => __('Start Date'),
                'subject' => __('Subject')
                );

$order_options = array(
                'ASC' => __('Ascending'),
                'DESC' => __('Descending')
                );

?>

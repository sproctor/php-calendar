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

function search_results()
{
	global $vars, $phpcdb, $phpcid, $sort_options, $order_options;

	$searchstring = $vars['searchstring'];
	if(!empty($vars['search-from-date'])
			&& strlen($vars['search-from-date']) > 0)
		$start = get_timestamp('search-from');
	else
		$start = false;
	if(!empty($vars['search-to-date'])
			&& strlen($vars['search-to-date']) > 0)
		$end = get_timestamp('search-to');
	else
		$end = false;

        // make sure sort is valid
	$sort = htmlentities($vars['sort']);
        if(array_search($sort, array_keys($sort_options)) === false) {
                soft_error(__('Invalid sort option') . ": $sort");
        }

        // make sure order is valid
	$order = htmlentities($vars['order']);
        if(array_search($order, array_keys($order_options)) === false) {
                soft_error(__('Invalid order option') . ": $order");
        }

	$keywords = explode(" ", $searchstring);

	$results = $phpcdb->search($phpcid, $keywords, $start, $end, $sort,
			$order);

	$tags = array();
	foreach ($results as $event) {
		if(!$event->can_read())
			continue;

		$name = $event->get_author();
		$subject = $event->get_subject();
		$desc = $event->get_desc();
		$date = $event->get_date_string();
		$time = $event->get_time_string();
		$eid = $event->get_eid();

		$tags[] = tag('tr',
				tag('td',
					tag('strong',
						create_event_link(
							$subject,
							'display_event',
							$eid)
					   )),
				tag('td', "$date $time"),
				tag('td', $desc));
	}

	if(sizeof($tags) == 0) {
		$html = tag('div', tag('strong',
					__('No events matched your search criteria.')));
	} else {
		$html = tag('table',
				attributes('class="phpc-main"'),
				tag('caption', __('Search Results')),
				tag('thead',
					tag('tr',
						tag('th', __('Subject')),
						tag('th', __('Date Time')),
						tag('th', __('Description')))));
		foreach($tags as $tag) $html->add($tag);
	}

	return $html;
}

function search_form()
{
	global $day, $month, $year, $phpc_script, $month_names, $sort_options,
	       $order_options, $phpcid, $phpc_cal;
	
	$date_format = $phpc_cal->date_format;
 
	$form = new Form($phpc_script, __('Search'),'post');
    	$form->add_part(new FormFreeQuestion('searchstring', __('Phrase'),
				false, 32, true));
	$form->add_hidden('action', 'search');
	$form->add_hidden('phpcid', $phpcid);
	$form->add_part(new FormDateQuestion('search-from', __('From'),
				$date_format));
	$form->add_part(new FormDateQuestion('search-to', __('To'),
				$date_format));
	$sort = new FormDropdownQuestion('sort', __('Sort By'));
	$sort->add_options($sort_options);
	$form->add_part($sort);
	$order = new FormDropdownQuestion('order', __('Order'));
	$order->add_options($order_options);
	$form->add_part($order);
	$form->add_part(new FormSubmitButton(__("Search")));
	return $form->get_form();
}

function search()
{
	global $vars;

	if(isset($vars['searchstring'])) return search_results();
	return search_form();
}

?>

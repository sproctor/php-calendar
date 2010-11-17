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

function search_results()
{
	global $vars, $phpcdb, $phpcid, $sort_options, $order_options;

	$searchstring = $vars['searchstring'];

	$start = $vars['syear'] . str_pad($vars['smonth'], 2, '0', STR_PAD_LEFT)
		. str_pad($vars['sday'], 2, '0', STR_PAD_LEFT);
	$end = $vars['eyear'] . str_pad($vars['emonth'], 2, '0', STR_PAD_LEFT)
		. str_pad($vars['eday'], 2, '0', STR_PAD_LEFT);

        // make sure sort is valid
	$sort = htmlentities($vars['sort']);
        if(array_search($sort, array_keys($sort_options)) === false) {
                soft_error(_('Invalid sort option') . ": $sort");
        }

        // make sure order is valid
	$order = htmlentities($vars['order']);
        if(array_search($order, array_keys($order_options)) === false) {
                soft_error(_('Invalid order option') . ": $order");
        }

	$keywords = explode(" ", $searchstring);

	$results = $phpcdb->search($phpcid, $keywords, $start, $end, $sort,
			$order);

	$tags = array();
	foreach ($results as $event) {
		if(!can_read_event($event))
			continue;

		$name = $event->get_username();
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
					_('No events matched your search criteria.')));
	} else {
		$html = tag('table',
				attributes('class="phpc-main"'),
				tag('caption', _('Search Results')),
				tag('thead',
					tag('tr',
						tag('th', _('Subject')),
						tag('th', _('Date Time')),
						tag('th', _('Description')))));
		foreach($tags as $tag) $html->add($tag);
	}

	return $html;
}

function search_form()
{
	global $day, $month, $year, $phpc_script, $month_names, $sort_options,
	       $order_options, $phpcid;

	$day_sequence = create_sequence(1, 31);
	$year_sequence = create_sequence(1970, 2037);
	$html_table = tag('table',
			tag('caption', _('Search')),
			tag('tfoot',
				tag('tr',
					tag('td', attributes('colspan="2"'),
						create_submit(_('Submit'))))),
			tag('tr',
				tag('td', _('Phrase') . ': '),
				tag('td', tag('input', attributes('type="text"',
							'name="searchstring"',
							'size="32"')),
					create_hidden('action', 'search'),
					create_hidden('phpcid', $phpcid))),
			tag('tr',
				tag('td', _('From') . ': '),
				tag('td',
					create_select('sday', $day_sequence,
						$day),
					create_select('smonth', $month_names,
						$month),
					create_select('syear', $year_sequence,
						$year))),
			tag('tr',
				tag('td', _('To') . ': '),
				tag('td',
					create_select('eday', $day_sequence,
						$day),
					create_select('emonth', $month_names,
						$month),
					create_select('eyear', $year_sequence,
						$year))),
			tag('tr',
					tag('td', _('Sort By') . ': '),
					tag('td',
						create_select('sort',
							$sort_options))),
			tag('tr',
					tag('td', _('Order') . ': '),
					tag('td',
						create_select('order',
							$order_options))));

	return tag('form', attributes("action=\"$phpc_script\"",
				'method="post"'), $html_table);
}

function search()
{
	global $vars;

	if(isset($vars['searchstring'])) return search_results();
	return search_form();
}

?>

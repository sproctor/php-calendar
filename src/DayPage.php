<?php
/*
 * Copyright 2016 Sean Proctor
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

/*
   This file has the functions for the day displays of the calendar
*/

namespace PhpCalendar;

class DayPage extends Page
{
	// View for a single day
	/**
	 * @param Context $context
	 * @param \string[] $template_variables
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	function action(Context $context, $template_variables)
	{
		$year = $context->getYear();
		$month = $context->getMonth();
		$day = $context->getDay();

		$monthname = month_name($month);

		$have_events = false;

		$html_table = tag('table', attributes('class="phpc-main"'),
				tag('caption', "$day $monthname $year"),
				tag('thead',
					tag('tr',
						tag('th', __('Title')),
						tag('th', __('Time')),
						tag('th', __('Description'))
					   )));
		if($context->getCalendar()->can_modify($context->getUser())) {
			$html_table->add(tag('tfoot',
						tag('tr',
							tag('td',
								attributes('colspan="4"'),
								create_hidden('action', 'event_delete'),
								create_hidden('day', $day),
								create_hidden('month', $month),
								create_hidden('year', $year),
								create_submit(__('Delete Selected'))))));
		}

		$html_body = tag('tbody');

		$results = $context->db->get_occurrences_by_date($context->getCalendar()->cid, $year, $month, $day);
		while($row = $results->fetch_assoc()) {

			$event = new Occurrence($context, $row);

			if(!$event->can_read($context->getUser()))
				continue;

			$have_events = true;

			$eid = $event->get_eid();

			$html_subject = tag('td');

			if($event->can_modify($context->getUser())) {
				$html_subject->add(create_checkbox('eid[]', $eid));
			}

			$html_subject->add(create_event_link(tag('strong', $event->get_subject()), 'display_event', $eid));

			if($event->can_modify($context->getUser())) {
				$html_subject->add(create_event_link(__(' (Modify)'), 'event_form', $eid));
			}

			$html_body->add(tag('tr',
						$html_subject,
						tag('td', $event->get_time_span_string()),
						tag('td', $event->get_desc())));
		}

		$html_table->add($html_body);

		if($context->getCalendar()->can_modify($context->getUser())) {
			$output = tag('form', attrs('action="' . PHPC_SCRIPT . '"', 'class="phpc-form-confirm"', 'method="post"'),
					$html_table);
		} else {
			$output = $html_table;
		}

		if(!$have_events)
			$output = tag('h2', __('No events on this day.'));

		$dialog = tag('div', attrs('id="phpc-dialog"', 'title="' . __("Confirmation required") . '"'),
				__("Permanently delete the selected events?"));

		//$template_variables['cid'] = $cid;
		$template_variables['year'] = $year;
		//$template_variables['week_start'] = $week_start;
		$template_variables['occurrences'] = get_occurrences_by_day($calendar, $context->getUser(), $from_date,
				$to_date);
		return new Response($context->twig->render("day.html", $template_variables));
	}
}

function create_day_menu(Context $context, $year, $month, $day)
{
	$html = tag('div', attrs('class="phpc-bar ui-widget-content"'));
	
	$prev_time = mktime(0, 0, 0, $month, $day - 1, $year);
	$prev_day = date('j', $prev_time);
	$prev_month = date('n', $prev_time);
	$prev_year = date('Y', $prev_time);
	$prev_month_name = month_name($prev_month);

	$last_args = array('year' => $prev_year, 'month' => $prev_month, 'day' => $prev_day);

	menu_item_prepend($context, $html, "$prev_month_name $prev_day", 'display_day', $last_args);

	$next_time = mktime(0, 0, 0, $month, $day + 1, $year);
	$next_day = date('j', $next_time);
	$next_month = date('n', $next_time);
	$next_year = date('Y', $next_time);
	$nextmonthname = month_name($next_month);

	$next_args = array('year' => $next_year, 'month' => $next_month,
			'day' => $next_day);

	menu_item_append($context, $html, "$nextmonthname $next_day", 'display_day', $next_args);

	return $html;
}

function create_day_item($time) {
	
}
?>

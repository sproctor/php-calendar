<?php
/*
   Copyright 2002 Sean Proctor, Nathan Poiro

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


function search_results()
{
	global $vars, $db, $calendar_name;

	$searchstring = $vars['searchstring'];

	$start = "$vars[syear]-$vars[smonth]-$vars[sday]";
	$end = "$vars[eyear]-$vars[emonth]-$vars[eday]";
	$sort = $vars['sort'];
	$order = $vars['order'];

	$keywords = explode(" ", $searchstring);

	$where = '';
	foreach($keywords as $keyword) {
		$where .= "subject LIKE '%$keyword%' "
			."OR description LIKE '%$keyword%'\n";
	}

	$query = 'SELECT * FROM '.SQL_PREFIX."events "
		."WHERE ($where) "
		."AND calendar = '$calendar_name' "
		."AND enddate >= DATE '$start' "
		."AND startdate <= DATE '$end'"
		."ORDER BY $sort $order";

	$result = $db->Execute($query)
		or db_error(_('Encountered an error while searching.', $query);


	$html =  tag('table',
                        attributes('class="phpc-main"'),
			tag('caption', _('Search Results')),
			tag('thead',
				tag('tr',
					tag('th', _('Subject')),
					tag('th', _('Date Time')),
					tag('th', _('Description')))));
	while ($row = $result->FetchRow()) {
		$name = stripslashes($row['uid']);
		$subject = stripslashes($row['subject']);
		$desc = nl2br(stripslashes($row['description']));
		$desc = parse_desc($desc);

		$html[] = tag('tr',
				tag('td', attributes('class="phpc-list"'),
					tag('strong',
						create_action_link($subject,
							'display', $row['id'])
					   )),
				tag('td', attributes('class="phpc-list"'),
                                        $row['startdate'] . ' ' .
					formatted_time_string($row['starttime'],
						$row['eventtype'])),
				tag('td', attributes('class="phpc-list"'),
                                        $desc));
	}

	if(sizeof($html) == 0) {
		$html[] = tag('tr',
				tag('td', attributes('colspan="3"'),
					tag('strong', _('No events.'))));
	}

	return $html;
}

function search_form()
{
	global $day, $month, $year;

	$html_table = tag('table', attributes('class="phpc-main"'),
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
					create_hidden('action', 'search'))),
			tag('tr',
				tag('td', _('From') . ': '),
				tag('td',
					create_select('sday', 'day', $day),
					create_select('smonth', 'month', $month),
					create_select('syear', 'year', $year))),
			tag('tr',
				tag('td', _('To') . ': '),
				tag('td',
					create_select('eday', 'day', $day),
					create_select('emonth', 'month', $month),
					create_select('eyear', 'year', $year))),
			tag('tr',
				tag('td', _('Sort By') . ': '),
				tag('td',
					tag('select', attributes('name="sort"'),
						tag('option',
							attributes('value="startdate"'),
							_('Start Date')),
						tag('option',
							attributes('value="subject"'),
							_('Subject'))))),
			tag('tr',
				tag('td', _('Order') . ': '),
				tag('td',
					tag('select',
						attributes('name="order"'),
						tag('option', attributes('value="ASC"'),
							_('Ascending')),
						tag('option', attributes('value="DES"'),
							_('Decending'))))));
	return tag('form', attributes("action=\"$_SERVER[SCRIPT_NAME]\"",
                                'method="post"'), $html_table);
}

function search()
{
	global $vars;

	if(isset($vars['searchstring'])) return search_results();
	return search_form();
}

?>

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

function options()
{
	global $calendar_name, $vars, $db;

	//Check password and username
	if(isset($vars['submit'])){

		$query = "UPDATE ".SQL_PREFIX."calendars SET\n";

		if(isset($vars['hours_24']))
			$query .= "hours_24 = 1,\n";
		else
			$query .= "hours_24 = 0,\n";

		if(isset($vars['start_monday']))
			$query .= "start_monday = 1,\n";
		else
			$query .= "start_monday = 0,\n";

		if(isset($vars['translate']))
			$query .= "translate = 1,\n";
		else
			$query .= "translate = 0,\n";

		$calendar_title = $vars['calendar_title'];

		$query .= "anon_permission = '$vars[anon_perm]',\n"
			."calendar_title = '$calendar_title'\n"
			."WHERE calendar=$calendar_name;";

		if(check_user()){
			$result = $db->Execute($query);
			if(!$result) {
				db_error("Error reading options", $query);
			}

			return tag('div', _('Updated options'));
		}
		return tag('div', _('Permission denied'));
	}
	return options_form();
}


function options_form()
{
	global $config;

	return tag('form', attributes("action=\"$_SERVER[SCRIPT_NAME]\"",
                                'method="post"'),
			tag('table', attributes('class="phpc-main"'),
				tag('caption', _('Options')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('action', 'options'),
							create_submit(_('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', _('Start Monday').':'),
						tag('td', create_checkbox('start_monday', '1',
								$config['start_monday']))),
					tag('tr',
						tag('th', _('24 hour').':'),
						tag('td', create_checkbox('hours_24', '1',
								$config['hours_24']))),
					tag('tr',
						tag('th', _('Translate').':'),
						tag('td', create_checkbox('translate', '1',
								$config['translate']))),
					tag('tr',
						tag('th', _('Calendar Title').':'),
						tag('td', create_text('calendar_title',
								$config['calendar_title']))),
					tag('tr',
						tag('th', _('Anonymous Permission').':'),
						tag('td', create_select('anon_perm', 'anon_perm',
								$config['anon_permission']))))));
}

?>

<?php 
/*
   Copyright 2002 - 2005 Sean Proctor

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

define('CHECK', 1);
define('TEXT', 2);
define('DROPDOWN', 3);

function admin()
{
        if(!check_user()) {
                soft_error(_('You must be logged in'));
        }

	return tag('div', options_form(), new_user_form());
}

function new_calendar_form()
{
	global $phpc_script;

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes('class="phpc-main"'),
				tag('caption', _('Create New User')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('action', 'new_calendar_submit'),
							create_submit(_('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', _('Calendar Name').':'),
						tag('td', create_text('name'))),
					tag('tr',
						tag('th', _('Calendar Owner').':'),
						tag('td', create_text('owner'))))));
}

function new_user_form()
{
	global $phpc_script;

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes('class="phpc-main"'),
				tag('caption', _('Create New User')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('action', 'new_user_submit'),
							create_submit(_('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', _('User Name').':'),
						tag('td', create_text('user_name'))),
					tag('tr',
						tag('th', _('Password').':'),
						tag('td', create_password('password1'))),
					tag('tr',
						tag('th', _('Confirm Password').':'),
						tag('td', create_password('password2'))))));
}

?>

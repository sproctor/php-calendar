<?php 
/*
   Copyright 2002 - 2005 Sean Proctor, Nathan Poiro

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

function login($calendar)
{
	$html = tag('div');

	$user = phpc_get_user();

	if($user->attempted_login()) {

		if($user->logged_in()) {
			$arguments = array();
			if(isset($calendar->vars['lastaction'])) {
				$arguments[] = "action={$calendar->vars['lastaction']}";
				unset($calendar->vars['lastaction']);
			}

			foreach($calendar->vars as $key => $var) {
				if(in_array($key, $calendar->persistent_vars)
						&& !isset($arguments, $key)) {
					$arguments[] = "$key=$var";
				}
			}

			$string = "";
			if(sizeof($arguments) > 0)
				$string .= implode('&', $arguments);

			$calendar->redirect($string);
			return tag('h2', _('Loggin in...'));
		}

		$html->add(tag('h2', _('Sorry, Invalid Login')));

	}

	$html->add(login_form($calendar));
	return $html;
}

function login_form($calendar)
{
        $submit_data = tag('td', attributes('colspan="2"'),
                                create_hidden('action', 'login'),
                                create_submit(_('Submit')));

        foreach($calendar->vars as $key => $var) {
                if(in_array($key, $calendar->persistent_vars))
                        $submit_data->prepend(create_hidden($key, $var));
        }

	return tag('form', attributes("action=\"{$calendar->script}\"",
				'method="post"'),
		tag('table', attributes('class="phpc-main"'),
			tag('caption', _('Log in')),
			tag('tfoot',
				tag('tr', $submit_data)),
			tag('tbody',
				tag('tr',
					tag('th', _('Username').':'),
					tag('td', create_input('username'))),
				tag('tr',
					tag('th', _('Password').':'),
					tag('td', create_password
                                                ('password'))))));
}

?>

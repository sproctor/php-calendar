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

function login()
{
	global $vars, $day, $month, $year, $user, $password;

	$html = tag('div');
        if(array_key_exists('lastaction', $vars)) {
                $lastaction = $vars['lastaction'];
        } else {
                $lastaction = 'display';
        }

	//Check password and username
	if(isset($vars['username'])){
		$user = $vars['username'];
		$password = $vars['password'];

		if(check_user()){
                        $_SESSION['user'] = $user;
                        $_SESSION['password'] = $password;
                        if(!empty($vars['day'])) {
                                $day_string = "&day=$day";
                        } else {
                                $day_string = '';
                        }
			header("Location: $_SERVER[SCRIPT_NAME]?"
                                        ."action=$lastaction$day_string"
                                        ."&month=$month&year=$year");
			return tag('h2', _('Loggin in...'));
		}

		$html[] = tag('h2', _('Sorry, Invalid Login'));

	}

	$html[] = login_form($lastaction);
	return $html;
}


function login_form($lastaction)
{
	return tag('form', attributes("action=\"$_SERVER[SCRIPT_NAME]\"",
				'method="post"'),
		tag('table', attributes('class="phpc-main"'),
			tag('caption', _('Log in')),
			tag('tfoot',
				tag('tr',
					tag('td', attributes('colspan="2"'),
						create_hidden('lastaction', $lastaction),
						create_hidden('action', 'login'),
						create_submit(_('Submit'))))),
			tag('tbody',
				tag('tr',
					tag('th', _('Username').':'),
					tag('td', create_text('username'))),
				tag('tr',
					tag('th', _('Password').':'),
					tag('td', create_password('password'))))));
}

?>

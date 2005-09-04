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

	//Check password and username
	if(isset($calendar->vars['username'])) {
                if(!isset($calendar->vars['password']))
                        $calendar->vars['password'] = '';

		$user = $calendar->vars['username'];
		$password = $calendar->vars['password'];

		if($calendar->verify_user($user, $password)) {
                        $calendar->session['username'] = $user;
                        $calendar->session_write_close();

                        $string = "Location: {$calendar->script}";

                        if(isset($calendar->vars['lastaction'])) {
                                $arguments[] = "action={$calendar->vars['lastaction']}";
                                unset($calendar->vars['lastaction']);
                        }

                        $arguments = array();
                        foreach($calendar->vars as $key => $var) {
                                if(in_array($key, $calendar->persistent_vars))
                                        $arguments[] = "$key=$var";
                        }

                        if(sizeof($arguments) > 0)
                                $string .= '?' . implode('&', $arguments);

                        header($string);
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

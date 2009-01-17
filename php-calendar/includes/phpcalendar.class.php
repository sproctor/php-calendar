<?php
/*
   Copyright 2007 Sean Proctor

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

/*
   this file contains the calendar interface for use by people embedding our
   code and in index.php
*/

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

require_once($phpc_root_path . 'includes/html.php');
require_once($phpc_root_path . 'includes/helpers.php');
require_once($phpc_root_path . 'includes/globals.php');
require_once($phpc_root_path . 'includes/common.php');
require_once($phpc_root_path . 'includes/phpcdatabase.class.php');
require_once($phpc_root_path . 'includes/phpcuser.class.php');

class PhpCalendar {
        var $vars = false;
        var $session = false;
        var $calendar = false;
	var $self;

        // event variables
        var $timestamp = false;
        var $day = false;
        var $month = false;
        var $year = false;

        // internal variables
        var $assured = false;
        var $persistent_vars = array('day', 'month', 'year', 'lastaction',
                        'cid', 'id');

        function PhpCalendar($self) {
                $this->self = $self;
		
                $this->vars = &$_REQUEST;
		$this->session = &$_SESSION;

		/*echo "<pre>VARS:\n";
		foreach($this->vars as $var => $val) {
			echo "$var = $val\n";
		}
		foreach($_SESSION as $var => $val) {
			echo "$var = $val\n";
		}
		echo "</pre>";*/
        }

        // set the session to a non-default location
        function set_session(&$session) {
                $this->session = &$session;
        }

        // set the vars to a non-default location
        function set_vars(&$vars) {
                $this->vars = &$vars;
        }

        function create_link($contents, $variables, $attrs = NULL,
                        $blacklist = NULL) {
                //$variables = array_merge($this->vars, $variables);

                $str = $this->self;
                if(!empty($variables)) {
                        $results = array();
                        foreach($variables as $key => $value) {
                                if($blacklist !== NULL
                                                && in_array($key, $blacklist))
                                        continue;
                                $results[] = "$key=$value";
                        }
                        $str .= '?' . implode('&amp;', $results);
                }

                return tag('a', attributes("href=\"$str\"", $attrs), $contents);
        }

        // creates a form. scans the form for any missing variables and adds
        // them to the top
        function create_form()
        {
                $args = func_get_args();

                $blacklist = array_shift($args);

                $tag = tag('form', attributes("action=\"{$this->self}\""),
                                $args);

                $blacklist = array_merge($blacklist, get_input_names($tag));

                foreach($this->vars as $name => $value) {
                        if(!in_array($name, $blacklist)) {
                                $tag->prepend(create_hidden($name, $value));
                        }
                }

                return $tag;
        }

        function assure_data() {
                global $phpc_root_path;

                if($this->assured) return;
                $this->assured = true;

                if($this->session === false) {
                        session_start();
                        $this->session = &$_SESSION;
                }

                // Make the database connection.
                $this->db = phpc_get_db();

                // set $day/$month/$year as best as we can
                if($this->year !== false)
                        $year = $this->year;
                elseif(isset($this->vars['year']))
                        $year = $this->vars['year'];
                else
                        $year = date('Y');

                if($this->month !== false)
                        $month = $this->month;
                elseif(isset($this->vars['month']))
                        $month = $this->vars['month'];
                else
                        $month = date('n');

                if($this->day !== false)
                        $day = $this->day;
                elseif(isset($this->vars['day']))
                        $day = $this->vars['day'];
                elseif($month == date('n') && $year == date('Y'))
                        $day = date('j');
                else
                        $day = 1;

                if($this->timestamp === false) {
                        $this->timestamp = mktime(0, 0, 0, $month, $day, $year);
                        if($this->timestamp <= 0)
                                soft_error(_('Could not create a valid time from the parameters given.'));
                }

                $this->year = date('Y', $this->timestamp);
                $this->month = date('n', $this->timestamp);
                $this->day = date('j', $this->timestamp);

                // find the calendar
                if($this->calendar === false) {
                        if(!empty($this->vars['name'])) {
                                $this->calendar = $this->db>get_calendar_by_name
					($this->vars['name']);
                        } elseif(!empty($this->vars['cid'])) {
                                $this->calendar = $this->db->get_calendar_by_id
					($this->vars['cid']);
                        } else {
                                $this->calendar = $this->db->get_calendar_by_id
					(0);
                        }
                }

                if($this->get_config('translate') && !defined('NO_GETTEXT')) {
                        if(isset($vars['lang'])) {
                                $lang = substr($vars['lang'], 0, 2);
                                setcookie('lang', $lang);
                        } elseif(isset($_COOKIE['lang'])) {
                                $lang = substr($_COOKIE['lang'], 0, 2);
                        } elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                                $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],
                                                0, 2);
                        } else {
                                $lang = 'en';
                        }

                        switch($lang) {
                                case 'de':
                                        putenv("LANGUAGE=de_DE");
                                        putenv("LANG=de_DE");
                                        setlocale(LC_ALL, 'de_DE');
                                        break;
                                case 'en':
                                        setlocale(LC_ALL, 'en_US');
                                        break;
                        }

                        bindtextdomain('messages', $phpc_root_path . 'locale');
                        textdomain('messages');
                }
        }

        /* The following functions create_*_link are just wrappers to
         * create_link to avoid having to manually create the array for basic
         * usage
         */
        function create_action_link($text, $action, $attribs = NULL) {
                return tag('div', attributes('class="phpc-menu-item"'),
				$this->create_link($text,
					array('action' => $action), $attribs));
        }

        function create_event_link($text, $action, $id, $attribs = NULL) {
                return $this->create_link($text, array('action' => $action,
                                        'eventid' => $id), $attribs);
        }

        function create_date_link($text, $action, $year, $month = false,
                        $day = false, $attribs = NULL) {
                $array = array('year' => $year);
                $blacklist = array();

                if($month !== false) $array['month'] = $month;
                else $blacklist[] = 'month';

                if($day !== false) $array['day'] = $day;
                else $blacklist[] = 'day';

                return $this->create_link($text, $array, $attribs, $blacklist);
        }

        // creates the navbar for the top of the calendar
        // returns HTML data for the navbar
	function sidebar() {
                $this->assure_data();

		$user = phpc_get_user();

		$action = $this->get_action();

                $html = tag('div', attributes('class="phpc-sidebar"'));

                // adding a new line after each link so they get some separation
                if(/*can_add_event() && */ $action != 'add') { 
                        $html->add($this->create_action_link(_('Add Event'),
						'event_form'));
                }

                if($action != 'search') {
                        $html->add($this->create_action_link(_('Search'),
                                                'search'));
                }

                if(!empty($this->vars['day']) || !empty($this->vars['eventid'])
                                || $action != 'display') {
                        $html->add($this->create_action_link(_('View Month'),
                                                'display'));
                }

                if($action != 'display' || !empty($this->vars['id'])) {
                        $html->add($this->create_action_link(_('View date'),
                                                'display'));
                }

                if($user->logged_in()) {
                        $html->add($this->create_action_link(_('Log out'),
						'logout'));
                } else {
                        $html->add($this->create_link(_('Log in'),
                                                array('action' => 'login',
                                                        'lastaction'
                                                        => $action)), "\n");
                }

                if($user->is_admin() && $action != 'admin') {
                        $html->add($this->create_action_link(_('Admin'),
                                                'admin'), "\n");
                }

                return $html;
	}


        function get_vars() {
                $this->assure_data();

                return $this->vars;
        }

        function get_day() {
                $this->assure_data();

                return $this->day;
        }

        function get_month() {
                $this->assure_data();

                return $this->month;
        }

        function get_year() {
                $this->assure_data();

                return $this->year;
        }

        function get_config($name = false) {
                $this->assure_data();

                if($name === false) return $this->calendar;

                return $this->calendar[$name];
        }

	function get_id() {
		$this->assure_data();
		return $this->get_config('calendarID');
	}

	function get_current_event()
	{
		if(!isset($this->vars['eventid'])) {
			soft_error(_("No event matches that ID."));
		}
		$user = phpc_get_user();
		return $this->db->get_event_by_id($this->vars["eventid"],
				$user->id);
	}

	function redirect($location)
	{
		header("Location: {$this->self}?$location");
		exit;
	}

        function session_write_close() {
                session_write_close();
        }

	function get_action()
	{
		if(empty($this->vars['action'])) {
			$action = 'display';
		} else {
			$action = $this->vars['action'];
		}

		return $action;
	}

	function display() {
		global $phpc_root_path;

		$this->assure_data();

		$legal_actions = array('event_form', 'event_delete', 'display',
				'event_submit', 'search', 'login', 'logout',
				'admin', 'options_submit', 'new_user_submit');

		$action = $this->get_action();

		if(!in_array($action, $legal_actions, true)) {
			soft_error(_('Invalid action: '.$action));
		}

                require_once($phpc_root_path . "includes/$action.php");
		return call_user_func_array($action, array(&$this));
	}
}

function phpc_get($self = false)
{
	static $phpc;

	if(!isset($phpc)) {
		if($self === false) soft_error(_('No script name specified when initializing calendar.'));
		else $phpc = new PhpCalendar ($self);
	}

	return $phpc;
}
?>

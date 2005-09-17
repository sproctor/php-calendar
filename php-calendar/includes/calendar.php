<?php
/*
   Copyright 2005 Sean Proctor

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

require_once($phpc_root_path . 'adodb/adodb.inc.php');
require_once($phpc_root_path . 'includes/html.php');
require_once($phpc_root_path . 'includes/helpers.php');
require_once($phpc_root_path . 'includes/globals.php');

class Calendar {
        var $name = false;
        var $id = false;
        var $db = false;
        var $username = false;
        var $uid = false;
        var $vars = false;
        var $session = false;
        var $config = false;

        // event variables
        var $event_id = false;
        var $timestamp = false;
        var $day = false;
        var $month = false;
        var $year = false;

        // internal variables
        var $assured = false;
        var $persistent_vars = array('day', 'month', 'year', 'lastaction',
                        'cid', 'id');

        function Calendar($script, $vars) {
                $this->vars = $vars;
                $this->script = $script;
        }

        // set the user
        function set_username($user) {
                $this->username = $user;
        }

        // set the session to a non-default location
        function set_session(&$session) {
                $this->session = &$session;
        }

        function get_username() {
                $this->assure_data();

                return $this->username;
        }

        function logged_in() {
                return $this->get_username() != 'anonymous';
        }

        // return the UID for the current user.
        function get_uid() {
                $this->assure_data();

                return $this->uid;

        }

        function verify_user($user, $password)
        {
                $this->assure_data();

                $passwd = md5($password);

                $query= "SELECT uid FROM ".SQL_PREFIX."users\n"
                        ."WHERE username='$user' "
                        ."AND password='$passwd' ";

                $result = $this->db->Execute($query)
                        or $this->db_error($query);

                $row = $result->FetchRow();
                if(!$row) return false;

                $this->username = $user;
                $this->uid = $row['uid'];
                $this->session['user'] = $this->username;
                $this->session['uid'] = $this->uid;

                return true;
        }

        function create_link($contents, $variables, $attrs = NULL,
                        $blacklist = NULL) {
                $variables = array_merge($this->vars, $variables);

                $str = $this->script;
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

                $tag = tag('form', attributes("action=\"{$this->script}\""),
                                $args);

                $blacklist = array_merge($blacklist, get_input_names($tag));

                foreach($this->vars as $name => $value) {
                        if(!in_array($name, $blacklist)) {
                                $tag->prepend(create_hidden($name, $value));
                        }
                }

                return $tag;
        }

        // called when there is an error involving the DB
        function db_error($query = false)
        {
                $string = "<h3>"._('Error in SQL query')."</h3>\n"
                        ."<p>".$this->db->ErrorNo().': '.$this->db->ErrorMsg()
                        ."</p>\n";
                if($query) $string .= "<h3>"._('SQL query')
                        .":</h3><p>$query</p>\n";
                soft_error($string);
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
                $this->db = NewADOConnection(SQL_TYPE);
                if(!$this->db->Connect(SQL_HOST, SQL_USER, SQL_PASSWD,
                                        SQL_DATABASE)) {
                        $this->db_error();
                }

                if($this->username === false)  {
                        if(empty($this->session['username'])) {
                                $this->username = 'anonymous';
                        } else {
                                $this->username = $this->session['username'];
                        }
                }

                if($this->uid === false) {
                        $query= "SELECT uid FROM ".SQL_PREFIX."users\n"
                                ."WHERE username = '{$this->username}'";

                        $result = $this->db->Execute($query)
                                or $this->db_error($query);

                        $row = $result->FetchRow()
                                or soft_error(_('Invalid username'));

                        $this->uid = $row['uid'];
                }

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
                /* step 1: check if id or name are set, if not set one of them
                 * from the variables */
                if($this->name === false && $this->id === false) {
                        if(!empty($this->vars['name'])) {
                                $this->name = $this->vars['name'];
                        } elseif(!empty($this->vars['cid'])) {
                                $this->id = $this->vars['cid'];
                        } else {
                                $this->id = 0;
                        }
                }

                /* step 2: set the other one */
                if($this->id === false) {
                        $query = "SELECT id FROM ".SQL_PREFIX."calendars\n"
                                ."WHERE name='{$this->name}'";
                        $result = $this->db->execute($query)
                                or $this->db_error($query);
                        $row = $result->FetchRow()
                                or soft_error(_('Could not find a calendar by that name.'));
                        $this->id = $row['id'];
                }

                if($this->config === false) {
                        $query = "SELECT * from ".SQL_PREFIX."calendars\n"
                                ."WHERE id={$this->id}\n"
                                ."LIMIT 0,1";

                        $result = $this->db->Execute($query)
                                or $this->db_error($query);

                        $this->config = $result->FetchRow($result)
                                or soft_error(_('No configuration found for this calendar.'));
                }

                if($this->config['translate'] && !defined('NO_GETTEXT')) {
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

        // returns all the events for a particular day
        function get_events_by_date($day = false, $month = false,
                        $year = false) {
                $this->assure_data();

                if($day === false) $day = $this->day;
                if($month === false) $day = $this->day;
                if($year === false) $day = $this->day;

                $startdate = $this->db->SQLDate('Y-m-d',
                                'occurrences.start_date');
                $enddate = $this->db->SQLDate('Y-m-d', 'occurrences.end_date');
                $date = "DATE '" . date('Y-m-d', mktime(0, 0, 0, $month, $day,
                                        $year)) . "'";
                // day of week
                $dow_date = $this->db->SQLDate('w', $date);
                // day of month
                $dom_date = $this->db->SQLDate('d', $date);

                $query = 'SELECT * FROM '.SQL_PREFIX."events AS events,
                        ".SQL_PREFIX."occurrences AS occurrences
                                WHERE occurrences.event_id=events.id
                                AND (occurrences.start_date IS NULL
                                                OR $date >= $startdate)
                                AND (occurrences.end_date IS NULL
                                                OR $date <= $enddate)
                                AND (occurrences.day_of_week IS NULL
                                                OR occurrences.day_of_week
                                                = $dow_date)
                                AND (occurrences.day_of_month IS NULL
                                                OR occurrences.day_of_month
                                                = $dom_date)
                                AND (occurrences.month IS NULL
                                                OR occurrences.month = $month)
                                AND (occurrences.nth_in_month IS NULL
                                                OR occurrences.nth_in_month =
                                                FLOOR(MOD($dom_date, 7)))
                                AND events.calendar = {$this->id}
                                ORDER BY events.time";

                $result = $this->db->Execute($query)
                        or $this->db_error($query);

                return $result;
        }

        // returns the event that for $event_id
        function get_event_by_id($event_id = false) {
                $this->assure_data();

                if($event_id === false) $event_id = $this->event_id;

                $query = "SELECT events.*,
                        ".$db->SQLDate('Y', "occurrences.start_date")." AS year,
                        ".$db->SQLDate('m', "occurrences.start_date")
                                ." AS month,
                        ".$db->SQLDate('d', "occurrences.start_date")." AS day,
                        ".$db->SQLDate('Y', "occurrences.end_date")
                                ." AS end_year,
                        ".$db->SQLDate('m', "occurrences.end_date")
                                ." AS end_month,
                        ".$db->SQLDate('d', "occurrences.end_date")
                                ." AS end_day,
                        users.username
                                FROM ".SQL_PREFIX."events AS events,
                        ".SQL_PREFIX."users AS users,
                        ".SQL_PREFIX."occurrences AS occurrences
                                WHERE events.id = $event_id
                                AND events.uid = users.uid
                                AND occurrences.event_id = events.id
                                AND events.calendar = {$this->id}
                                LIMIT 0,1";

                $result = $db->Execute($query) or $this->db_error($query);

                $event = $result->FetchRow() or
                        soft_error(_('No event with that id.'));

                return array_map('stripslashes', $event);
        }

        /* The following functions create_*_link are just wrappers to
         * create_link to avoid having to manually create the array for basic
         * usage
         */
        function create_action_link($text, $action, $attribs = NULL) {
                return $this->create_link($text, array('action' => $action),
                                $attribs);
        }

        function create_id_link($text, $action, $id, $attribs = NULL) {
                return $this->create_link($text, array('action' => $action,
                                        'id' => $id), $attribs);
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

        function can_add_event() {
                return $this->get_config('anon_permission') || check_user();
        }

        // creates the navbar for the top of the calendar
        // returns XHTML data for the navbar
        function navbar($action) {
                $this->assure_data();

                $html = tag('div', attributes('class="phpc-navbar"'));

                // adding a new line after each link so they get some separation
                if(/*can_add_event() && */ $action != 'add') { 
                        $html->add($this->create_action_link(_('Add Event'),
                                                'event_form'), "\n");
                }

                if($action != 'search') {
                        $html->add($this->create_action_link(_('Search'),
                                                'search'), "\n");
                }

                if(!empty($this->vars['day']) || !empty($this->vars['id'])
                                || $action != 'display') {
                        $html->add($this->create_action_link(_('View Month'),
                                                'display'), "\n");
                }

                if($action != 'display' || !empty($this->vars['id'])) {
                        $html->add($this->create_action_link(_('View date'),
                                                'display'), "\n");
                }

                if($this->logged_in()) {
                        $html->add($this->create_link(_('Log out'),
                                                array('action' => 'logout',
                                                        'lastaction'
                                                        => $action)), "\n");
                } else {
                        $html->add($this->create_link(_('Log in'),
                                                array('action' => 'login',
                                                        'lastaction'
                                                        => $action)), "\n");
                }

                if($this->logged_in() && $action != 'admin') {
                        $html->add($this->create_action_link(_('Admin'),
                                                'admin'), "\n");
                }

                if(isset($this->var['display'])
                                && $this->var['display'] == 'day') {
                        $monthname = month_name($this->month);

                        $lasttime = mktime(0, 0, 0, $this->month,
                                        $this->day - 1, $this->year);
                        $lastday = date('j', $lasttime);
                        $lastmonth = date('n', $lasttime);
                        $lastyear = date('Y', $lasttime);
                        $lastmonthname = month_name($lastmonth);

                        $nexttime = mktime(0, 0, 0, $this->month,
                                        $this->day + 1, $this->year);
                        $nextday = date('j', $nexttime);
                        $nextmonth = date('n', $nexttime);
                        $nextyear = date('Y', $nexttime);
                        $nextmonthname = month_name($nextmonth);

                        $html->prepend($this->create_date_link(
                                                "$lastmonthname $lastday",
                                                'display', $lastyear,
                                                $lastmonth, $lastday), "\n");
                        $html->add($this->create_date_link(
                                                "$nextmonthname $nextday",
                                                'display', $nextyear,
                                                $nextmonth, $nextday), "\n");
                }

                return $html;
        }

        function get_vars() {
                $this->assure_data();

                return $this->vars;
        }

        function get_event_id() {
                $this->assure_data();

                return $this->event_id;
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

                if($name === false) return $this->config;

                return $this->config[$name];
        }

        function session_write_close() {
                session_write_close();
        }

        function display() {
                global $phpc_root_path;

                require_once($phpc_root_path . 'includes/display.php');
                $this->assure_data();

                return display($this);
        }

        function event_form() {
                global $phpc_root_path;

                require_once($phpc_root_path . 'includes/event_form.php');
                $this->assure_data();

                return event_form($this);
        }

        function event_submit() {
                global $phpc_root_path;

                require_once($phpc_root_path . 'includes/event_submit.php');
                $this->assure_data();

                return event_submit($this);
        }

        function login() {
                global $phpc_root_path;

                require_once($phpc_root_path . 'includes/login.php');
                $this->assure_data();

                return login($this);
        }

        function logout() {
                global $phpc_root_path;

                require_once($phpc_root_path . 'includes/logout.php');
                $this->assure_data();

                return logout($this);
        }
}

?>

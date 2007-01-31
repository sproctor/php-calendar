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

require_once($phpc_root_path . 'adodb/adodb.inc.php');
require_once($phpc_root_path . 'includes/html.php');
require_once($phpc_root_path . 'includes/helpers.php');
require_once($phpc_root_path . 'includes/globals.php');

class Calendar {
        var $name = false;
        var $db = false;

        function Calendar($name, $db) {
		$this->name = $name;
		$this->db = $db;
        }

        // returns all the events for a particular day
        function get_events_by_date($day, $month, $year) {

                $startdate = $this->db->SQLDate('Y-m-d',
                                'occurrences.start_date');
                $enddate = $this->db->SQLDate('Y-m-d', 'occurrences.end_date');
                $date = "DATE '" . date('Y-m-d', mktime(0, 0, 0, $month, $day,
                                        $year)) . "'";
                // day of month
                $dom_date = $this->db->SQLDate('d', $date);

                $query = "SELECT * FROM ".SQL_PREFIX."event AS event,
                        ".SQL_PREFIX."occurrence AS occurrence,
			".SQL_PREFIX."group AS group
                                WHERE eventid = event.id
				AND groupid = group.id
                                AND (startdate IS NULL OR $date >= $startdate)
                                AND (enddate IS NULL OR $date <= $enddate)
                                AND (dayofmonthlower IS NULL
						OR dayofmonthlower <= $dom_date)
                                AND (dayofmonthupper IS NULL
						OR dayofmonthupper >= $dom_date)
                                AND (month IS NULL OR month = $month)
				AND (daysbetween IS NULL
						OR DATEDIFF(startdate, $date)
						% daysbetween = 0)
                                AND calendarid = {$this->id}
				GROUP BY eventid
                                ORDER BY time";

                $result = $this->db->Execute($query)
                        or $this->db_error($query);

                return $result;
        }

        // returns the event that for $event_id
        function get_event_by_id($event_id) {

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

}

?>

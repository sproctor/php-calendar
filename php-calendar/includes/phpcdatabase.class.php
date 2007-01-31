<?php

require_once 'adodb/adodb.inc.php';
require_once 'config.php';

class PhpcDatabase {
	var $db;

	function PhpcDatabase()
	{
                $this->db = NewADOConnection(SQL_TYPE);
                if(!$this->db->Connect(SQL_HOST, SQL_USER, SQL_PASSWD,
                                        SQL_DATABASE)) {
                        $db_error();
                }

	}

        function get_events_by_date($day, $month, $year, $calendarid, $userid)
	{
                $startdate = $this->db->SQLDate('Y-m-d', 'startdate');
                $enddate = $this->db->SQLDate('Y-m-d', 'enddate');
                $date = "DATE '" . date('Y-m-d', mktime(0, 0, 0, $month, $day,
                                        $year)) . "'";
                // day of month
                $dom_date = $this->db->SQLDate('d', $date);

                $query = "SELECT event.title,event.description,"
			."event.id AS eventid,occurrence.time,"
			."occurrence.duration\n"
			."FROM ".SQL_PREFIX."event AS event\n"
                        ."INNER JOIN ".SQL_PREFIX."occurrence AS occurrence"
			."	ON (eventid = event.id)\n"
			."WHERE calendarid = $calendarid"
			."	AND (startdate IS NULL\n"
			."		OR $date >= $startdate)\n"
			."	AND (enddate IS NULL OR $date <= $enddate)\n"
			."	AND (dayofmonthlower IS NULL\n"
			."		OR dayofmonthlower <= $dom_date)\n"
			."	AND (dayofmonthupper IS NULL\n"
			."		OR dayofmonthupper >= $dom_date)\n"
			."	AND (month IS NULL OR month = $month)\n"
			."	AND (daysbetween IS NULL\n"
			."		OR DATEDIFF(startdate, $date)\n"
			."			% daysbetween = 0)\n"
			."	GROUP BY eventid\n"
			."	ORDER BY time";

                $result = $this->db->Execute($query)
                        or $this->db_error($query);

                return $result;
        }

        // returns the event that for $eventid
        function get_event_by_id($eventid, $userid) {
                $query = "SELECT event.id AS eventid,event.title,"
			."event.description,event.calendarid,"
			.$this->db->SQLDate('Y', "startdate")." AS year,"
                        .$this->db->SQLDate('m', "startdate")." AS month,"
                        .$this->db->SQLDate('d', "startdate")." AS day,"
                        .$this->db->SQLDate('Y', "enddate")." AS endyear,"
                        .$this->db->SQLDate('m', "enddate")." AS endmonth,"
                        .$this->db->SQLDate('d', "enddate")." AS endday,"
			."occurrence.id AS occurrenceid,"
                        ."user.username,user.id AS userid\n"
			."FROM ".SQL_PREFIX."event AS event\n"
                        ."LEFT JOIN ".SQL_PREFIX."user AS user"
			." ON (userid=user.id)\n"
                        ."LEFT JOIN ".SQL_PREFIX."occurrence AS occurrence\n"
			." ON (eventid=event.id)\n"
			."WHERE event.id=$eventid";

                $result = $this->db->Execute($query) or $this->db_error($query);

                $event = $result->FetchRow() or
                        soft_error(_('No event with that id.'));

		if($event["username"] === NULL) {
			soft_error(_('No user associated with that event.'));
		}
		//echo "<pre>"; print_r($event); echo "</pre>";
		if($event["occurrenceid"] === NULL) {
			soft_error(_('No occurrences associated with that event.'));
		}
                return array_map('stripslashes', $event);
        }

	function delete_event($id)
	{
		$sql = 'DELETE FROM '.SQL_PREFIX ."event WHERE id='$id'";
		$result = $this->db->Execute($sql)
			or $this->db_error($sql);

		return ($this->db->Affected_Rows($result) > 0);
	}

	function get_calendar_by_id($id)
	{
		$query = "SELECT * from ".SQL_PREFIX."calendar\n"
			."WHERE id={$id}\n";

		$result = $this->db->Execute($query)
			or $this->db_error($query);

		$config = $result->FetchRow($result)
			or soft_error(_('No configuration found for this calendar.'));

		return $config;
	}

	function get_calendar_by_name($name)
	{
		$query = "SELECT * from ".SQL_PREFIX."calendar\n"
			."WHERE name={$name}\n";

		$result = $this->db->Execute($query)
			or $this->db_error($query);

		$config = $result->FetchRow($result)
			or soft_error(_('No configuration found for this calendar.'));

		return $config;
	}

        function get_userdata($username, $password)
        {
                $passmd5 = md5($password);

                $query= "SELECT * FROM ".SQL_PREFIX."user\n"
                        ."WHERE username='$username' AND passmd5='$passmd5'";

                $result = $this->db->Execute($query)
                        or $this->db_error($query);

                return $result->FetchRow();
        }

	function submit_event($event)
	{
		$event_fields = array("id", "userid", "title", "description",
				"calendarid");
		$occur = array();
		if(!empty($event["endyear"]) && !empty($event["endmonth"])
				&& !empty($event["endday"])) {
			$occur['enddate'] = $this->db->DBDate(mktime(0, 0, 0,
						$event["endmonth"],
						$event["endday"],
						$event["endyear"]));
		}

		/* if we have date-* set start and end dates to that date */
		if(!empty($event["date-year"]) && !empty($event["date-month"])
				&& !empty($event["date-day"])) {
			$occur['startdate'] = $this->db->DBDate(mktime(0, 0, 0,
						$event["date-month"],
						$event["date-day"],
						$event["date-year"]));
			$occur['enddate'] = $this->db->DBDate(mktime(0, 0, 0,
						$event["date-month"],
						$event["date-day"],
						$event["date-year"]));
		}

		if($occur["enddate"] < $occur["startdate"]) {
			soft_error(_('The start of the event cannot be after the end of the event.'));
		}

		if(!empty($event["hour"]) && !empty($event["minute"])) {
			$occur["time"] = date('H:i:s', $startstamp);
		}

		if(!empty($event["duration"])) {
			$occur["duration"] = $duration;
		}

		if($event["id"]) {
			if(false) {
				soft_error(_('You do not have permission to modify events.'));
			}
			$query = "DELETE FROM ".SQL_PREFIX."occurrence\n"
				."WHERE eventid={$event['id']}";

			$result = $this->db->Execute($query)
				or $this->db_error($query);
		} else {
			if(false) {
				soft_error(_('You do not have permission to post.'));
			}
			$event["id"] = $this->db->GenID(SQL_PREFIX
					. 'eventsequence');
		}

		/* create/modify the event */
		$sets = array();
		foreach($event_fields as $v) {
			if(!empty($event[$v])) {
				if(is_string($event[$v])) {
					if($event[$v]{0} != "'")
						$sets[] = "$v='{$event[$v]}'";
					else
						$sets[] = "$v={$event[$v]}";
				} elseif(is_int($event[$v])) {
					$sets[] = "$v={$event[$v]}";
				} else {
					soft_error(_('Unexepected type.'));
				}
			}
		}
		$query = "REPLACE INTO ".SQL_PREFIX."event\n"
			."SET ".join(",", $sets);
                $result = $this->db->Execute($query)
                        or $this->db_error($query);

		/* create the occurrence */
		$occur["eventid"] = $event["id"];
		$sets = array();
		//echo "<pre>"; print_r($occur); echo "</pre>";
		foreach($occur as $k => $v) {
			if(is_string($v)) {
				if($v{0} != "'")
					$sets[] = "$k='$v'";
				else
					$sets[] = "$k=$v";
			} elseif(is_int($v)) {
				$sets[] = "$k=$v";
			} else {
				soft_error(_('Unexepected type.'));
			}
		}
		//echo "<pre>"; print_r($sets); echo "</pre>";
		$query = "INSERT INTO ".SQL_PREFIX."occurrence\n"
			."SET ".join(",", $sets);
		//echo "<pre>$query</pre>";
                $result = $this->db->Execute($query)
                        or $this->db_error($query);

		return $occur["eventid"];
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
}

// creates and returns a static connection to the database
function phpc_get_db()
{
	static $db;

	if(!isset($db)) {
		$db = new PhpcDatabase;
	}

	return $db;
}

?>

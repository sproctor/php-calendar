<?php
/*
 * Copyright 2009 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("$phpc_includes_path/phpccalendar.class.php");
require_once("$phpc_includes_path/phpcevent.class.php");
require_once("$phpc_includes_path/phpcoccurrence.class.php");
require_once("$phpc_includes_path/phpcuser.class.php");

class PhpcDatabase {
	var $dbh;

	function PhpcDatabase()
	{
		// Make the database connection.
		$this->dbh = new mysqli(SQL_HOST, SQL_USER, SQL_PASSWD);
		$this->dbh->select_db(SQL_DATABASE);

		if(mysqli_connect_errno()) {
			soft_error("Database connect failed ("
					. mysqli_connect_errno() . "): "
					. mysqli_connect_error());
		}
	}

	// returns all the events for a particular day
	// $from and $to are timestamps only significant to the date.
	// an event that happens later in the day of $to is included
        function get_occurrences_by_date_range($cid, $from, $to)
	{
		$from_date = date('Y-m-d', $from);
		$to_date = date('Y-m-d', $to);

		$sql_events = SQL_PREFIX . "events";
		$sql_occurrences = SQL_PREFIX . "occurrences";
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

                $query = "SELECT `subject`, `description`, `$sql_events`.`eid`,"
		        ." `$sql_events`.`cid`, `oid`, `username`, `timetype`, "
			."`readonly`, "
			."`catid`, `name`, `bg_color`, `text_color`, "
			."HOUR(`starttime`) AS `starthour`, "
			."MINUTE(`starttime`) AS `startminute`, "
			."HOUR(`endtime`) AS `endhour`, "
			."MINUTE(`endtime`) AS `endminute`, "
			."YEAR(`startdate`) AS `startyear`, "
			."MONTH(`startdate`) AS `startmonth`, "
			."DAY(`startdate`) AS `startday`, "
			."YEAR(`enddate`) AS `endyear`, "
			."MONTH(`enddate`) AS `endmonth`, "
			."DAY(`enddate`) AS `endday`\n"
			."FROM `$sql_events`\n"
                        ."INNER JOIN `$sql_occurrences` USING (`eid`)\n"
			."LEFT JOIN `$users_table` on `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `$sql_events`.`cid` = '$cid'\n"
			."	AND `startdate` <= DATE('$to_date')\n"
			."	AND `enddate` >= DATE('$from_date')\n"
			."	ORDER BY `startdate`, `starttime`";

		$result = $this->dbh->query($query)
			or $this->db_error(_('Error in get_occurrences_by_date_range'),
					$query);

		$events = array();
		while($row = $result->fetch_assoc()) {
			$events[] = new PhpcOccurrence($row);
		}
		return $events;
        }

	// returns all the events for a particular day
        function get_occurrences_by_date($cid, $year, $month, $day)
	{
		$stamp = mktime(0, 0, 0, $month, $day, $year);

		return $this->get_occurrences_by_date_range($cid, $stamp,
				$stamp);
        }

	// returns the event that corresponds to $id
	function get_event_by_eid($eid)
	{
		$events_table = SQL_PREFIX . 'events';
		$occurrences_table = SQL_PREFIX . 'occurrences';
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

                $query = "SELECT `subject`, `description`, `username`, "
			."`$events_table`.`eid`, `$events_table`.`cid`, "
			."`readonly`, "
			."`catid`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `eid` = '$eid'\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_event_by_eid'),
					$query);

		$result = $sth->fetch_assoc()
			or soft_error(_("Event doesn't exist") . ": $eid");

		return new PhpcEvent($result);
	}

	// returns the category that corresponds to $tid
	function get_category($catid)
	{
		$cats_table = SQL_PREFIX . 'categories';

                $query = "SELECT `name`, `text_color`, `bg_color`, `cid`, "
			."`catid`\n"
			."FROM `$cats_table`\n"
			."WHERE `catid` = $catid";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_category'), $query);

		$result = $sth->fetch_assoc()
			or soft_error(_("Category does not exist")
					. ": $catid");

		return $result;
	}

	// returns the categories for calendar $cid
	function get_categories($cid = false)
	{
		$cats_table = SQL_PREFIX . 'categories';

		if($cid)
			$where = "WHERE `cid` = '$cid'\n";
		else
			$where = "WHERE `cid` IS NULL\n";

                $query = "SELECT `name`, `text_color`, `bg_color`, `cid`, "
			."`catid`\n"
			."FROM `$cats_table`\n"
			.$where;

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_categories'),
					$query);

		$arr = array();
		while($result = $sth->fetch_assoc()) {
			$arr[] = $result;
		}

		return $arr;
	}

	// returns the event that corresponds to $oid
	function get_occurrence_by_oid($oid)
	{
		$events_table = SQL_PREFIX . 'events';
		$occurrences_table = SQL_PREFIX . 'occurrences';
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

                $query = "SELECT `subject`, `description`, `username`, "
			."`$events_table`.`eid`, `$events_table`.`cid`, `oid`, "
			."`timetype`, `readonly`, "
			."`catid`, `name`, `bg_color`, `text_color`, "
			."HOUR(`starttime`) AS `starthour`, "
			."MINUTE(`starttime`) AS `startminute`, "
			."HOUR(`endtime`) AS `endhour`, "
			."MINUTE(`endtime`) AS `endminute`, "
			."YEAR(`startdate`) AS `startyear`, "
			."MONTH(`startdate`) AS `startmonth`, "
			."DAY(`startdate`) AS `startday`, "
			."YEAR(`enddate`) AS `endyear`, "
			."MONTH(`enddate`) AS `endmonth`, "
			."DAY(`enddate`) AS `endday`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `oid` = $oid\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_occurrence_by_oid'),
					$query);

		$result = $sth->fetch_assoc()
			or soft_error(_("Event doesn't exist") . ": $oid");

		return new PhpcOccurrence($result);
	}

        function get_occurrences_by_eid($eid)
	{
		$sql_events = SQL_PREFIX . "events";
		$sql_occurrences = SQL_PREFIX . "occurrences";
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

                $query = "SELECT `subject`, `description`, "
			."`$sql_events`.`eid`, `$sql_events`.`cid`, `oid`, "
			."`username`, `timetype`, `readonly`, "
			."`catid`, `name`, `bg_color`, `text_color`, "
			."HOUR(`starttime`) AS `starthour`, "
			."MINUTE(`starttime`) AS `startminute`, "
			."HOUR(`endtime`) AS `endhour`, "
			."MINUTE(`endtime`) AS `endminute`, "
			."YEAR(`startdate`) AS `startyear`, "
			."MONTH(`startdate`) AS `startmonth`, "
			."DAY(`startdate`) AS `startday`, "
			."YEAR(`enddate`) AS `endyear`, "
			."MONTH(`enddate`) AS `endmonth`, "
			."DAY(`enddate`) AS `endday`\n"
			."FROM `$sql_events`\n"
                        ."INNER JOIN `$sql_occurrences` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `eid` = $eid\n"
			."	ORDER BY `startdate`, `starttime`";

		$result = $this->dbh->query($query)
			or $this->db_error(_('Error in get_occurrences_by_eid'),
					$query);

		$events = array();
		while($row = $result->fetch_assoc()) {
			$events[] = new PhpcOccurrence($row);
		}
		return $events;
        }

	function delete_event($eid)
	{

		$query = 'DELETE FROM `'.SQL_PREFIX ."events`\n"
			."WHERE `eid` = '$eid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error while removing an event.'),
					$query);

		$rv = $this->dbh->affected_rows > 0;

		$this->delete_occurrences($eid);

		return $rv;
	}

	function delete_occurrences($eid)
	{
		$query = 'DELETE FROM `'.SQL_PREFIX ."occurrences`\n"
			."WHERE `eid` = '$eid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error while removing an event.'),
					$query);

		return $this->dbh->affected_rows;
	}

	function delete_calendar($id)
	{

		$query1 = 'DELETE FROM `'.SQL_PREFIX ."calendars`\n"
			."WHERE cid='$id'";
		$query2 = 'DELETE FROM `'.SQL_PREFIX ."config`\n"
			."WHERE cid='$id'";

		$sth = $this->dbh->query($query1)
			or $this->db_error(_('Error while removing a calendar'),
					$query1);
		$this->dbh->query($query2)
			or $this->db_error(_('Error while calendar config'),
					$query2);

		return $this->dbh->affected_rows > 0;
	}

	function delete_category($catid)
	{

		$query = 'DELETE FROM `'.SQL_PREFIX ."categories`\n"
			."WHERE `catid` = '$catid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error while removing category.'),
					$query);

		return $this->dbh->affected_rows > 0;
	}

	function delete_user($id)
	{

		$query1 = 'DELETE FROM `'.SQL_PREFIX ."users`\n"
			."WHERE uid='$id'";
		$query2 = 'DELETE FROM `'.SQL_PREFIX ."permissions`\n"
			."WHERE uid='$id'";

		$this->dbh->query($query1)
			or $this->db_error(_('Error while removing a calendar'),
					$query1);
		$rv = $this->dbh->affected_rows > 0;

		$this->dbh->query($query2)
			or $this->db_error(_('Error while removing '),
					$query2);

		return $rv;
	}

	function get_calendar_config($cid)
	{
		static $config = NULL;

		if ($config != NULL)
			return $config;

		// Load configuration
		$query = "SELECT * from " . SQL_PREFIX .
			"config WHERE cid=$cid";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Could not read configuration'),
					$query);

		$config = array();
		while($row = $sth->fetch_assoc()) {
			$config[$row['config_name']] = $row['config_value'];
		}

		return $config;
	}

	function get_permissions($cid, $uid)
	{
		static $perms = array();

		if (empty($perms[$cid]))
			$perms[$cid] = array();
		elseif (!empty($perms[$cid][$uid]))
			return $perms[$cid][$uid];

		$query = "SELECT * from " . SQL_PREFIX .
			"permissions WHERE `cid`='$cid' AND `uid`='$uid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Could not read permissions.'),
					$query);

		$perms[$cid][$uid] = $sth->fetch_assoc();
		return $perms[$cid][$uid];
	}

	function get_calendars()
	{
		$query = "SELECT `cid`, `config_value` AS `title`\n"
			."FROM `" . SQL_PREFIX .  "calendars`\n"
			."JOIN `" . SQL_PREFIX . "config` USING (`cid`)\n"
			."WHERE `config_name`=\"calendar_title\"";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Could not get calendars.'),
					$query);

		$calendars = array();
		while($result = $sth->fetch_assoc()) {
			$calendars[] = new PhpcCalendar($result);
		}
		return $calendars;
	}

	function get_calendar($cid)
	{
		$query = "SELECT `cid`, `config_value` AS `title`\n"
			."FROM `" . SQL_PREFIX .  "calendars`\n"
			."JOIN `" . SQL_PREFIX . "config` USING (`cid`)\n"
			."WHERE `config_name`=\"calendar_title\" "
			."AND `cid`='$cid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Could not get calendars.'),
					$query);

		return new PhpcCalendar($sth->fetch_assoc());
	}

	function get_users()
	{
		$query = "SELECT * FROM `" . SQL_PREFIX .  "users`";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Could not get user.'), $query);

		$users = array();
		while($user = $sth->fetch_assoc()) {
			$users[] = new PhpcUser($user);
		}
		return $users;
	}

	function get_users_with_permissions($cid)
	{
		$permissions_table = SQL_PREFIX . "permissions";

		$query = "SELECT `uid`, `username`, `password`, "
			."`read`, `write`, `readonly`, `modify`, "
			."`$permissions_table`.`admin` AS `admin`\n"
			."FROM `" . SQL_PREFIX . "users`\n"
			."LEFT JOIN `$permissions_table`\n"
			."USING (`uid`)\n"
			."WHERE `cid`='$cid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Could not get user.'), $query);

		$users = array();
		while($user = $sth->fetch_assoc()) {
			$users[] = $user;
		}
		return $users;
	}

	function get_user_by_name($username)
	{
		$query = "SELECT `uid`, `username`, `password`, `admin`\n"
			."FROM ".SQL_PREFIX."users\n"
			."WHERE username='$username'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_("Error getting user."), $query);

		$result = $sth->fetch_assoc();
		if($result)
			return new PhpcUser($result);
		else
			return false;
	}

	function get_user($uid)
	{
		$query = "SELECT `uid`, `username`, `password`, `admin`\n"
			."FROM ".SQL_PREFIX."users\n"
			."WHERE `uid`='$uid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_("Error getting user."), $query);

		$result = $sth->fetch_assoc();
		if($result)
			return new PhpcUser($result);
		else
			return false;
	}

	function create_user($username, $password, $make_admin)
	{
		$query = "INSERT into `".SQL_PREFIX."users`\n"
			."(`username`, `password`, `admin`) VALUES\n"
			."('$username', '$password', $make_admin)";

		$this->dbh->query($query)
			or $this->db_error(_('Error creating user.'), $query);
	}

	function create_calendar()
	{
		$query = "INSERT INTO ".SQL_PREFIX."calendars\n"
			."(`cid`) VALUE (DEFAULT)";

		$this->dbh->query($query)
			or $this->db_error(_('Error reading options'), $query);

		return $this->dbh->insert_id;
	}

	function create_config($cid, $name, $value)
	{
		$query = "INSERT INTO ".SQL_PREFIX."config\n"
			."(`cid`, `config_name`, `config_value`)\n"
			."VALUES ($cid, '$name', '$value')";

		$this->dbh->query($query)
			or $this->db_error(_('Error creating options'), $query);
	}

	function update_config($cid, $name, $value)
	{
		$query = "INSERT ".SQL_PREFIX."config\n"
			."(`cid`, `config_name`, `config_value`)\n"
			."VALUES ($cid, '$name', '$value')\n"
			."ON DUPLICATE KEY UPDATE config_value = '$value'";

		$this->dbh->query($query)
			or $this->db_error(_('Error reading options'), $query);
	}

	function create_event($cid, $uid, $subject, $description, $readonly,
			$catid = false)
	{
		$fmt_readonly = asbool($readonly);

		if(!$catid)
			$catid = 'NULL';

		$query = "INSERT INTO `" . SQL_PREFIX . "events`\n"
			."(`cid`, `owner`, `subject`, `description`, "
			."`readonly`, `catid`)\n"
			."VALUES ('$cid', '$uid', '$subject', '$description', "
			."$fmt_readonly, $catid)";

		$this->dbh->query($query)
			or $this->db_error(_('Error creating event.'), $query);

		return $this->dbh->insert_id;
	}

	function create_occurrence($eid, $startdate, $enddate, $timetype,
			$starttime = NULL, $endtime = NULL)
	{
		$fmt_startdate = date("Y-m-d", $startdate);
		$fmt_enddate = date("Y-m-d", $enddate);

		$query = "INSERT INTO `" . SQL_PREFIX . "occurrences`\n"
			."SET `eid` = '$eid', `startdate` = '$fmt_startdate', "
			."`enddate` = '$fmt_enddate', `timetype` = $timetype";
		if($starttime !== NULL)
			$query .= ", `starttime` = '$starttime'";
		if($endtime !== NULL)
			$query .= ", `endtime` = '$endtime'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error creating event.'), $query);

		return $this->dbh->insert_id;
	}

	function modify_event($eid, $subject, $description, $readonly)
	{
		$fmt_readonly = asbool($readonly);

		$query = "UPDATE " . SQL_PREFIX . "events\n"
			."SET\n"
			."subject='$subject',\n"
			."description='$description',\n"
			."`readonly`=$fmt_readonly\n"
			."WHERE eid='$eid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error modifying event.'), $query);

		return $sth->affected_rows > 0;
	}

	function create_category($cid, $name, $text_color, $bg_color)
	{
		$query = "INSERT INTO `" . SQL_PREFIX . "categories`\n"
			."(`cid`, `name`, `text_color`, `bg_color`)\n"
			."VALUES ('$cid', '$name', '$text_color', '$bg_color')";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error creating category.'),
					$query);

		return $this->dbh->insert_id;
	}

	function modify_category($catid, $name, $text_color, $bg_color)
	{
		$query = "UPDATE " . SQL_PREFIX . "categories\n"
			."SET\n"
			."`name`='$name',\n"
			."`text_color`='$text_color',\n"
			."`bg_color`='$bg_color'\n"
			."WHERE `catid`='$catid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error modifying category.'),
					$query);

		return $sth->affected_rows > 0;
	}

	function search($cid, $keywords, $start, $end, $sort, $order)
	{
		$events_table = SQL_PREFIX . 'events';
		$occurrences_table = SQL_PREFIX . 'occurrences';
		$users_table = SQL_PREFIX . 'users';

		$words = array();
		foreach($keywords as $keyword) {
			$words[] = "(subject LIKE '%$keyword%' "
				."OR description LIKE '%$keyword%')\n";
		}
		$where = implode(' AND ', $words);

                $query = "SELECT `subject`, `description`, `username`, "
			."`$events_table`.`eid`, `cid`, `timetype`, "
			."HOUR(`starttime`) AS `starthour`, "
			."MINUTE(`starttime`) AS `startminute`, "
			."HOUR(`endtime`) AS `endhour`, "
			."MINUTE(`endtime`) AS `endminute`, "
			."YEAR(`startdate`) AS `startyear`, "
			."MONTH(`startdate`) AS `startmonth`, "
			."DAY(`startdate`) AS `startday`, "
			."YEAR(`enddate`) AS `endyear`, "
			."MONTH(`enddate`) AS `endmonth`, "
			."DAY(`enddate`) AS `endday`\n"
			."FROM `$events_table` \n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` on `uid` = `owner`\n"
			."WHERE ($where) "
			."AND cid = '$cid' "
			."AND enddate >= DATE '$start' "
			."AND startdate <= DATE '$end' "
			."ORDER BY $sort $order";

		if(!($result = $this->dbh->query($query)))
			$this->db_error(_('Error during searching'), $query);

		$events = array();
		while($row = $result->fetch_assoc()) {
			$events[] = new PhpcOccurrence($row);
		}
		return $events;
	}

	function update_permissions($cid, $uid, $perms)
	{
		$names = array();
		$values = array();
		$sets = array();
		foreach($perms as $name => $value) {
			$names[] = "`$name`";
			$values[] = $value;
			$sets[] = "`$name`=$value";
		}

		$query = "INSERT INTO ".SQL_PREFIX."permissions\n"
			."(`cid`, `uid`, ".implode(", ", $names).")\n"
			."VALUES ($cid, $uid, ".implode(", ", $values).")\n"
			."ON DUPLICATE KEY UPDATE ".implode(", ", $sets);

		if(!($sth = $this->dbh->query($query)))
			$this->db_error(_('Error updating user permissions.'),
					$query);
	}

	// called when there is an error involving the DB
	function db_error($str, $query = "")
	{
		$string = "$str<br />" . $this->dbh->error;
		if($query != "") {
			$string .= "<br />"._('SQL query').": $query";
		}
		soft_error($string);
	}

}

?>

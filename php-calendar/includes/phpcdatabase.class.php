<?php
/*
 * Copyright 2012 Sean Proctor
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
	var $calendars;

	function __construct() {
		if(!defined("SQL_PORT"))
			define("SQL_PORT", ini_get("mysqli.default_port"));

		// Make the database connection.
		$this->dbh = new mysqli(SQL_HOST, SQL_USER, SQL_PASSWD,
			SQL_DATABASE, SQL_PORT);

		if(mysqli_connect_errno()) {
			soft_error("Database connect failed ("
					. mysqli_connect_errno() . "): "
					. mysqli_connect_error());
		}

		$this->dbh->set_charset("utf8");
	}

	function __destruct() {
		$this->dbh->close();
	}

	private function get_event_fields() {
		$events_table = SQL_PREFIX . "events";
		$fields = array('subject', 'description', 'owner', 'eid', 'cid',
				'readonly', 'catid');
		return "`$events_table`.`"
			. implode("`, `$events_table`.`", $fields) . "`\n";
	}

	private function get_occurrence_fields() {
		return $this->get_event_fields() . ", `time_type`, `oid`, "
			. "UNIX_TIMESTAMP(`start_ts`) AS `start_ts`, "
			. "DATE_FORMAT(`start_date`, '%Y%m%d') AS `start_date`, "
			. "UNIX_TIMESTAMP(`end_ts`) AS `end_ts`, "
			. "DATE_FORMAT(`end_date`, '%Y%m%d') AS `end_date`\n";
	}

	private function get_user_fields() {
		$users_table = SQL_PREFIX . "users";
		return "`$users_table`.`uid`, `username`, `password`, `$users_table`.`admin`, `password_editable`, `timezone`, `language`, `gid`";
	}

	// returns all the events for a particular day
	// $from and $to are timestamps only significant to the date.
	// an event that happens later in the day of $to is included
	function get_occurrences_by_date_range($cid, $from, $to)
	{
		$from_date = date('Y-m-d', $from);
		$to_date = date('Y-m-d', $to);

		$events_table = SQL_PREFIX . "events";
		$occurrences_table = SQL_PREFIX . "occurrences";
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

        $query = "SELECT " . $this->get_occurrence_fields()
			.", `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` ON `$events_table`.`catid` "
			."= `$cats_table`.`catid`\n"
			."WHERE `$events_table`.`cid` = '$cid'\n"
			."	AND IF(`start_ts`, DATE(`start_ts`), `start_date`) <= DATE('$to_date')\n"
			."	AND IF(`end_ts`, DATE(`end_ts`), `end_date`) >= DATE('$from_date')\n"
			."	ORDER BY `start_ts`, `start_date`, `oid`";

		$results = $this->dbh->query($query)
			or $this->db_error(_('Error in get_occurrences_by_date_range'),
					$query);

		return $results;
        }
		
	/* if category is visible to user id */
	function is_cat_visible($uid,$catid)
	{
	    $users_table = SQL_PREFIX . 'users';
	   	$groups_table = SQL_PREFIX . 'groups';
		
		if (is_admin()) return true;
		
		$query = "SELECT * FROM `" . $users_table. "` JOIN `" . $groups_table. "` ON (`" . $users_table. "`.gid = `" . $groups_table. "`.gid) WHERE `catid`=".$catid." AND `uid`=".$uid ;	
		
		$results = $this->dbh->query($query)
			or false;
		if (!$results) return 0;
		return $results->num_rows;
	}

	// returns all the events for a particular day
	function get_occurrences_by_date($cid, $year, $month, $day)
	{
		$stamp = mktime(0, 0, 0, $month, $day, $year);

		return $this->get_occurrences_by_date_range($cid, $stamp,
				$stamp);
        }

	// returns the event that corresponds to eid
	function get_event_by_eid($eid)
	{
		$events_table = SQL_PREFIX . 'events';
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

		$query = "SELECT " . $this->get_event_fields()
			.", `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `eid` = '$eid'\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_event_by_eid'),
					$query);

		$result = $sth->fetch_assoc()
			or soft_error(_("Event doesn't exist") . ": $eid");

		return $result;
	}

	// returns the event that corresponds to oid
	function get_event_by_oid($oid)
	{
		$events_table = SQL_PREFIX . 'events';
		$occurrences_table = SQL_PREFIX . 'occurrences';
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

		$query = "SELECT " . $this->get_event_fields()
			.", `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
			."LEFT JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `oid` = '$oid'\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_event_by_oid'),
					$query);

		$result = $sth->fetch_assoc()
			or soft_error(_("Event doesn't exist with oid")
					. ": $oid");

		return $result;
	}

	// returns the category that corresponds to $catid
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
			or soft_error(_("Category doesn't exist with catid")
					. ": $catid");
	
		$result['gid']=$this->get_groups($catid);

		return $result;
	}

function get_groups($catid)
{
	$groups_table = SQL_PREFIX . 'groups';

        $query = "SELECT `gid`\n"
			."FROM `$groups_table`\n"
			."WHERE `catid` = $catid";

		$gth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_category'), $query);
					
		$group = array();
		while($row = $gth->fetch_assoc()) {
			$group[] = $row['gid'];
		}					
	return $group;
}
	
	// returns the categories for calendar $cid
	function get_categories($cid = false)
	{
		$cats_table = SQL_PREFIX . 'categories';

		if($cid)
			$where = "WHERE `cid` = '$cid'\n";
		else
			$where = "WHERE `cid` IS NULL\n";

         $query = "SELECT `catid`\n"
			."FROM `$cats_table`\n"
			.$where;

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_categories'),
					$query);

		$arr = array();
		while($result = $sth->fetch_assoc()) {
			$arr[] = $this->get_category($result['catid']);
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

                $query = "SELECT " . $this->get_occurrence_fields()
			.", `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `oid` = '$oid'\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error in get_occurrence_by_oid'),
					$query);

		$result = $sth->fetch_assoc()
			or soft_error(_("Event doesn't exist with oid") . ": $oid");

		return new PhpcOccurrence($result);
	}

        function get_occurrences_by_eid($eid)
	{
		$events_table = SQL_PREFIX . "events";
		$occurrences_table = SQL_PREFIX . "occurrences";
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

                $query = "SELECT " . $this->get_occurrence_fields()
			.", `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `eid` = '$eid'\n"
			."	ORDER BY `start_ts`, `start_date`, `oid`";

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

	function delete_occurrence($oid)
	{
		$query = 'DELETE FROM `'.SQL_PREFIX ."occurrences`\n"
			."WHERE `oid` = '$oid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error while removing an occurrence.'),
					$query);

		return $this->dbh->affected_rows;
	}

	function delete_calendar($id)
	{

		$query1 = 'DELETE FROM `'.SQL_PREFIX ."calendars`\n"
			."WHERE cid='$id'";

		$sth = $this->dbh->query($query1)
			or $this->db_error(_('Error while removing a calendar'),
					$query1);

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
		$query = "SELECT *\n"
			."FROM `" . SQL_PREFIX .  "calendars`\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Could not get calendars.'),
					$query);

		while($result = $sth->fetch_assoc()) {
			$cid = $result["cid"];
			if(empty($this->calendars[$cid]))
				$this->calendars[$cid] = new PhpcCalendar
					($result);
		}
		return $this->calendars;
	}

	function get_calendar($cid)
	{
		if(empty($this->calendars[$cid])) {
			$query = "SELECT *\n"
				."FROM `" . SQL_PREFIX .  "calendars`\n"
				."WHERE `cid`='$cid'";

			$sth = $this->dbh->query($query)
				or $this->db_error(_('Could not get calendar.'),
					$query);
			$this->calendars[$cid] = new PhpcCalendar
				($sth->fetch_assoc());
		}

		return $this->calendars[$cid];
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
			."`permissions`.`admin` AS `calendar_admin`\n"
			."FROM `" . SQL_PREFIX . "users`\n"
			."LEFT JOIN (SELECT * FROM `$permissions_table`\n"
			."	WHERE `cid`='$cid') AS `permissions`\n"
			."USING (`uid`)\n";

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
		$query = "SELECT " . $this->get_user_fields()
			."\nFROM ".SQL_PREFIX."users\n"
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
		$query = "SELECT " . $this->get_user_fields()
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

	function create_user($username, $password, $make_admin, $gid)
	{
		$query = "INSERT into `".SQL_PREFIX."users`\n"
			."(`username`, `password`, `admin`, `gid`) VALUES\n"
			."('$username', '$password', '$make_admin', '$gid')";

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

	function update_config($cid, $name, $value)
	{
		$query = "UPDATE ".SQL_PREFIX."calendars \n"
		."SET `".$name."`='$value'\n"
		."WHERE `cid`='$cid'";

		$this->dbh->query($query)
			or $this->db_error(_('Error reading options'), $query);
	}

	function create_config($cid, $name, $value)
	{
	
		$query = "UPDATE ".SQL_PREFIX."calendars \n"
			."SET `".$name."`='$value'\n"
			."WHERE `cid`='$cid'"; 

		$this->dbh->query($query)
			or $this->db_error(_('Error creating options'), $query);
	}
	
	function set_password($uid, $password)
	{
		$query = "UPDATE `" . SQL_PREFIX . "users`\n"
			."SET `password`='$password'\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(_('Error updating password.'),
					$query);
	}

	function set_timezone($uid, $timezone)
	{
		$query = "UPDATE `" . SQL_PREFIX . "users`\n"
			."SET `timezone`='$timezone'\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(_('Error updating timezone.'),
					$query);
	}

	function set_language($uid, $language)
	{
		$query = "UPDATE `" . SQL_PREFIX . "users`\n"
			."SET `language`='$language'\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(_('Error updating language.'),
					$query);
	}

	function create_event($cid, $uid, $subject, $description, $readonly,
			$catid = false)
	{
		$fmt_readonly = asbool($readonly);

		if(!$catid)
			$catid = 'NULL';
		else
			$catid = "'$catid'";

		$query = "INSERT INTO `" . SQL_PREFIX . "events`\n"
			."(`cid`, `owner`, `subject`, `description`, "
			."`readonly`, `catid`)\n"
			."VALUES ('$cid', '$uid', '$subject', '$description', "
			."$fmt_readonly, $catid)";

		$this->dbh->query($query)
			or $this->db_error(_('Error creating event.'), $query);

		$eid = $this->dbh->insert_id;

		if($eid <= 0)
			soft_error("Bad eid creating event.");

		return $eid;
	}

	function create_occurrence($eid, $time_type, $start_ts, $end_ts)
	{

		$query = "INSERT INTO `" . SQL_PREFIX . "occurrences`\n"
			."SET `eid` = '$eid', `time_type` = '$time_type'";

		if($time_type == 0) {
			$query .= ", `start_ts` = FROM_UNIXTIME('$start_ts')"
				. ", `end_ts` = FROM_UNIXTIME('$end_ts')";
		} else {
			$start_date = date("Y-m-d", $start_ts);
			$end_date = date("Y-m-d", $end_ts);
			$query .= ", `start_date` = '$start_date'"
				. ", `end_date` = '$end_date'";
		}

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error creating occurrence.'),
					$query);

		return $this->dbh->insert_id;
	}

	function modify_occurrence($oid, $time_type, $start_ts, $end_ts)
	{

		$query = "UPDATE `" . SQL_PREFIX . "occurrences`\n"
			."SET `time_type` = '$time_type'";

		if($time_type == 0) {
			$query .= ", `start_ts` = FROM_UNIXTIME('$start_ts')"
				. ", `end_ts` = FROM_UNIXTIME('$end_ts')";
		} else {
			$start_date = date("Y-m-d", $start_ts);
			$end_date = date("Y-m-d", $end_ts);
			$query .= ", `start_date` = '$start_date'"
				. ", `end_date` = '$end_date'";
		}

		$query .= "\nWHERE `oid`='$oid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error modifying occurrence.'),
					$query);

		return $this->dbh->affected_rows > 0;
	}

	function modify_event($eid, $subject, $description, $readonly,
			$catid = false)
	{
		$fmt_readonly = asbool($readonly);

		$query = "UPDATE `" . SQL_PREFIX . "events`\n"
			."SET\n"
			."`subject`='$subject',\n"
			."`description`='$description',\n"
			."`readonly`=$fmt_readonly,\n"
			.($catid !== false ? "`catid`='$catid'\n"
				: "`catid`=NULL\n")
			."WHERE eid='$eid'";

		$this->dbh->query($query)
			or $this->db_error(_('Error modifying event.'), $query);

		return $this->dbh->affected_rows > 0;
	}

	function create_category($cid, $name, $text_color,$bg_color, $groups)
	{
		$query = "INSERT INTO `" . SQL_PREFIX . "categories`\n"
			."(`cid`, `name`, `text_color`, `bg_color`)\n"
			."VALUES ('$cid', '$name', '$text_color', '$bg_color')";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error creating category.'),
					$query);
		
		$ret_id=$this->dbh->insert_id;
		
		$group_arr=explode(',',$groups);
		
		foreach ($group_arr as $grp)
		{
		$query = "INSERT INTO `" . SQL_PREFIX . "groups`\n"
			."(`gid`, `catid`)\n"
			."VALUES ('$grp', '$ret_id')";

		$sth = $this->dbh->query($query)
			or $this->db_error(_('Error creating category.'),
					$query);
		}
		
		return $ret_id;
	}

	function modify_category($catid, $name, $text_color, $bg_color, $groups)
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

					
		$group_arr=explode(',',$groups);
		
		
		foreach ($group_arr as $grp)
		{
			/* not very clever here.. */
			$query = "SELECT * FROM `" . SQL_PREFIX . "groups` WHERE `catid`=".$catid." AND `gid`=".$grp ;	

			$results = $this->dbh->query($query)
				or false;
			if ($results->num_rows==0)
			{
				$query = "INSERT INTO `" . SQL_PREFIX . "groups`\n"
					."(`gid`, `catid`)\n"
					."VALUES ('$grp', '$catid')";

				$gth = $this->dbh->query($query)
					or $this->db_error(_('Error creating category.'),
							$query);
			}
		}
		
		return $this->dbh->affected_rows > 0;
	}

	function search($cid, $keywords, $start, $end, $sort, $order)
	{
		$events_table = SQL_PREFIX . 'events';
		$occurrences_table = SQL_PREFIX . 'occurrences';
		$users_table = SQL_PREFIX . 'users';
		$cats_table = SQL_PREFIX . 'categories';

		$words = array();
		foreach($keywords as $keyword) {
			$words[] = "(`subject` LIKE '%$keyword%' "
				."OR `description` LIKE '%$keyword%')\n";
		}
		$where = implode(' AND ', $words);

                $query = "SELECT " . $this->get_occurrence_fields()
			.", `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table` \n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` on `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE ($where)\n"
			."AND `$events_table`.`cid` = '$cid'\n"
			."AND IF(`start_ts`, DATE(`start_ts`), `start_date`) >= STR_TO_DATE('$start', '%Y%m%d')\n"
			."AND IF(`end_ts`, DATE(`end_ts`), `end_date`) <= STR_TO_DATE('$end', '%Y%m%d')\n"
			."ORDER BY `$sort` $order";

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
			."VALUES ('$cid', '$uid', ".implode(", ", $values).")\n"
			."ON DUPLICATE KEY UPDATE ".implode(", ", $sets);

		if(!($sth = $this->dbh->query($query)))
			$this->db_error(_('Error updating user permissions.'),
					$query);
	}

	function get_login_token($uid, $series) {
		$query = "SELECT token FROM ".SQL_PREFIX."logins\n"
			."WHERE `uid`='$uid' AND `series`='$series'";

		$sth = $this->dbh->query($query)
			or $this->db_error(_("Error getting login token."),
					$query);

		$result = $sth->fetch_assoc();
		if(!$result)
			return false;
		return $result["token"];
	}

	function add_login_token($uid, $series, $token) {
		$query = "INSERT INTO ".SQL_PREFIX."logins\n"
			."(`uid`, `series`, `token`)\n"
			."VALUES ('$uid', '$series', '$token')";

		$this->dbh->query($query)
			or $this->db_error(_("Error adding login token."),
					$query);
	}

	function update_login_token($uid, $series, $token) {
		$query = "UPDATE ".SQL_PREFIX."logins\n"
			."SET `token`='$token', `atime`=NOW()\n"
			."WHERE `uid`='$uid' AND `series`='$series'";

		$this->dbh->query($query)
			or $this->db_error(_("Error updating login token."),
					$query);
	}

	function remove_login_tokens($uid) {
		$query = "DELETE FROM ".SQL_PREFIX."logins\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(_("Error removing login tokens."),
					$query);
	}

	function cleanup_login_tokens() {
		$query = "DELETE FROM ".SQL_PREFIX."logins\n"
			."WHERE `atime` < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

		$this->dbh->query($query)
			or $this->db_error(_("Error cleaning login tokens."),
					$query);
	}

	// called when there is an error involving the DB
	function db_error($str, $query = "")
	{
		$string = $str . "<pre>" . htmlspecialchars($this->dbh->error,
				ENT_COMPAT, "UTF-8") . "</pre>";
		if($query != "") {
			$string .= "<pre>" . _('SQL query') . ": "
				. htmlspecialchars($query, ENT_COMPAT, "UTF-8")
				. "</pre>";
		}
		throw new Exception($string);
	}

}

?>

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

namespace PhpCalendar;

class Database {
	private $dbh;
	private $calendars;
	private $config;
	private $event_columns;
	private $occurrence_columns;
	private $user_fields;
	private $prefix;

	/**
	 * Database constructor.
     */
	function __construct($config) {
		if(isset($config["sql_port"]))
			$port = $config["sql_port"];
		else
			$port = ini_get("mysqli.default_port");

		$this->prefix = $config["sql_prefix"];

		// Make the database connection.
		$this->dbh = @new \mysqli($config["sql_host"], $config["sql_user"], $config["sql_passwd"], $config["sql_database"], $port);

		if($this->dbh->connect_errno) {
			soft_error("Database connect failed ({$this->dbh->connect_errno}): {$this->dbh->connect_error}");
		}

		$this->dbh->set_charset("utf8");

		// TODO: Make these const
		$this->event_columns = "`{$this->prefix}categories`.`gid`, `{$this->prefix}events`.`subject`, "
			."`{$this->prefix}events`.`description`, `{$this->prefix}events`.`owner`, `{$this->prefix}events`.`eid`, "
			."`{$this->prefix}events`.`cid`, `{$this->prefix}events`.`readonly`, `{$this->prefix}events`.`catid`, "
			."UNIX_TIMESTAMP(`ctime`) AS `ctime`, UNIX_TIMESTAMP(`mtime`) AS `mtime`";

		$this->occurrence_columns = $this->event_columns . ", `time_type`, `oid`, "
			. "UNIX_TIMESTAMP(`start_ts`) AS `start_ts`, DATE_FORMAT(`start_date`, '%Y%m%d') AS `start_date`, "
			. "UNIX_TIMESTAMP(`end_ts`) AS `end_ts`, DATE_FORMAT(`end_date`, '%Y%m%d') AS `end_date`\n";

		$this->user_fields = "`{$this->prefix}users`.`uid`, `username`, `password`, `{$this->prefix}users`.`admin`, "
			."`password_editable`, `default_cid`, `timezone`, `language`, `disabled`";
	}

	function __destruct() {
		$this->dbh->close();
	}

	// returns all the events for a particular day
	// $from and $to are timestamps only significant to the date.
	// an event that happens later in the day of $to is included
	/**
	 * @param int $cid
	 * @param \DateTime $from
	 * @param \DateTime $to
	 * @return Occurrence[]
	 * @throws \Exception
	 */
	function get_occurrences_by_date_range($cid, \DateTime $from, \DateTime $to)
	{
		$from_str = "FROM_UNIXTIME('" . $from->getTimestamp() . "')";
		$to_str = "FROM_UNIXTIME('" . $to->getTimestamp() . "')";

		$events_table = $this->prefix . "events";
		$occurrences_table = $this->prefix . "occurrences";
		$users_table = $this->prefix . 'users';
		$cats_table = $this->prefix . 'categories';

		$query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` ON `$events_table`.`catid` = `$cats_table`.`catid`\n"
			."WHERE `$events_table`.`cid` = '$cid'\n"
			."	AND IF(`start_ts`, `start_ts` <= ?, `start_date` <= DATE(?))\n"
			."	AND IF(`end_ts`, `end_ts` >= ?, `end_date` >= DATE(?))\n"
			."	ORDER BY `start_ts`, `start_date`, `oid`";
		$stmt = $this->dbh->prepare($query);
		$stmt->bind_param('ssss', $to_str, $to_str, $from_str, $from_str);
		$stmt->execute()
			or $this->db_error(__('Error in get_occurrences_by_date_range'), $query);
		$arr = array();
		while($row = $stmt->fetch()) {
			$arr[] = new Occurrence($this, $row);
		}
		return $arr;
	}
		
	/* if category is visible to user id */
	/**
	 * @param User $user
	 * @param int $catId
	 * @return bool
	 * @throws \Exception
     */
	function is_cat_visible(User $user, $catId)
	{
		$users_table = $this->prefix . 'users';
		$user_groups_table = $this->prefix . 'user_groups';
		$cats_table = $this->prefix . 'categories';

		if ($user->is_admin())
			return true;

		$query = "SELECT * FROM `$users_table` u\n"
			."JOIN `$user_groups_table` ug USING (`uid`)\n"
			."JOIN `$cats_table` c ON c.`gid`=ug.`gid`\n"
			."WHERE c.`catid`=? AND u.`uid`=?";

		$stmt = $this->dbh->prepare($query);
		$stmt->bind_param('ii', $catId, $user->get_uid());
		$stmt->execute()
			or soft_error(__("Error executing SQL statement in is_cat_visible."));

		$results = $stmt->get_result();
		if(!$results)
			return false;

		return $results->num_rows > 0;
	}

	// returns all the events for a particular day
	function get_occurrences_by_date($cid, $year, $month, $day)
	{
		$from_stamp = mktime(0, 0, 0, $month, $day, $year);
		$to_stamp = mktime(23, 59, 59, $month, $day, $year);

		return $this->get_occurrences_by_date_range($cid, $from_stamp, $to_stamp);
        }

	// returns the event that corresponds to eid
	function get_event_by_eid($eid)
	{
		$events_table = $this->prefix . 'events';
		$users_table = $this->prefix . 'users';
		$cats_table = $this->prefix . 'categories';

		$query = "SELECT {$this->event_columns}, `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `eid` = ?\n";

		$stmt = $this->dbh->prepare($query);
		$stmt->bind_param('i', $eid);
		$stmt->execute()
			or $this->db_error(__('Error in get_event_by_eid'), $query);

		$result = $stmt->get_result();
		$arr = $result->fetch_assoc();
		if(!$arr)
			return false;
		return new Event($this, $arr);
	}

	// returns the event that corresponds to oid
	function get_event_by_oid($oid)
	{
		$events_table = $this->prefix . 'events';
		$occurrences_table = $this->prefix . 'occurrences';
		$users_table = $this->prefix . 'users';
		$cats_table = $this->prefix . 'categories';

		$query = "SELECT {$this->event_columns}, `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
			."LEFT JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `oid` = '$oid'\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_event_by_oid'),
					$query);

		return $sth->fetch_assoc();
	}

	// returns the category that corresponds to $catid
	function get_category($catid) {
		$cats_table = $this->prefix . 'categories';
		$groups_table = $this->prefix . 'groups';

		$query = "SELECT `$cats_table`.`name` AS `name`, `text_color`, "
			."`bg_color`, `$cats_table`.`cid` AS `cid`, "
			."`$cats_table`.`gid`, `catid`, "
			."`$groups_table`.`name` AS `group_name`\n"
			."FROM `$cats_table`\n"
			."LEFT JOIN `$groups_table` USING (`gid`)\n"
			."WHERE `catid` = $catid";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_category'), $query);
			
		$result = $sth->fetch_assoc()
			or soft_error(__("Category doesn't exist with catid")
					. ": $catid");
	
		return $result;
	}

	function get_group($gid) {
		$groups_table = $this->prefix . 'groups';

		$query = "SELECT `name`, `gid`, `cid`\n"
			."FROM `$groups_table`\n"
			."WHERE `gid` = $gid";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_group'), $query);
			
		$result = $sth->fetch_assoc()
			or soft_error(__("Group doesn't exist with gid")
					. ": $gid");
	
		return $result;
	}

	function get_groups($cid = false) {
		$groups_table = $this->prefix . 'groups';

		$query = "SELECT `gid`, `name`, `cid`\n"
			."FROM `$groups_table`\n";

		if($cid !== false)
			$query .= "WHERE `cid` = $cid";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_groups'), $query);
					
		$groups = array();
		while($row = $sth->fetch_assoc()) {
			$groups[] = $row;
		}					
		return $groups;
	}

	function get_user_groups($uid) {
		$groups_table = $this->prefix . 'groups';
		$user_groups_table = $this->prefix . 'user_groups';

		$query = "SELECT `gid`, `cid`, `name`\n"
			."FROM `$groups_table`\n"
			."INNER JOIN `$user_groups_table` USING (`gid`)\n"
			."WHERE `uid` = $uid";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_user_groups'),
					$query);
					
		$groups = array();
		while($row = $sth->fetch_assoc()) {
			$groups[] = $row;
		}					
		return $groups;
	}
	
	// returns the categories for calendar $cid
	function get_categories($cid = false) {
		$cats_table = $this->prefix . 'categories';
		$groups_table = $this->prefix . 'groups';

		if($cid)
			$where = "WHERE `$cats_table`.`cid` = '$cid'\n";
		else
			$where = "WHERE `$cats_table`.`cid` IS NULL\n";

		$query = "SELECT `$cats_table`.`name` AS `name`, `text_color`, "
			."`bg_color`, `$cats_table`.`cid` AS `cid`, "
			."`$cats_table`.`gid`, `catid`, "
			."`$groups_table`.`name` AS `group_name`\n"
			."FROM `$cats_table`\n"
			."LEFT JOIN `$groups_table` USING (`gid`)\n"
			.$where;

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_categories'),
					$query);

		$arr = array();
		while($result = $sth->fetch_assoc()) {
			$arr[] = $result;
		}

		return $arr;
	}

	// returns the categories for calendar $cid
	//   if there are no 
	function get_visible_categories($uid, $cid = false)
	{
		$cats_table = $this->prefix . 'categories';
		$user_groups_table = $this->prefix . 'user_groups';

		$where_cid = "`cid` IS NULL";
		if($cid)
			$where_cid = "($where_cid OR `cid` = '$cid')";

		$query = "SELECT `name`, `text_color`, `bg_color`, `cid`, "
			."`gid`, `catid`\n"
			."FROM `$cats_table`\n"
			."LEFT JOIN `$user_groups_table` USING (`gid`)\n"
			."WHERE (`uid` IS NULL OR `uid` = '$uid') AND $where_cid\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_visible_categories'),
					$query);

		$arr = array();
		while($result = $sth->fetch_assoc()) {
			$arr[] = $result;
		}

		return $arr;
	}

	function get_field($fid) {
		$fields_table = $this->prefix . 'fields';

		$query = "SELECT `$fields_table`.`name` AS `name`, `required`, "
			."`format`, `$fields_table`.`cid` AS `cid`, `fid`\n"
			."FROM `$fields_table`\n"
			."WHERE `fid` = $fid";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_field'), $query);
			
		$result = $sth->fetch_assoc()
			or soft_error(__("Field doesn't exist with 'fid'") . ": $fid");
	
		return $result;
	}

	// returns the event that corresponds to $oid
	function get_occurrence_by_oid($oid)
	{
		$events_table = $this->prefix . 'events';
		$occurrences_table = $this->prefix . 'occurrences';
		$users_table = $this->prefix . 'users';
		$cats_table = $this->prefix . 'categories';

                $query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `oid` = '$oid'\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_occurrence_by_oid'),
					$query);

		$result = $sth->fetch_assoc()
			or soft_error(__("Event doesn't exist with oid") . ": $oid");

		return new Occurrence($this, $result);
	}

	// returns the categories for calendar $cid
	function get_fields($cid = false) {
		$fields_table = $this->prefix . 'fields';

		if($cid)
			$where = "WHERE `$fields_table`.`cid` = '$cid'\n";
		else
			$where = "WHERE `$fields_table`.`cid` IS NULL\n";

		$query = "SELECT `name`, `required`, `format`, `cid`, `fid`\n"
			."FROM `$fields_table`\n"
			.$where;

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_fields'), $query);

		$arr = array();
		while($result = $sth->fetch_assoc()) {
			$arr[$result['fid']] = $result;
		}

		return $arr;
	}

	function get_event_fields($eid) {
		$event_fields_table = $this->prefix . 'event_fields';
		$query = "SELECT `fid`, `value`\n"
			."FROM `$event_fields_table`\n"
			."WHERE `eid`='$eid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error in get_event_fields'), $query);

		$arr = array();
		while($result = $sth->fetch_assoc()) {
			$arr[] = $result;
		}

		return $arr;
	}

        function get_occurrences_by_eid($eid)
	{
		$events_table = $this->prefix . "events";
		$occurrences_table = $this->prefix . "occurrences";
		$users_table = $this->prefix . 'users';
		$cats_table = $this->prefix . 'categories';

                $query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE `eid` = '$eid'\n"
			."	ORDER BY `start_ts`, `start_date`, `oid`";

		$result = $this->dbh->query($query)
			or $this->db_error(__('Error in get_occurrences_by_eid'),
					$query);

		$events = array();
		while($row = $result->fetch_assoc()) {
			$events[] = new Occurrence($this, $row);
		}
		return $events;
        }

	function delete_event($eid)
	{

		$query = 'DELETE FROM `'.$this->prefix ."events`\n"
			."WHERE `eid` = '$eid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error while removing an event.'),
					$query);

		$rv = $this->dbh->affected_rows > 0;

		$this->delete_occurrences($eid);

		return $rv;
	}

	function delete_occurrences($eid)
	{
		$query = 'DELETE FROM `'.$this->prefix ."occurrences`\n"
			."WHERE `eid` = '$eid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error while removing an event.'),
					$query);

		return $this->dbh->affected_rows;
	}

	function delete_occurrence($oid)
	{
		$query = 'DELETE FROM `'.$this->prefix ."occurrences`\n"
			."WHERE `oid` = '$oid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error while removing an occurrence.'),
					$query);

		return $this->dbh->affected_rows;
	}

	function delete_calendar($cid) {
		$events = $this->prefix . 'events';
		$occurrences = $this->prefix . 'occurrences';

		$query = 'DELETE FROM `'.$this->prefix ."calendars`\n"
			."WHERE cid='$cid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error while removing a calendar'), $query);

		$rv = $this->dbh->affected_rows > 0;

		$query = "DELETE FROM `$occurrences`, `$events`\n"
			."USING `$occurrences` INNER JOIN `$events`\n"
			."WHERE `$occurrences`.`eid`=`$events`.`eid` AND `$events`.`cid`='$cid'";
			
		$this->dbh->query($query)
			or $this->db_error(__('Error while removing events from calendar'), $query);

		return $rv;
	}

	function delete_category($catid)
	{

		$query = 'DELETE FROM `'.$this->prefix ."categories`\n"
			."WHERE `catid` = '$catid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error removing category.'), $query);

		return $this->dbh->affected_rows > 0;
	}

	function delete_group($gid)
	{

		$query = 'DELETE FROM `'.$this->prefix ."groups`\n"
			."WHERE `gid` = '$gid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error removing group.'), $query);

		return $this->dbh->affected_rows > 0;
	}

	function delete_field($fid)
	{

		$query = 'DELETE FROM `'.$this->prefix ."fields`\n"
			."WHERE `fid` = '$fid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error removing field.'), $query);

		return $this->dbh->affected_rows > 0;
	}

	function disable_user($id)
	{

		$query = 'UPDATE `'.$this->prefix ."users`\n"
			."SET `disabled`=1\n"
			."WHERE uid='$id'";

		$this->dbh->query($query)
			or $this->db_error(__('Error disabling a user.'),
					$query);
		return $this->dbh->affected_rows > 0;
	}

	function enable_user($id)
	{

		$query = 'UPDATE `'.$this->prefix ."users`\n"
			."SET `disabled`=0\n"
			."WHERE uid='$id'";

		$this->dbh->query($query)
			or $this->db_error(__('Error enabling a user.'),
					$query);
		return $this->dbh->affected_rows > 0;
	}

	public function get_permissions($cid, $uid)
	{
		static $perms = array();

		if (empty($perms[$cid]))
			$perms[$cid] = array();
		elseif (!empty($perms[$cid][$uid]))
			return $perms[$cid][$uid];

		$query = "SELECT * from " . $this->prefix .
			"permissions WHERE `cid`='$cid' AND `uid`='$uid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Could not read permissions.'),
					$query);

		$perms[$cid][$uid] = $sth->fetch_assoc();
		return $perms[$cid][$uid];
	}

	/**
	 * @return \PhpCalendar\Calendar[]
	 * @throws \Exception
	 */
	function get_calendars() {
		if(!empty($this->calendars))
			return $this->calendars;

		$query = "SELECT *\n"
			."FROM `" . $this->prefix .  "calendars`\n"
			."ORDER BY `cid`";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Could not get calendars.'),
					$query);

		$this->calendars = array();
		while($result = $sth->fetch_assoc()) {
			$cid = $result["cid"];
			//assert(empty($this->calendars[$cid]));
			$this->calendars[$cid] = Calendar::createFromMap($this, $result);
		}

		return $this->calendars;
	}

	/**
	 * @param $cid int
	 * @return null|\PhpCalendar\Calendar
	 */
	function get_calendar($cid)
	{
		$calendars = $this->get_calendars();
		if(empty($calendars[$cid])) {
			return NULL;
		}

		return $calendars[$cid];
	}

	function get_config($name) {
		if (!isset($this->config)) {
			$query = "SELECT `name`, `value` FROM `" . $this->prefix
				. "config`";
			$sth = $this->dbh->query($query)
				or $this->db_error(__('Could not get config.'),
						$query);
			$this->config = array();
			while($result = $sth->fetch_assoc()) {
				$this->config[$result['name']] =
					$result['value'];
			}
		}
		if (isset($this->config[$name]))
			return $this->config[$name];
		// otherwise
		return false;
	}

	function set_config($name, $value) {
		$query = "REPLACE INTO `".$this->prefix."config`\n"
			."(`name`, `value`) VALUES\n"
			."('$name', '$value')";

		$this->dbh->query($query)
			or $this->db_error(__('Error setting option.'),
					$query);
	}

	function set_user_default_cid($uid, $cid) {
		$query = "UPDATE `".$this->prefix."users`\n"
			."SET `default_cid`='$cid'\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(__('Error setting default cid.'),
					$query);
	}

	function get_users()
	{
		$query = "SELECT * FROM `" . $this->prefix .  "users`";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Could not get user.'), $query);

		$users = array();
		while($user = $sth->fetch_assoc()) {
			$users[] = User::createFromMap($this, $user);
		}
		return $users;
	}

	function get_users_with_permissions($cid)
	{
		$permissions_table = $this->prefix . "permissions";

		$query = "SELECT *, `permissions`.`admin` AS `calendar_admin`\n"
			."FROM `" . $this->prefix . "users`\n"
			."LEFT JOIN (SELECT * FROM `$permissions_table`\n"
			."	WHERE `cid`='$cid') AS `permissions`\n"
			."USING (`uid`)\n";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Could not get user.'), $query);

		$users = array();
		while($user = $sth->fetch_assoc()) {
			$users[] = $user;
		}
		return $users;
	}

	function get_user_by_name($username)
	{
		$query = "SELECT {$this->user_fields}\n"
			."FROM ".$this->prefix."users\n"
			."WHERE username='$username'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__("Error getting user."), $query);

		$result = $sth->fetch_assoc();
		if($result)
			return User::createFromMap($this, $result);
		else
			return false;
	}

	function get_user($uid)
	{
		$query = "SELECT {$this->user_fields}\n"
			."FROM ".$this->prefix."users\n"
			."WHERE `uid`='$uid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__("Error getting user."), $query);

		$result = $sth->fetch_assoc();
		if($result)
			return User::createFromMap($this, $result);
		else
			return false;
	}

	function create_user($username, $password, $make_admin) {
		$admin = $make_admin ? 1 : 0;
		$query = "INSERT into `".$this->prefix."users`\n"
			."(`username`, `password`, `admin`) VALUES\n"
			."('$username', '$password', $admin)";

		$this->dbh->query($query)
			or $this->db_error(__('Error creating user.'), $query);

		return $this->dbh->insert_id;
	}

	function create_calendar()
	{
		$query = "INSERT INTO ".$this->prefix."calendars\n"
			."(`cid`) VALUE (DEFAULT)";

		$this->dbh->query($query)
			or $this->db_error(__('Error reading options'), $query);

		return $this->dbh->insert_id;
	}

	function set_calendar_config($cid, $name, $value)
	{
		$query = "UPDATE `".$this->prefix."calendars`\n"
			."SET `$name`='$value'\n"
			."WHERE `cid`='$cid'";

		$this->dbh->query($query)
			or $this->db_error(__('Error setting calendar option.'),
					$query);
	}

	function set_password($uid, $password)
	{
		$query = "UPDATE `" . $this->prefix . "users`\n"
			."SET `password`='$password'\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(__('Error updating password.'),
					$query);
	}

	function set_timezone($uid, $timezone)
	{
		$query = "UPDATE `" . $this->prefix . "users`\n"
			."SET `timezone`='$timezone'\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(__('Error updating timezone.'),
					$query);
	}

	function set_language($uid, $language)
	{
		$query = "UPDATE `" . $this->prefix . "users`\n"
			."SET `language`='$language'\n"
			."WHERE `uid`='$uid'";

		$this->dbh->query($query)
			or $this->db_error(__('Error updating language.'),
					$query);
	}

	function user_add_group($uid, $gid) {
		$user_groups_table = $this->prefix . 'user_groups';

		$query = "INSERT INTO `$user_groups_table`\n"
			."(`gid`, `uid`) VALUES\n"
			."('$gid', '$uid')";

		$this->dbh->query($query)
			or $this->db_error(__('Error adding group to user.'),
					$query);
	}

	function user_remove_group($uid, $gid) {
		$user_groups_table = $this->prefix . 'user_groups';

		$query = "DELETE FROM `$user_groups_table`\n"
			."WHERE `uid` = '$uid' AND `gid` = '$gid'";

		$this->dbh->query($query)
			or $this->db_error(__('Error removing group from user.'), $query);
	}

	function create_event($cid, $uid, $subject, $description, $readonly,
			$catid = false)
	{
		$fmt_readonly = asbool($readonly);

		if(!$catid)
			$catid = 'NULL';
		else
			$catid = "'$catid'";

		$query = "INSERT INTO `" . $this->prefix . "events`\n"
			."(`cid`, `owner`, `subject`, `description`, "
			."`readonly`, `catid`)\n"
			."VALUES ('$cid', '$uid', '$subject', '$description', "
			."$fmt_readonly, $catid)";

		$this->dbh->query($query)
			or $this->db_error(__('Error creating event.'), $query);

		$eid = $this->dbh->insert_id;

		if($eid <= 0)
			soft_error("Bad eid creating event.");

		return $eid;
	}

	function create_occurrence($eid, $time_type, $start_ts, $end_ts)
	{

		$query = "INSERT INTO `" . $this->prefix . "occurrences`\n"
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
			or $this->db_error(__('Error creating occurrence.'),
					$query);

		return $this->dbh->insert_id;
	}

	function add_event_field($eid, $fid, $value)
	{
		$query = "INSERT INTO `" . $this->prefix . "event_fields`\n"
			."SET `eid` = '$eid', `fid` = '$fid', `value` = '$value'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error adding field to event.'), $query);
	}

	function modify_occurrence($oid, $time_type, $start_ts, $end_ts)
	{

		$query = "UPDATE `" . $this->prefix . "occurrences`\n"
			."SET `time_type` = '$time_type'";

		if($time_type == 0) {
			$query .= ", `start_ts` = FROM_UNIXTIME('$start_ts')"
				. ", `end_ts` = FROM_UNIXTIME('$end_ts')"
				. ", `start_date` = NULL"
				. ", `end_date` = NULL";
		} else {
			$start_date = date("Y-m-d", $start_ts);
			$end_date = date("Y-m-d", $end_ts);
			$query .= ", `start_date` = '$start_date'"
				. ", `end_date` = '$end_date'"
				. ", `start_ts` = NULL"
				. ", `end_ts` = NULL";
		}

		$query .= "\nWHERE `oid`='$oid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error modifying occurrence.'),
					$query);

		return $this->dbh->affected_rows > 0;
	}

	function modify_event($eid, $subject, $description, $readonly,
			$catid = false)
	{
		$fmt_readonly = asbool($readonly);

		$query = "UPDATE `" . $this->prefix . "events`\n"
			."SET\n"
			."`subject`='$subject',\n"
			."`description`='$description',\n"
			."`readonly`=$fmt_readonly,\n"
			."`mtime`=NOW(),\n"
			.($catid !== false ? "`catid`='$catid'\n"
				: "`catid`=NULL\n")
			."WHERE eid='$eid'";

		$this->dbh->query($query)
			or $this->db_error(__('Error modifying event.'), $query);

		return $this->dbh->affected_rows > 0;
	}

	function create_category($cid, $name, $text_color, $bg_color,
			$gid = false) {
		$gid_key = $gid ? ', `gid`' : '';
		$gid_value = $gid ? ", '$gid'" : '';
		$query = "INSERT INTO `" . $this->prefix . "categories`\n"
			."(`cid`, `name`, `text_color`, `bg_color`$gid_key)\n"
			."VALUES ('$cid', '$name', '$text_color', '$bg_color'$gid_value)";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error creating category.'),
					$query);
		
		return $this->dbh->insert_id;
	}

	function create_group($cid, $name)
	{
		$query = "INSERT INTO `" . $this->prefix . "groups`\n"
			."(`cid`, `name`)\n"
			."VALUES ('$cid', '$name')";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error creating group.'), $query);
		
		return $this->dbh->insert_id;
	}

	function create_field($cid, $name, $required, $format) {
		if($format === false)
			$format_val = 'NULL';
		else
			$format_val = "'$format'";

		$query = "INSERT INTO `" . $this->prefix . "fields`\n"
			."(`cid`, `name`, `required`, `format`)\n"
			."VALUES ('$cid', '$name', '$required', $format_val)";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error creating field.'), $query);
		
		return $this->dbh->insert_id;
	}

	function modify_category($catid, $name, $text_color, $bg_color, $gid)
	{
		$query = "UPDATE " . $this->prefix . "categories\n"
			."SET\n"
			."`name`='$name',\n"
			."`text_color`='$text_color',\n"
			."`bg_color`='$bg_color',\n"
			."`gid`='$gid'\n"
			."WHERE `catid`='$catid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error modifying category.'),
					$query);

		return $this->dbh->affected_rows > 0;
	}

	function modify_group($gid, $name)
	{
		$query = "UPDATE " . $this->prefix . "groups\n"
			."SET `name`='$name'\n"
			."WHERE `gid`='$gid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error modifying group.'),
					$query);

		return $this->dbh->affected_rows > 0;
	}

	function modify_field($fid, $name, $required, $format)
	{
		if($format === false)
			$format_val = 'NULL';
		else
			$format_val = "'$format'";

		$query = "UPDATE " . $this->prefix . "fields\n"
			."SET\n"
			."`name`='$name',\n"
			."`required`='$required',\n"
			."`format`=$format_val\n"
			."WHERE `fid`='$fid'";

		$sth = $this->dbh->query($query)
			or $this->db_error(__('Error modifying field.'), $query);

		return $this->dbh->affected_rows > 0;
	}

	function search($cid, $keywords, $start, $end, $sort, $order) {
		$events_table = $this->prefix . 'events';
		$occurrences_table = $this->prefix . 'occurrences';
		$users_table = $this->prefix . 'users';
		$cats_table = $this->prefix . 'categories';
		$start_str = "FROM_UNIXTIME('$start')";
		$end_str = "FROM_UNIXTIME('$end')";

		$words = array();
		foreach($keywords as $keyword) {
			$words[] = "(`subject` LIKE '%$keyword%' "
				."OR `description` LIKE '%$keyword%')\n";
		}
		$where = implode(' AND ', $words);

		if($start)
			$where .= "AND IF(`start_ts`, `start_ts` <= $end_str, `start_date` <= DATE($start_str))\n";
		if($end)
			$where .= "AND IF(`end_ts`, `end_ts` >= $start_str, `end_date` >= DATE($end_str))\n";

                $query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
			."FROM `$events_table`\n"
                        ."INNER JOIN `$occurrences_table` USING (`eid`)\n"
			."LEFT JOIN `$users_table` ON `uid` = `owner`\n"
			."LEFT JOIN `$cats_table` USING (`catid`)\n"
			."WHERE ($where)\n"
			."AND `$events_table`.`cid` = '$cid'\n"
			."ORDER BY `$sort` $order";

		if(!($result = $this->dbh->query($query)))
			$this->db_error(__('Error during searching'), $query);

		$events = array();
		while($row = $result->fetch_assoc()) {
			$events[] = new Occurrence($row);
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

		$query = "INSERT INTO ".$this->prefix."permissions\n"
			."(`cid`, `uid`, ".implode(", ", $names).")\n"
			."VALUES ('$cid', '$uid', ".implode(", ", $values).")\n"
			."ON DUPLICATE KEY UPDATE ".implode(", ", $sets);

		if(!($sth = $this->dbh->query($query)))
			$this->db_error(__('Error updating user permissions.'),
					$query);
	}

	// called when there is an error involving the DB
	function db_error($str, $query = "")
	{
		$string = $str . "<pre>" . htmlspecialchars($this->dbh->error,
				ENT_COMPAT, "UTF-8") . "</pre>";
		if($query != "") {
			$string .= "<pre>" . __('SQL query') . ": "
				. htmlspecialchars($query, ENT_COMPAT, "UTF-8")
				. "</pre>";
		}
		throw new \Exception($string);
	}

}

?>

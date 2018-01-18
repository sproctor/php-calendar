<?php
/*
 * Copyright Sean Proctor
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

class Database
{
    /**
     * @var \PDO
     */
    private $dbh;
    /**
     * @var Calendar[]
     */
    private $calendars;
    /**
     * @var string[]
     */
    private $config;
    private $event_columns;
    private $occurrence_columns;
    private $user_fields;
    /**
     * @var string
     */
    private $prefix;

    /**
     * Database constructor.
     *
     * @param string[] $config
     */
    public function __construct($config)
    {
        $dsn = "mysql:dbname={$config["sql_database"]};host={$config["sql_host"]};charset=utf8";
        if (isset($config["sql_port"])) {
            $dsn .= ";port=" . $config["sql_port"];
        }

        $this->prefix = $config["sql_prefix"];

        // Make the database connection.
        $this->dbh = new \PDO($dsn, $config["sql_user"], $config["sql_passwd"]);
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // TODO: Make these const
        $this->event_columns = "`{$this->prefix}categories`.`gid`, `{$this->prefix}events`.`subject`, "
            . "`{$this->prefix}events`.`description`, `{$this->prefix}events`.`owner`, `{$this->prefix}events`.`eid`, "
            . "`{$this->prefix}events`.`cid`, `{$this->prefix}events`.`catid`, "
            . "UNIX_TIMESTAMP(`ctime`) AS `ctime`, UNIX_TIMESTAMP(`mtime`) AS `mtime`";

        $this->occurrence_columns = $this->event_columns . ", `time_type`, `oid`, `start`, `end`";

        $this->user_fields = "`{$this->prefix}users`.`uid`, `username`, `password`, `{$this->prefix}users`.`admin`, "
            . "`password_editable`, `default_cid`, `timezone`, `language`, `disabled`";
    }

    /**
     * Returns all the events for a particular date range
     * $from and $to are only significant to the date.
     * an event that happens later in the day of $to is included
     * @param int                $cid
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return Occurrence[]
     */
    public function getOccurrencesByDateRange($cid, \DateTimeInterface $from, \DateTimeInterface $to)
    {
        $events_table = $this->prefix . "events";
        $occurrences_table = $this->prefix . "occurrences";
        $users_table = $this->prefix . 'users';
        $cats_table = $this->prefix . 'categories';

        $from_datetime = datetime_to_sql_date($from);
        $from_date = $from->format('Y-m-d');

        $to_datetime = datetime_to_sql_date($to);
        $to_date = $from->format('Y-m-d');

        $query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
            . "FROM `$events_table`\n"
            . "INNER JOIN `$occurrences_table` USING (`eid`)\n"
            . "LEFT JOIN `$users_table` ON `uid` = `owner`\n"
            . "LEFT JOIN `$cats_table` ON `$events_table`.`catid` = `$cats_table`.`catid`\n"
            . "WHERE `$events_table`.`cid`=$cid\n"
            . "	AND IF(`time_type`=0, `start` < '$to_datetime', DATE(`start`) < DATE('$to_date'))\n"
            . "	AND IF(`time_type`=0, `end` >= '$from_datetime', DATE(`end`) >= DATE('$from_date'))\n"
            . "	ORDER BY `start`, `oid`";
        //echo "<pre>$query</pre>";
        $sth = $this->dbh->prepare($query);
        $sth->execute();
        $arr = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $arr[] = new Occurrence($this, $row);
        }
        return $arr;
    }

    /**
     * Returns if category is visible to user id
     *
     * @param User $user
     * @param int  $catId
     * @return bool
     */
    public function isCategoryVisible(User $user, $catId)
    {
        $users_table = $this->prefix . 'users';
        $user_groups_table = $this->prefix . 'user_groups';
        $cats_table = $this->prefix . 'categories';

        if ($user->is_admin()) {
            return true;
        }

        $query = "SELECT * FROM `$users_table` u\n"
            . "JOIN `$user_groups_table` ug USING (`uid`)\n"
            . "JOIN `$cats_table` c ON c.`gid`=ug.`gid`\n"
            . "WHERE c.`catid`=:catid AND u.`uid`={$user->get_uid()}";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':catid', $catId, \PDO::PARAM_INT);
        $sth->execute();

        $results = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$results) {
            return false;
        }

        return $results->num_rows > 0;
    }

    /**
     * Returns all the events for a particular day
     *
     * @param int $cid
     * @param int $year
     * @param int $month
     * @param int $day
     * @return Occurrence[]
     */
    public function getOccurrencesByDate($cid, $year, $month, $day)
    {
        $from_stamp = mktime(0, 0, 0, $month, $day, $year);
        $to_stamp = mktime(23, 59, 59, $month, $day, $year);

        return $this->getOccurrencesByDateRange(
            $cid,
            new \DateTime("$year-$month-$day 00:00:00"),
            (new \DateTime("$year-$month-$day 00:00:00"))->add(new \DateInterval("P1D"))
        );
    }

    /**
     * Returns the event that corresponds to eid
     *
     * @param int $eid
     * @return null|Event
     * @throws \Exception
     */
    public function getEvent($eid)
    {
        $events_table = $this->prefix . 'events';
        $users_table = $this->prefix . 'users';
        $cats_table = $this->prefix . 'categories';

        $query = "SELECT {$this->event_columns}, `username`, `name`, `bg_color`, `text_color`\n"
            . "FROM `$events_table`\n"
            . "LEFT JOIN `$users_table` ON `uid`=`owner`\n"
            . "LEFT JOIN `$cats_table` USING (`catid`)\n"
            . "WHERE `eid`=:eid\n";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        }
        return new Event($this, $result);
    }

    /**
     * Returns the category that corresponds to $catid
     *
     * @param int $catid
     * @return string[]
     * @throws \Exception
     */
    public function getCategory($catid)
    {
        $cats_table = $this->prefix . 'categories';
        $groups_table = $this->prefix . 'groups';

        $query = "SELECT `$cats_table`.`name` AS `name`, `text_color`, "
            . "`bg_color`, `$cats_table`.`cid` AS `cid`, "
            . "`$cats_table`.`gid`, `catid`, "
            . "`$groups_table`.`name` AS `group_name`\n"
            . "FROM `$cats_table`\n"
            . "LEFT JOIN `$groups_table` USING (`gid`)\n"
            . "WHERE `catid`=:catid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':catid', $catid);
        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            throw new \UnexpectedValueException(__("nonexistent-value-error", ['%name%' => __('category')]));
        }
        return $result;
    }

    /**
     * @param int $gid
     * @return mixed
     * @throws \Exception
     */
    public function getGroup($gid)
    {
        $groups_table = $this->prefix . 'groups';

        $query = "SELECT `name`, `gid`, `cid`\n"
            . "FROM `$groups_table`\n"
            . "WHERE `gid`=:gid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':gid', $gid, \PDO::PARAM_INT);
        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            throw new \UnexpectedValueException(__("nonexistent-value-error", ['%name%' => __('group')]));
        }
        return $result;
    }

    /**
     * @return array[]
     */
    public function getGroups()
    {
        $groups_table = $this->prefix . 'groups';

        $query = "SELECT `gid`, `name`, `cid`\n"
            . "FROM `$groups_table`";

        $sth = $this->dbh->prepare($query);
        $sth->execute();

        $groups = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $groups[] = $row;
        }
        return $groups;
    }

    /**
     * @param int $cid
     * @return array[]
     */
    public function getGroupsForCalendar($cid)
    {
        $groups_table = $this->prefix . 'groups';

        $query = "SELECT `gid`, `name`, `cid`\n"
            . "FROM `$groups_table`\n"
            . "WHERE `cid`=:cid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        $groups = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $groups[] = $row;
        }
        return $groups;
    }

    /**
     * @param int $uid
     * @return string[][]
     */
    public function getGroupsForUser($uid)
    {
        $groups_table = $this->prefix . 'groups';
        $user_groups_table = $this->prefix . 'user_groups';

        $query = "SELECT `gid`, `cid`, `name`\n"
            . "FROM `$groups_table`\n"
            . "INNER JOIN `$user_groups_table` USING (`gid`)\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();

        $groups = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $groups[] = $row;
        }
        return $groups;
    }

    // returns the categories for calendar $cid

    /**
     * @param int $cid
     * @return string[][]
     */
    public function getCategoriesForCalendar($cid)
    {
        $cats_table = $this->prefix . 'categories';
        $groups_table = $this->prefix . 'groups';

        $query = "SELECT `$cats_table`.`name` AS `name`, `text_color`, "
            . "`bg_color`, `$cats_table`.`cid` AS `cid`, "
            . "`$cats_table`.`gid`, `catid`, "
            . "`$groups_table`.`name` AS `group_name`\n"
            . "FROM `$cats_table`\n"
            . "LEFT JOIN `$groups_table` USING (`gid`)\n"
            . "WHERE `$cats_table`.`cid`=:cid\n";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        $arr = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $arr[] = $result;
        }

        return $arr;
    }

    /**
     * @return array[]
     */
    public function getGlobalCategories()
    {
        $cats_table = $this->prefix . 'categories';
        $groups_table = $this->prefix . 'groups';

        $query = "SELECT `$cats_table`.`name` AS `name`, `text_color`, "
            . "`bg_color`, `$cats_table`.`cid` AS `cid`, "
            . "`$cats_table`.`gid`, `catid`, "
            . "`$groups_table`.`name` AS `group_name`\n"
            . "FROM `$cats_table`\n"
            . "LEFT JOIN `$groups_table` USING (`gid`)\n"
            . "WHERE `$cats_table`.`cid` IS NULL\n";

        $sth = $this->dbh->prepare($query);
        $sth->execute();

        $arr = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $arr[] = $result;
        }

        return $arr;
    }

    // returns the categories for calendar $cid
    //   if there are no
    /**
     * @param int $uid
     * @param int $cid
     * @return array[]
     */
    public function getVisibleCategories($uid, $cid)
    {
        $cats_table = $this->prefix . 'categories';
        $user_groups_table = $this->prefix . 'user_groups';

        $query = "SELECT `name`, `text_color`, `bg_color`, `cid`, "
            . "`gid`, `catid`\n"
            . "FROM `$cats_table`\n"
            . "LEFT JOIN `$user_groups_table` USING (`gid`)\n"
            . "WHERE (`uid` IS NULL OR `uid`=:uid) AND (`cid` IS NULL OR `cid`=:cid)\n";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        $arr = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $arr[] = $result;
        }

        return $arr;
    }

    /**
     * @param int $fid
     * @return string[]
     */
    public function getField($fid)
    {
        $fields_table = $this->prefix . 'fields';

        $query = "SELECT `$fields_table`.`name` AS `name`, `required`, "
            . "`format`, `$fields_table`.`cid` AS `cid`, `fid`\n"
            . "FROM `$fields_table`\n"
            . "WHERE `fid` = :fid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':fid', $fid, \PDO::PARAM_INT);
        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            throw new \UnexpectedValueException(__('nonexistent-value-error', ['%name%' => __('field')]));
        }
    }

    /**
     * Returns the event that corresponds to $oid
     *
     * @param int $oid
     * @return Occurrence|null
     * @throws \Exception
     */
    public function getOccurrence($oid)
    {
        $events_table = $this->prefix . 'events';
        $occurrences_table = $this->prefix . 'occurrences';
        $users_table = $this->prefix . 'users';
        $cats_table = $this->prefix . 'categories';

        $query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
            . "FROM `$events_table`\n"
            . "INNER JOIN `$occurrences_table` USING (`eid`)\n"
            . "LEFT JOIN `$users_table` ON `uid` = `owner`\n"
            . "LEFT JOIN `$cats_table` USING (`catid`)\n"
            . "WHERE `oid` = :oid\n";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':oid', $oid, \PDO::PARAM_INT);
        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }

        return new Occurrence($this, $result);
    }

    // returns the categories for calendar $cid

    /**
     * @param int $cid
     * @return array[]
     */
    public function getFields($cid)
    {
        $fields_table = $this->prefix . 'fields';

        $query = "SELECT `name`, `required`, `format`, `cid`, `fid`\n"
            . "FROM `$fields_table`\n"
            . "WHERE `$fields_table`.`cid` IS NULL OR `$fields_table`.`cid` = :cid\n";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        $arr = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $arr[$result['fid']] = $result;
        }

        return $arr;
    }

    /**
     * @param int $eid
     * @return string[][]
     */
    public function getEventFields($eid)
    {
        $event_fields_table = $this->prefix . 'event_fields';
        $query = "SELECT `fid`, `value`\n"
            . "FROM `$event_fields_table`\n"
            . "WHERE `eid`=:eid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        $sth->execute();

        $arr = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $arr[] = $result;
        }

        return $arr;
    }

    /**
     * @param int $eid
     * @return Occurrence[]
     */
    public function getOccurrences($eid)
    {
        $events_table = $this->prefix . "events";
        $occurrences_table = $this->prefix . "occurrences";
        $users_table = $this->prefix . 'users';
        $cats_table = $this->prefix . 'categories';

        $query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
            . "FROM `$events_table`\n"
            . "INNER JOIN `$occurrences_table` USING (`eid`)\n"
            . "LEFT JOIN `$users_table` ON `uid` = `owner`\n"
            . "LEFT JOIN `$cats_table` USING (`catid`)\n"
            . "WHERE `eid` = :eid\n"
            . "	ORDER BY `start`, `oid`";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        $sth->execute();

        $occurrences = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $occurrences[] = new Occurrence($this, $row);
        }
        return $occurrences;
    }

    /**
     * @param int $eid
     */
    public function deleteEvent($eid)
    {
        $this->deleteOccurrences($eid);

        $query = 'DELETE FROM `' . $this->prefix . "events`\n"
            . "WHERE `eid` = :eid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $eid
     * @return bool
     */
    public function deleteOccurrences($eid)
    {
        $query = 'DELETE FROM `' . $this->prefix . "occurrences`\n"
            . "WHERE `eid` = :eid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->rowCount() > 0;
    }

    /**
     * @param int $oid
     */
    public function deleteOccurrence($oid)
    {
        $query = 'DELETE FROM `' . $this->prefix . "occurrences`\n"
            . "WHERE `oid` = :oid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':oid', $oid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $cid
     */
    public function deleteCalendar($cid)
    {
        $events = $this->prefix . 'events';
        $occurrences = $this->prefix . 'occurrences';

        // Delete events and occurrences
        $query = "DELETE FROM `$occurrences`, `$events`\n"
            . "USING `$occurrences` INNER JOIN `$events`\n"
            . "WHERE `$occurrences`.`eid`=`$events`.`eid` AND `$events`.`cid`=:cid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        // Delete calendar config
        $query = 'DELETE FROM `' . $this->prefix . "calendars`\n"
            . "WHERE `cid`=:cid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $catid
     */
    public function deleteCategory($catid)
    {

        $query = 'DELETE FROM `' . $this->prefix . "categories`\n"
            . "WHERE `catid` = :catid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':catid', $catid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $gid
     */
    public function deleteGroup($gid)
    {

        $query = 'DELETE FROM `' . $this->prefix . "groups`\n"
            . "WHERE `gid` = :gid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':gid', $gid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $fid
     */
    public function deleteField($fid)
    {

        $query = 'DELETE FROM `' . $this->prefix . "fields`\n"
            . "WHERE `fid` = :fid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':fid', $fid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() > 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $uid
     */
    public function disableUser($uid)
    {

        $query = 'UPDATE `' . $this->prefix . "users`\n"
            . "SET `disabled`=1\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $uid
     */
    public function enableUser($uid)
    {

        $query = 'UPDATE `' . $this->prefix . "users`\n"
            . "SET `disabled`=0\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int $cid
     * @param int $uid
     * @return string[]
     * @throws \Exception
     */
    public function getPermissions($cid, $uid)
    {
        static $perms = array();

        if (empty($perms[$cid])) {
            $perms[$cid] = array();
        }

        if (!empty($perms[$cid][$uid])) {
            $query = "SELECT * FROM " . $this->prefix . "permissions WHERE `cid`=:cid AND `uid`=:uid";

            $sth = $this->dbh->prepare($query);
            $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
            $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
            $sth->execute();

            $perms[$cid][$uid] = $sth->fetch(\PDO::FETCH_ASSOC);
        }

        return $perms[$cid][$uid];
    }

    /**
     * @return Calendar[]
     * @throws \Exception
     */
    public function getCalendars()
    {
        if (!empty($this->calendars)) {
            return $this->calendars;
        }

        $query = "SELECT *\n"
            . "FROM `" . $this->prefix . "calendars`\n"
            . "ORDER BY `cid`";

        $sth = $this->dbh->query($query);

        $this->calendars = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $cid = $result["cid"];
            //assert(empty($this->calendars[$cid]));
            $this->calendars[$cid] = Calendar::createFromMap($this, $result);
        }

        return $this->calendars;
    }

    /**
     * @param int $cid
     * @return Calendar
     */
    public function getCalendar($cid)
    {
        $calendar = $this->getCalendars()[$cid];
        if ($calendar == null) {
            throw new InvalidInputException(__('invalid-calendar-id-error'));
        }
        return $calendar;
    }

    /**
     * @param string $name
     * @return null|string
     */
    public function getConfig($name)
    {
        if (!isset($this->config)) {
            $query = "SELECT `name`, `value` FROM `" . $this->prefix . "config`";
            $sth = $this->dbh->query($query);

            $this->config = array();
            while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
                $this->config[$result['name']] = $result['value'];
            }
        }
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        // otherwise
        return null;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setConfig($name, $value)
    {
        $query = "REPLACE INTO `" . $this->prefix . "config`\n"
            . "(`name`, `value`) VALUES\n"
            . "(:name, :value)";

        $sth = $this->dbh->prepare($query);
        $sth->execute([':name' => $name, ':value' => $value]);
    }

    /**
     * @param int $uid
     * @param int $cid
     */
    public function setUserDefaultCid($uid, $cid)
    {
        $query = "UPDATE `" . $this->prefix . "users`\n"
            . "SET `default_cid`=:cid\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * @return User[]
     */
    public function getUsers()
    {
        $query = "SELECT * FROM `" . $this->prefix . "users`";

        $sth = $this->dbh->query($query);

        $users = array();
        while ($user = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $users[] = User::createFromMap($this, $user);
        }
        return $users;
    }

    /**
     * @param int $cid
     * @return array[]
     */
    public function getUsersPermissions($cid)
    {
        $permissions_table = $this->prefix . "permissions";

        $query = "SELECT * FROM `$permissions_table`\n"
            . "	WHERE `cid`=:cid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        $users = array();
        while ($user = $sth->fetch(\PDO::FETCH_ASSOC)) {
            foreach ($user as $key => $val) {
                if ($key == 'uid') {
                    $user[$key] = $val;
                } else {
                    $user[$key] = (bool) $val;
                }
            }
            $users[] = $user;
        }
        return $users;
    }

    /**
     * @param string $username
     * @return null|User
     */
    public function getUserByName($username)
    {
        $query = "SELECT {$this->user_fields}\n"
            . "FROM " . $this->prefix . "users\n"
            . "WHERE username=:username";

        $sth = $this->dbh->prepare($query);
        $sth->execute([':username' => $username]);

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            return User::createFromMap($this, $result);
        } else {
            return null;
        }
    }

    /**
     * @param int $uid
     * @return null|User
     */
    public function getUser($uid)
    {
        $query = "SELECT {$this->user_fields}\n"
            . "FROM " . $this->prefix . "users\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            return User::createFromMap($this, $result);
        } else {
            return null;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool   $make_admin
     * @return string
     */
    public function createUser($username, $password, $make_admin)
    {
        $admin = $make_admin ? 1 : 0;
        $query = "INSERT into `" . $this->prefix . "users`\n"
            . "(`username`, `password`, `admin`) VALUES\n"
            . "(:username, :password, $admin)";

        $sth = $this->dbh->prepare($query);
        $sth->execute([':username' => $username, ':password' => password_hash($password, PASSWORD_DEFAULT)]);

        return $this->dbh->lastInsertId();
    }

    /**
     * @return string
     */
    public function createCalendar()
    {
        $query = "INSERT INTO " . $this->prefix . "calendars\n"
            . "(`cid`) VALUE (DEFAULT)";

        $this->dbh->query($query);

        return $this->dbh->lastInsertId();
    }

    /**
     * @param int    $cid
     * @param string $name
     * @param string $value
     */
    public function setCalendarConfig($cid, $name, $value)
    {
        if (empty($value)) {
            $value = '0';
        }
        
        $query = "UPDATE `" . $this->prefix . "calendars`\n"
            . "SET `$name`=:value\n"
            . "WHERE `cid`=:cid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        //$sth->bindValue(':name', $name);
        $sth->bindValue(':value', $value);
        $sth->execute();
    }

    /**
     * @param int    $uid
     * @param string $password
     */
    public function setPassword($uid, $password)
    {
        $query = "UPDATE `" . $this->prefix . "users`\n"
            . "SET `password`=:password\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->bindValue(':password', password_hash($password, PASSWORD_DEFAULT));
        $sth->execute();
    }

    /**
     * @param int    $uid
     * @param string $timezone
     */
    public function setTimezone($uid, $timezone)
    {
        $query = "UPDATE `" . $this->prefix . "users`\n"
            . "SET `timezone`=:timezone\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->bindValue(':timezone', $timezone);
        $sth->execute();
    }

    /**
     * @param int    $uid
     * @param string $language
     */
    public function setLanguage($uid, $language)
    {
        $query = "UPDATE `" . $this->prefix . "users`\n"
            . "SET `language`=:language\n"
            . "WHERE `uid`=:uid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->bindValue(':language', $language);
        $sth->execute();
    }

    /**
     * @param int $uid
     * @param int $gid
     */
    public function userAddGroup($uid, $gid)
    {
        $user_groups_table = $this->prefix . 'user_groups';

        $query = "INSERT INTO `$user_groups_table`\n"
            . "(`gid`, `uid`) VALUES\n"
            . "(:gid, :uid)";

        $sth = $this->dbh->query($query);
        $sth->bindValue(':gid', $gid, \PDO::PARAM_INT);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * @param int $uid
     * @param int $gid
     */
    public function userRemoveGroup($uid, $gid)
    {
        $user_groups_table = $this->prefix . 'user_groups';

        $query = "DELETE FROM `$user_groups_table`\n"
            . "WHERE `uid` :uid AND `gid`=:gid";

        $sth = $this->dbh->query($query);
        $sth->bindValue(':gid', $gid, \PDO::PARAM_INT);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * @param int      $cid
     * @param int      $uid
     * @param string   $subject
     * @param string   $description
     * @param int      $catid
     * @return int
     */
    public function createEvent($cid, $uid, $subject, $description, $catid)
    {
        $query = "INSERT INTO `" . $this->prefix . "events`\n"
            . "(`cid`, `owner`, `subject`, `description`, `catid`)\n"
            . "VALUES (:cid, :uid, :subject, :description, :catid)";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->bindValue(':subject', $subject);
        $sth->bindValue(':description', $description);
        $sth->bindValue(':catid', $catid, \PDO::PARAM_INT);
        $sth->execute();

        return intval($this->dbh->lastInsertId());
    }

    /**
     * @param int                $eid
     * @param int                $time_type
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return string
     */
    public function createOccurrence($eid, $time_type, \DateTimeInterface $start, \DateTimeInterface $end)
    {
        // Stored as UTC
        if ($time_type == 0) {
            $start_str = datetime_to_sql_date($start);
            $end_str = datetime_to_sql_date($end);
        } else {
            // ignore the time for full day events
            $start_str = $start->format("Y-m-d");
            $end_str = $end->format("Y-m-d");
        }

        $query = "INSERT INTO `{$this->prefix}occurrences`\n"
            . "SET `eid`=:eid, `time_type`=:time_type, `start`='$start_str', `end`='$end_str'";
        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        $sth->bindValue(':time_type', $time_type, \PDO::PARAM_INT);
        $sth->execute();

        return $this->dbh->lastInsertId();
    }

    /**
     * @param int    $eid
     * @param int    $fid
     * @param string $value
     */
    public function addEventField($eid, $fid, $value)
    {
        $query = "INSERT INTO `{$this->prefix}event_fields`\n"
            . "SET `eid`=:eid, `fid`=:fid, `value`=:value";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        $sth->bindValue(':fid', $fid, \PDO::PARAM_INT);
        $sth->bindValue(':value', $value);
        $sth->execute();
    }

    /**
     * @param int                $oid
     * @param int                $time_type
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return bool
     */
    public function modifyOccurrence($oid, $time_type, \DateTimeInterface $start, \DateTimeInterface $end)
    {
        // Stored as UTC
        if ($time_type == 0) {
            $start_str = datetime_to_sql_date($start);
            $end_str = datetime_to_sql_date($start);
        } else {
            // ignore the time for full day events
            $start_str = $start->format("Y-m-d");
            $end_str = $end->format("Y-m-d");
        }

        $query = "UPDATE `{$this->prefix}occurrences`\n"
            . "SET `time_type`=:time_type, `start`='$start_str', `end`='$end_str'\n"
            . "WHERE `oid`=:oid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':time_type', $time_type, \PDO::PARAM_INT);
        $sth->bindValue(':oid', $oid, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->rowCount() > 0;
    }

    /**
     * @param int      $eid
     * @param string   $subject
     * @param string   $description
     * @param bool|int $catid
     */
    public function modifyEvent($eid, $subject, $description, $catid = false)
    {

        $query = "UPDATE `{$this->prefix}events`\n"
            . "SET\n"
            . "`subject`=:subject,\n"
            . "`description`=:description,\n"
            . "`mtime`=NOW(),\n"
            . "`catid`=" . ($catid !== false ? ":catid" : "NULL") . "\n"
            . "WHERE `eid`=:eid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':eid', $eid, \PDO::PARAM_INT);
        if ($catid !== false) {
            $sth->bindValue(':catid', $catid, \PDO::PARAM_INT);
        }
        $sth->bindValue(':subject', $subject);
        $sth->bindValue(':description', $description);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int      $cid
     * @param string   $name
     * @param string   $text_color
     * @param string   $bg_color
     * @param int      $gid
     * @return string
     */
    public function createCategory($cid, $name, $text_color, $bg_color, $gid)
    {
        $query = "INSERT INTO `{$this->prefix}categories`\n"
            . "SET `cid`=:cid, `name`=:name, `text_color`=:text_color, `bg_color`=:bg_color\n";
        if ($gid !== false) {
            $query .= ", `gid`=:gid";
        }

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        if ($gid !== false) {
            $sth->bindValue(':gid', $gid, \PDO::PARAM_INT);
        }
        $sth->bindValue(':name', $name);
        $sth->bindValue(':text_color', $text_color);
        $sth->bindValue(':bg_color', $bg_color);
        $sth->execute();

        return $this->dbh->lastInsertId();
    }

    /**
     * @param int    $cid
     * @param string $name
     */
    public function createGroup($cid, $name)
    {
        $query = "INSERT INTO `{$this->prefix}groups`\n"
            . "SET `cid`=:cid, `name`=:name";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->bindValue(':name', $name);
        $sth->execute();

        return $this->dbh->lastInsertId();
    }

    /**
     * @param int    $cid
     * @param string $name
     * @param bool   $required
     * @param string $format
     * @return string
     */
    public function createField($cid, $name, $required, $format)
    {
        if ($format === false) {
            $format_str = 'NULL';
        } else {
            $format_str = ":format";
        }

        $query = "INSERT INTO `{$this->prefix}fields`\n"
            . "`cid`=:cid, `name`=:name, `required`=:required, `format`=$format_str";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->bindValue(':required', asbool($required), \PDO::PARAM_BOOL);
        if ($format !== false) {
            $sth->bindValue(':format', $format);
        }
        $sth->bindValue(':name', $name);
        $sth->execute();

        return $this->dbh->lastInsertId();
    }

    /**
     * @param int    $catid
     * @param string $name
     * @param string $text_color
     * @param string $bg_color
     * @param int    $gid
     */
    public function modifyCategory($catid, $name, $text_color, $bg_color, $gid)
    {
        $query = "UPDATE `{$this->prefix}categories`\n"
            . "SET `name`=:name, `text_color`=:text_color, `bg_color`=:bg_color, `gid`=:gid\n"
            . "WHERE `catid`=:catid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':gid', $gid, \PDO::PARAM_INT);
        $sth->bindValue(':catid', $catid, \PDO::PARAM_INT);
        $sth->bindValue(':name', $name);
        $sth->bindValue(':text_color', $text_color);
        $sth->bindValue(':bg_color', $bg_color);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int    $gid
     * @param string $name
     */
    public function modifyGroup($gid, $name)
    {
        $query = "UPDATE `{$this->prefix}groups`\n"
            . "SET `name`=:name\n"
            . "WHERE `gid`=:gid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':gid', $gid, \PDO::PARAM_INT);
        $sth->bindValue(':name', $name);
        $sth->execute();

        if ($sth->rowCount() > 0) {
            throw new FailedActionException();
        }
    }

    /**
     * @param int         $fid
     * @param string      $name
     * @param bool        $required
     * @param bool|string $format
     */
    public function modifyField($fid, $name, $required, $format)
    {
        if ($format === false) {
            $format_val = 'NULL';
        } else {
            $format_val = ":format";
        }

        $query = "UPDATE `{$this->prefix}fields`\n"
            . "SET `name`=:name, `required`=:required, `format`=$format_val\n"
            . "WHERE `fid`=:fid";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':fid', $fid, \PDO::PARAM_INT);
        $sth->bindValue(':required', asbool($required), \PDO::PARAM_BOOL);
        if ($format !== false) {
            $sth->bindValue(':format', $format);
        }
        $sth->bindValue(':name', $name);
        $sth->execute();

        if ($sth->rowCount() == 0) {
            throw new FailedActionException();
        }
    }

    /**
     * $sort and $order must be checked
     *
     * @param  int                     $cid
     * @param  string                  $query
     * @param  \DateTimeInterface|null $start
     * @param  \DateTimeInterface|null $end
     * @param  string|null             $sort
     * @param  string|null             $order
     * @return Occurrence[]
     */
    public function search($cid, $query, $start = null, $end = null, $sort = null, $order = null)
    {
        if ($sort == null) {
            $sort = 'subject';
        }
        if ($order == null) {
            $order = 'ASC';
        }
        $events_table = $this->prefix . 'events';
        $occurrences_table = $this->prefix . 'occurrences';
        $users_table = $this->prefix . 'users';
        $cats_table = $this->prefix . 'categories';

        $words = array();
        $keywords = explode(" ", $query);
        foreach ($keywords as $unsafe_keyword) {
            $keyword = $this->dbh->quote("%$unsafe_keyword%");
            $words[] = "(`subject` LIKE $keyword OR `description` LIKE $keyword)\n";
        }
        $where = implode(' AND ', $words);

        if ($start) {
            //$start_str = sqlDate($start);
            $start_date = $start->format('Y-m-d');
            // Search doesn't have a field for time
            $where .= "AND DATE(`start`) <= DATE('$start_date')\n";
        }
        if ($end) {
            //$end_str = sqlDate($end);
            $end_date = $end->format('Y-m-d');
            $where .= "AND DATE(`end`) >= DATE('$end_date')\n";
        }

        $query = "SELECT {$this->occurrence_columns}, `username`, `name`, `bg_color`, `text_color`\n"
            . "FROM `$events_table`\n"
            . "INNER JOIN `$occurrences_table` USING (`eid`)\n"
            . "LEFT JOIN `$users_table` ON `uid`=`owner`\n"
            . "LEFT JOIN `$cats_table` USING (`catid`)\n"
            . "WHERE ($where)\n"
            . "AND `$events_table`.`cid`=:cid\n"
            . "ORDER BY `$sort` $order";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->execute();

        $occurrences = array();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $occurrences[] = new Occurrence($this, $row);
        }
        return $occurrences;
    }

    /**
     * @param int    $cid
     * @param int    $uid
     * @param bool[] $perms
     */
    public function updatePermissions($cid, $uid, $perms)
    {
        $stmts = array();
        foreach (['read', 'write', 'modify', 'admin'] as $name) {
            $stmts[] = "`$name`=" . asbool($perms[$name]);
        }
        $perm_str = implode(', ', $stmts);

        $query = "INSERT INTO `{$this->prefix}permissions`\n"
            . "SET `cid`=:cid, `uid`=:uid, $perm_str\n"
            . "ON DUPLICATE KEY UPDATE $perm_str";

        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':cid', $cid, \PDO::PARAM_INT);
        $sth->bindValue(':uid', $uid, \PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * @return string[]
     */
    public function update()
    {
        $updates = [];

        foreach (phpc_table_schemas($this->prefix) as $table) {
            $updates[] = $table->update($this->dbh);
        }

        $this->setConfig("version", PHPC_DB_VERSION);

        return $updates;
    }

    /**
     * @param bool $drop
     * @return string[]
     */
    public function create($drop = false)
    {
        $updates = [];

        foreach (phpc_table_schemas($this->prefix) as $table) {
            $updates[] = $table->create($this->dbh, $drop);
        }

        $this->setConfig("version", PHPC_DB_VERSION);

        return $updates;
    }
}

/**
 * @param bool $val
 * @return string
 */
function asbool($val)
{
    return $val ? "1" : "0";
}

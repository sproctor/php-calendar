<?php
/*
 * Copyright 2013 Sean Proctor
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

if(!defined('IN_PHPC')) {
	die("Hacking attempt");
}

function import() {
	global $vars, $phpcdb, $phpc_cal, $phpcid, $phpc_script;

	if(!is_admin()) {
		permission_error(__('Need to be admin'));
		exit;
	}

	$form_page = "$phpc_script?action=admin#phpc-import";

	if(!empty($vars['port']) && strlen($vars['port']) > 0) {
		$port = $vars['port'];
	} else {
		$port = ini_get("mysqli.default_port");
	}

	$old_dbh = @new mysqli($vars['host'], $vars['username'], $vars['passwd'],
			$vars['dbname'], $port);

	if(!$old_dbh || mysqli_connect_errno()) {
		return message_redirect("Database connect failed ("
				. mysqli_connect_errno() . "): "
				. mysqli_connect_error(),
				$form_page);
	}

	$events_table = $vars['prefix'] . 'events';
	$users_table = $vars['prefix'] . 'users';

	// Create user lookup table
	$users = array('anonymous' => '0');
	foreach($phpcdb->get_users() as $user) {
		$users[$user->get_username()] = $user->get_uid();
	}

	// Lookup old events
	$query = "SELECT YEAR(`startdate`) as `year`, "
		."MONTH(`startdate`) as `month`, "
		."DAY(`startdate`) as `day`, YEAR(`enddate`) as `endyear`,"
		."MONTH(`enddate`) as `endmonth`, DAY(`enddate`) as `endday`,"
		."HOUR(`starttime`) as `hour`, MINUTE(`starttime`) as `minute`,"
		."`duration`, `eventtype`, `subject`, `description`,"
		."`username`, `password`\n"
		."FROM `$events_table`\n"
		."LEFT JOIN `$users_table` USING (`uid`)\n";

	$sth = $old_dbh->query($query)
		or $phpcdb->db_error(__('Error selecting events in import'),
				$query);

	$events = 0;
	$occurrences = 0;

	while($result = $sth->fetch_assoc()) {
		$username = $result['username'];
		if(empty($username) || strlen($username) == 0) {
			$uid = 0;
		} else {
			if(!isset($users[$username])) {
				$users[$username] = $phpcdb->create_user($username,
						$result['password'], false);
			}
			$uid = $users[$username];
		}

		$eid = $phpcdb->create_event($phpcid, $uid,
				$phpcdb->dbh->escape_string($result["subject"]),
				$phpcdb->dbh->escape_string($result["description"]),
				false, false);
		$events++;

		$eventtype = $result['eventtype'];
		// Full Day or None
		if($eventtype == 2 || $eventtype == 4)
			$time_type = 1;
		// TBA
		elseif($eventtype == 3)
			$time_type = 2;
		// Normal (and all of the recurring)
		else
			$time_type = 0;

		$year = $result['year'];
		$month = $result['month'];
		$day = $result['day'];
		$hour = $result['hour'];
		$minute = $result['minute'];
		$duration = $result['duration'];

		$final_ts = mktime($hour, $minute, 0, $result['endmonth'],
				$result['endday'], $result['endyear']);

		while(true) {
			$start_ts = mktime($hour, $minute, 0, $month, $day,
					$year);
			if($start_ts > $final_ts)
				break;

			$endminute = $minute + ($duration % 60);
			$endhour = ($hour + floor($duration / 60)) % 24;
			$endday = $day + floor($endhour / 24);
		
			$end_ts = mktime($endhour, $endminute, 0, $month,
					$endday, $year);
			$phpcdb->create_occurrence($eid, $time_type, $start_ts, $end_ts);
			$occurrences++;

			// Increment start time
			switch($eventtype) {
			case 1: // Normal
			case 2: // Full Day
			case 3: // TBA
			case 4: // None
				$day++;
				break;
			case 5: // Weekly
				$day += 7;
				break;
			case 6: // Monthly
				$month++;
				break;
			case 7: // Yearly
				$year++;
				break;
			default:
				echo "bad event!!";
				exit;
			}
		}
	}


	return message_redirect(sprintf(__("Created %s events with %s occurences"),
				$events, $occurrences),
			$form_page);
}

?>

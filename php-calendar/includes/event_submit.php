<?php
/*
   Copyright 2002 Sean Proctor, Nathan Poiro

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

function submit_event()
{
	global $HTTP_GET_VARS, $calno, $user, $day, $month, $year, $db_events;

	if(isset($HTTP_GET_VARS['modify'])) {
		if(!isset($HTTP_GET_VARS['id'])) {
			soft_error(_('No ID given.'));
		}
		$id = $HTTP_GET_VARS['id'];
		$modify = 1;
	} else {
		$modify = 0;
	}

	if($HTTP_GET_VARS['description']) {
		$description = ereg_replace('<[bB][rR][^>]*>', "\n", 
				$HTTP_GET_VARS['description']);
	} else {
		$description = '';
	}

	if($HTTP_GET_VARS['subject']) {
		$subject = addslashes(ereg_replace('<[^>]*>', '', 
					$HTTP_GET_VARS['subject']));
	} else {
		$subject = '';
	}

	if($HTTP_GET_VARS['username']) {
		$username = addslashes(ereg_replace('<[^>]*>', '',
					$HTTP_GET_VARS['username']));
	} else {
		$username = '';
	}

	if($HTTP_GET_VARS['description']) {
		$description = addslashes(ereg_replace('</?([^aA/]|[a-zA-Z_]{2,})[^>]*>',
					'', $HTTP_GET_VARS['description']));
	} else {
		$description = '';
	}

	if(empty($HTTP_GET_VARS['day'])) soft_error(_('No day was given.'));

	if(empty($HTTP_GET_VARS['month'])) soft_error(_('No month was given.'));

	if(empty($HTTP_GET_VARS['year'])) soft_error(_('No year was given'));

	if(isset($HTTP_GET_VARS['hour'])) $hour = $HTTP_GET_VARS['hour'];
	else soft_error(_('No hour was given.'));

	if(isset($HTTP_GET_VARS['pm']) && $HTTP_GET_VARS['pm'] == 1) $hour += 12;

	if(isset($HTTP_GET_VARS['minute'])) $minute = $HTTP_GET_VARS['minute'];
	else soft_error(_('No minute was given.'));

	if(isset($HTTP_GET_VARS['durationmin']))
		$duration_min = $HTTP_GET_VARS['durationmin'];
	else soft_error(_('No duration minute was given.'));

	if(isset($HTTP_GET_VARS['durationhour']))
		$duration_hour = $HTTP_GET_VARS['durationhour'];
	else soft_error(_('No duration hour was given.'));

	if(isset($HTTP_GET_VARS['typeofevent']))
		$typeofevent = $HTTP_GET_VARS['typeofevent'];
	else soft_error(_('No type of event was given.'));

	if(isset($HTTP_GET_VARS['endday']))
		$end_day = $HTTP_GET_VARS['endday'];
	else soft_error(_('No end day was given'));

	if(isset($HTTP_GET_VARS['endmonth']))
		$end_month = $HTTP_GET_VARS['endmonth'];
	else soft_error(_('No end month was given'));

	if(isset($HTTP_GET_VARS['endyear']))
		$end_year = $HTTP_GET_VARS['endyear'];
	else soft_error(_('No end year was given'));

	if(strlen($subject) > SUBJECT_MAX) {
		soft_error('Your subject was too long.  '.SUBJECT_MAX.' characters max.');
	}

	$startstamp = mktime($hour, $minute, 0, $month, $day, $year);
	$startdate = date('Y-m-d', $startstamp);
	$starttime = date('H:i:s', $startstamp);

	$endstamp = mktime(0, 0, 0, $end_month, $end_day, $end_year);
	$enddate = date('Y-m-d', $endstamp);
	$duration = $duration_hour * 60 + $duration_min;

	if($modify) {
		if(empty($user) && ANON_PERMISSIONS < 2) {
			soft_error('You do not have permission to modify events.');
		}
		$query = 'UPDATE '.SQL_PREFIX."events\n"
			."SET username='$username',\n"
			."startdate='$startdate',\n"
			."enddate='$enddate',\n"
			."starttime='$starttime',\n"
			."duration='$duration',\n"
			."subject='$subject',\n"
			."description='$description',\n"
			."eventtype='$typeofevent'\n"
			."WHERE id='$id'";
	} else {
		if(empty($user) && ANON_PERMISSIONS < 1) {
			soft_error('You do not have permission to post.');
		}
		$query = 'INSERT INTO '.SQL_PREFIX."events\n"
			."(username, startdate, enddate, starttime, duration,"
			." subject, description, eventtype, calno)\n"
			."VALUES ('$username', '$startdate', '$enddate',"
			."'$starttime', '$duration', '$subject',"
			."'$description', '$typeofevent', '$calno')";
	}

	$result = $db_events->sql_query($query);

	if(!$result) {
		$error = $db_events->sql_error();
		soft_error(_('Error updating event')
				." $error[code]: $error[message]\n"
				."sql:\n$query");
	}

	$affected = $db_events->sql_affectedrows($result);
	if($affected < 1) soft_error(_('No changes made')."\nsql:\n$query");
	return '<div class="box">'._('Date updated').": $affected</div>\n";
}
?>

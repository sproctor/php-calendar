<?php
/*
   Copyright 2002, 2005 Sean Proctor, Nathan Poiro

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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function event_submit($calendar)
{
        /* Validate input */
	if(isset($calendar->vars['id'])) {
		$id = $calendar->vars['id'];
		$modify = 1;
	} else {
		$modify = 0;
	}

	if(isset($calendar->vars['description'])) {
		$description = addslashes(strip_tags(ereg_replace
                                        ('<[bB][rR][^>]*>', "\n", 
                                         $calendar->vars['description']),
                                        '<a>'));
	} else {
		$description = '';
	}

	if(isset($calendar->vars['subject'])) {
		$subject = addslashes(strip_tags($calendar->vars['subject']));
	} else {
		$subject = '';
	}

	if(empty($calendar->vars['day'])) {
                soft_error(_('No day was given.'));
        } else {
                $day = $calendar->day;
        }

	if(empty($calendar->vars['month'])) {
                soft_error(_('No month was given.'));
        } else {
                $month = $calendar->month;
        }

	if(empty($calendar->vars['year'])) {
                soft_error(_('No year was given'));
        } else {
                $year = $calendar->year;
        }

	if(!isset($calendar->vars['hour'])) {
                soft_error(_('No hour was given.'));
	} else {
                $hour = $calendar->vars['hour'];
        }

        if(!$calendar->get_config('hours_24')) {
                if(!empty($calendar->vars['pm'])) {
                        if($hour < 12) {
                                $hour += 12;
                        }
                } elseif($hour == 12) {
                        $hour = 0;
                }
        }

        if(!isset($calendar->vars['minute'])) {
                soft_error(_('No minute was given.'));
        } else {
                $minute = $calendar->vars['minute'];
        }

	if(!isset($calendar->vars['durationmin'])) {
	        soft_error(_('No duration minute was given.'));
        } else {
		$duration_min = $calendar->vars['durationmin'];
        }

	if(!isset($calendar->vars['durationhour'])) {
                soft_error(_('No duration hour was given.'));
	} else {
		$duration_hour = $calendar->vars['durationhour'];
        }

	if(!isset($calendar->vars['type'])) {
                soft_error(_('No type of event was given.'));
	} else {
		$type = $calendar->vars['type'];
        }

        if($type == 1) {
	if(!isset($calendar->vars['endday'])) {
                soft_error(_('No end day was given'));
        } else {
		$end_day = $calendar->vars['endday'];
        }

	if(isset($calendar->vars['endmonth'])){
                soft_error(_('No end month was given'));
        } else {
		$end_month = $calendar->vars['endmonth'];
        }

	if(isset($calendar->vars['endyear'])) {
                soft_error(_('No end year was given'));
        } else {
		$end_year = $calendar->vars['endyear'];
        }
        }

	if(strlen($subject) > $calendar->get_config('subject_max')) {
		soft_error(_('Your subject was too long')
				.". $config[subject_max] "._('characters max')
				.".");
	}

	$uid = $calendar->get_uid();

	$startstamp = mktime($hour, $minute, 0, $month, $day, $year);
	$endstamp = mktime(0, 0, 0, $end_month, $end_day, $end_year);

        if($endstamp < mktime(0, 0, 0, $month, $day, $year)) {
                soft_error(_('The start of the event cannot be after the end of the event.'));
        }
	$startdate = $db->DBDate($startstamp);
	$starttime = date('H:i:s', $startstamp);

	$enddate = $db->DBDate($endstamp);
	$duration = $duration_hour * 60 + $duration_min;

	if($modify) {
		if(!check_user() && $config['anon_permission'] < 2) {
			soft_error(_('You do not have permission to modify events.'));
		}
		$query = "UPDATE ".SQL_PREFIX."events\n"
			."SET startdate=$startdate,\n"
			."enddate=$enddate,\n"
			."starttime='$starttime',\n"
			."duration='$duration',\n"
			."subject='$subject',\n"
			."description='$description',\n"
			."eventtype='$typeofevent'\n"
			."WHERE id='$id'";

                $result = $calendar->db->Execute($query)
                        or $calendar->
                        $calendar->db_error(_('Error creating event'), $query);

                $affected = $calendar->db->Affected_Rows($result);
                if($affected < 1) return tag('div', _('No changes were made.'));
	} else {
		if(!check_user() &&
                                $calendar->get_config('anon_permission') < 1) {
			soft_error(_('You do not have permission to post.'));
		}
		$id = $calendar->db->GenID(SQL_PREFIX . 'sequence');
		$query = "INSERT INTO ".SQL_PREFIX."events\n"
			."(id, uid, type, time, "
                        ."duration, subject, description, "
                        ."calendar)\n"
			."VALUES ($id, $uid, $typeofevent, '$starttime', "
                        ."'$duration', '$subject', '$description', "
                        ."'{$calendar->id}')";
                $result = $calendar->db->Execute($query)
                        or $calendar->db_error($query);

                $values = array('event_id' => $id);
                if(!empty($startdate)) {
                        $values['start_date'] = $startdate;
                }
                if(!empty($enddate)) {
                        $value['end_date'] = $enddate;
                }
                $query = "INSERT INTO ".SQL_PREFIX."occurrences
                        (".implode(', ', array_keys($values)).")
                        VALUES (".implode(', ', $values).")";

                $result = $calendar->db->Execute($query)
                        or $calendar->db_error($query);
	}

        $calendar->session_write_close();

	header("Location: $phpc_script?action=display&id=$id");
	return tag('div', attributes('class="box"'), _('Date submitted'));
}

?>

<?php
/*
    Copyright 2002 Sean Proctor

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

include("event.inc");

function submit_event()
{
  global $sql_tableprefix, $HTTP_GET_VARS;

  $database = connect_to_database();
  if(isset($HTTP_GET_VARS['modify'])) {
    if(!isset($HTTP_GET_VARS['id'])) {
      soft_error(_("No ID given."));
    }
    $id = $HTTP_GET_VARS['id'];
    $modify = 1;
  } else {
    $modify = 0;
  }

  if($HTTP_GET_VARS['description']) {
    $description = ereg_replace("<[bB][rR][^>]*>", "\n", 
    $HTTP_GET_VARS['description']);
  } else {
    $description = '';
  }
     
  if($HTTP_GET_VARS['subject']) {
    $subject = addslashes(ereg_replace("<[^>]*>", "", 
      $HTTP_GET_VARS['subject']));
  } else {
    $subject = '';
  }

  if($HTTP_GET_VARS['username']) {
    $username = addslashes(ereg_replace("<[^>]*>", "",
      $HTTP_GET_VARS['username']));
  } else {
    $username = '';
  }

  if($HTTP_GET_VARS['description']) {
    $description = addslashes(ereg_replace("</?([^aA/]|[a-zA-Z_]{2,})[^>]*>",
    "", $HTTP_GET_VARS['description']));
  } else {
    $description = '';
  }

  if(!isset($HTTP_GET_VARS['day'])) $day = date("j");
  else $day = $HTTP_GET_VARS['day'];

  if(!isset($HTTP_GET_VARS['month'])) $month = date("n");
  else $month = $HTTP_GET_VARS['month'];

  if(!isset($HTTP_GET_VARS['year'])) $year = date("Y");
  else $year = $HTTP_GET_VARS['year'];

  if(isset($HTTP_GET_VARS['hour'])) $hour = $HTTP_GET_VARS['hour'];
  else $hour = 0;

  if(isset($HTTP_GET_VARS['pm']) && $HTTP_GET_VARS['pm'] == 1) $hour += 12;

  if(isset($HTTP_GET_VARS['minute'])) $minute = $HTTP_GET_VARS['minute'];
  else $minute = 0;

  if(isset($HTTP_GET_VARS['durationhour']))
    $durationhour = $HTTP_GET_VARS['durationhour'];
  else $durationhour = 1;

  if(isset($HTTP_GET_VARS['durationmin']))
    $durationmin = $HTTP_GET_VARS['durationmin'];
  else $durationmin = 0;
     
  if(isset($HTTP_GET_VARS['durationday']))
    $durationday = $HTTP_GET_VARS['durationday'];
  else $durationday = 0;

  if(isset($HTTP_GET_VARS['typeofevent']))
    $typeofevent = $HTTP_GET_VARS['typeofevent'];
  else $typeofevent = 0;

  $timestamp = date("Y-m-d H:i:s", mktime($hour,$minute,0,$month,$day,$year));
  $durationstamp = date("Y-m-d H:i:s", mktime($hour + $durationhour,
    $minute + $durationmin, 0, $month, $day + $durationday, $year));

  if($modify) {
    $query = 'UPDATE ' . $sql_tableprefix . "events SET username='$username',
      stamp='$timestamp',
      subject='$subject',
      description='$description',
      eventtype='$typeofevent', duration='$durationstamp' WHERE id='$id'";
  } else {
    $query = 'INSERT INTO ' . $sql_tableprefix . "events 
      (username, stamp, subject, description, eventtype, duration) 
      VALUES ('$username', '$timestamp', '$subject', '$description', 
      '$typeofevent', '$durationstamp')";
  }

  $result = mysql_query($query);
  if(empty($result)) {
    soft_error('Error updating event: error: '. mysql_error()
      . "<br>sql: $query");
  }
  return "<div class=\"box\">" . 
_("Date updated.")
 . "</div>";
}
?>

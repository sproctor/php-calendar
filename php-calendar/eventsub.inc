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

include_once("event.inc");

function submit_event()
{
  global $sql_tableprefix;

  $database = connect_to_database();
  $output = '';
  if(!isset($_GET['id'])) {
      $output .= "<div class=\"box\">" . 
_("No ID given.")
 . "</div>";
      return $output;
    } else {
      $id = $_GET['id'];
    }

    if (isset($_GET['modify'])) {
      remove_event($id)
        or soft_error("couldn't delete item");
      if(mysql_affected_rows() == 0) {
        $output .= "<div class=\"box\">" . 
_("Item already deleted.")
 . "</div>";
        return $output;
      }
    } else {
      $result = mysql_query("SELECT max(id) as id FROM " . $sql_tableprefix
        . "events");
      if($result) {
        $row = mysql_fetch_array($result);
        if($id != $row['id'] + 1) {
          $output .= "<div class=\"box\">" . 
_("Item already created.")
 . "</div>";
          return $output;
        }
      }
    }

    if($_GET['description']) {
      $description = ereg_replace("<[bB][rR][^>]*>", "\n", 
      $_GET['description']);
    } else {
      $description = '';
    }
     
    if($_GET['subject']) {
      $subject = addslashes(ereg_replace("<[^>]*>", "", $_GET['subject']));
    } else {
      $subject = '';
    }

    if($_GET['username']) {
      $username = addslashes(ereg_replace("<[^>]*>", "", $_GET['username']));
    } else {
      $username = '';
    }

    if($_GET['description']) {
      $description = addslashes(ereg_replace("</?([^aA/]|[a-zA-Z_]{2,})[^>]*>",
      "", $_GET['description']));
    } else {
      $description = '';
    }

if(!isset($_GET['day'])) $day = date("j");
else $day = $_GET['day'];

if(!isset($_GET['month'])) $month = date("n");
else $month = $_GET['month'];

if(!isset($_GET['year'])) $year = date("Y");
else $year = $_GET['year'];

    if(isset($_GET['hour'])) $hour = $_GET['hour'];
    else $hour = 0;

    if(isset($_GET['pm']) && $_GET['pm'] == 1) $hour += 12;

    if(isset($_GET['minute'])) $minute = $_GET['minute'];
    else $minute = 0;

    if(isset($_GET['durationhour'])) $durationhour = $_GET['durationhour'];
    else $durationhour = 1;

    if(isset($_GET['durationmin'])) $durationmin = $_GET['durationmin'];
    else $durationmin = 0;
     
    if(isset($_GET['durationday'])) $durationday = $_GET['durationday'];
    else $durationday = 0;

    if(isset($_GET['typeofevent'])) $typeofevent = $_GET['typeofevent'];
    else $typeofevent = 0;

    $timestamp = date("Y-m-d H:i:s", mktime($hour,$minute,0,$month,$day,$year));
    $durationstamp = date("Y-m-d H:i:s", mktime($hour+$durationhour,$minute+$durationmin,0,$month,$day+$durationday,$year));

    $result = mysql_query("INSERT INTO " . $sql_tableprefix . "events 
  (username, stamp, subject, description, eventtype, duration) 
  VALUES ('$username', '$timestamp', '$subject', '$description', 
  '$typeofevent', '$durationstamp')");
    if ($result)
      $output .= "<div class=\"box\">" . 
_("Item added.")
 . "</div>";
    else {
      $output .= "<div class=\"box\">" . 
_("Error adding item: ")
 . mysql_error() . "</div>";
    }
  return $output;
}
?>

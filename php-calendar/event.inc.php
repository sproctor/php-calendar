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

include('calendar.inc.php');

function event_form($action)
{
  global $BName, $HTTP_GET_VARS;

  if(!isset($HTTP_GET_VARS['day'])) $day = date("j");
  else $day = $HTTP_GET_VARS['day'];

  if(!isset($HTTP_GET_VARS['month'])) $month = date("n");
  else $month = $HTTP_GET_VARS['month'];

  if(!isset($HTTP_GET_VARS['year'])) $year = date("Y");
  else $year = $HTTP_GET_VARS['year'];

  $output = '<form method="get" action="eventsub.php">
<table class="box"';
  if($BName == 'MSIE') {
    $output .= ' cellspacing="0"';
  }

  $output .= '>
  <thead>
  <tr>
    <th colspan="2">';

  if($action == 'modify') {
    if (!isset($HTTP_GET_VARS['id'])) {
      $output .= '<div class="box">'
        . _("Nothing to modify.")
        . '</div>';
      return;
    } else {
      $id = $HTTP_GET_VARS['id'];
    }

    $output .= sprintf(_("Modifying id #%d"), $id);

    $result = get_event_by_id($id);

    $row = mysql_fetch_array($result);
    $username = stripslashes($row['username']);
    $subject = stripslashes($row['subject']);
    $desc = htmlspecialchars(stripslashes($row['description']));
    $thetime = $row['start_since_epoch'];
    $hour = date('G', $thetime);
    $minute = date('i', $thetime);
    $month = date('n', $thetime);
    $year = date('Y', $thetime);
    $day = date('j', $thetime);
    $durtime = $row['end_since_epoch'] - $thetime;
    $durmin = ($durtime / 60) % 60;     //seconds per minute
    $durhr  = ($durtime / 3600) % 24;   //seconds per hour
    $durday = floor($durtime / 86400);  //seconds per day

    if(empty($hours_24)) {
      if($hour >= 12) {
        $pm = 1;
        $hour = $hour - 12;
      } else $pm = 0;
    }

    $typeofevent = $row['eventtype'];

  } else {
    // case "add":
    $output .= _('Adding item to calendar');

    $username = '';
    $subject = '';
    $desc = '';
    if($day == date('j') && $month == date('n') && $year == date('Y')) {
      $hour = date('G') + 1;
      if(empty($hours_24)) {
        if($hour >= 12) {
          $hour = $hour - 12;
          $pm = 1;
        } else $pm = 0;
      }
    } else { $hour = 6; $pm = 1; }
    $minute = 0;
    $durhr = 1;
    $durday = 0;
    $durmin = 0;
    $typeofevent = 1;
  }

  $output .= '</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td>' . _('Name') . "</td>
    <td><input type=\"text\" name=\"username\" size=\"20\" value=\"$username\" /></td>
  </tr>
  <tr><td>" . _('Day') . "</td>
    <td>
      <select name=\"day\" size=\"1\">\n";
     
  $lastday = date('t', mktime(0, 0, 0, $month, 1, $year));
  for ($i = 1; $i <= $lastday; $i++){
    if ($i == $day) {
      $output .= "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
    } else {
      $output .= "        <option value=\"$i\">$i</option>\n";
    }
  }

  $output .= "      </select>\n      <select size=\"1\" name=\"month\">\n";

  for ($i = 1; $i <= 12; $i++) {
    $nm = month_name($i);
    if ($i == $month) {
      $output .= "        <option value=\"$i\" selected=\"selected\">$nm</option>\n";
    } else {
      $output .= "        <option value=\"$i\">$nm</option>\n";
    }
  }

  $output .= "      </select>\n      <select size=\"1\" name=\"year\">";

  for ($i=$year-2; $i<$year+5; $i++) {
    if ($i == $year) {
      $output .= "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
    } else {
      $output .= "        <option value=\"$i\">$i</option>\n";
    }
  }

  $output .= '      </select></td>
  </tr>
  <tr>
    <td>' . _('Event Type') . '</td>
    <td>
<select name="typeofevent" size="1">
<option value="1"';

  if($typeofevent == 1) {
    $output .= ' selected="selected"';
  }

  $output .= '>' . _('Normal Event') . '</option>
<option value="2"';

  if($typeofevent == 2) {
    $output .= ' selected="selected"';
  }

  $output .= '>' . _('Full Day Event') . '</option>
<option value="3"';

  if($typeofevent == 3) {
    $output .= ' selected="selected"';
  }

  $output .= '>' .  _('Unkown Time') . '</option>
</select>
    </td>
  </tr>
  <tr>
    <td>' .  _('Time') . "</td>
    <td>
<select name=\"hour\" size=\"1\">\n";

    if(empty($hours_24)) {
      for($i = 1; $i <= 12; $i++) {
        $output .= '<option value="' . $i % 12 . '"';
        if($hour == $i) {
          $output .= ' selected="selected"';
        }
        $output .= ">$i</option>\n";
      }
    } else {
      for($i = 0; $i < 24; $i++) {
        $output .= "<option value=\"$i\"";
        if($hour == $i) {
          $output .= ' selected="selected"';
        }
        $output .= '>' . $i . "</option>\n";
      }
    }

    $output .= "</select><b>:</b><select name=\"minute\" size=\"1\">\n";

    for($i = 0; $i <= 59; $i = $i + 5) {
      $output .= "<option value='$i'";
      if($minute >= $i && $i > $minute - 5) {
        $output .= ' selected="selected"';
      }
      $output .= sprintf(">%02d</option>\n", $i);
    }

    $output .= "</select>\n";

    if(empty($hours_24)) {
      $output .= '<select name="pm" size="1">
<option value="0"';
      if(empty($pm)) {
        $output .= ' selected="selected"';
      }
      $output .= '>AM</option>
<option value="1"';
      if($pm) {
        $output .= ' selected="selected"';
      }
      $output .= ">PM</option>
</select>\n";
    }

$output .= '</td>
  </tr>
  <tr>
    <td>' .  _('Duration') . '</td>
    <td>
<select name="durationday" size="1">' . "\n";

    for($i = 0; $i < 31; $i++) {
      $output .= "  <option value='$i'";
      if($durday == $i) {
        $output .= ' selected="selected"';
      }
      $output .= ">$i</option>\n";
    }
    $output .= '</select>
' .  _('days') . '
<select name="durationhour" size="1">';
    for($i = 0; $i < 24; $i++) {
      $output .= "<option value='$i'";
      if($durhr == $i) {
        $output .= ' selected="selected"';
      }
      $output .= ">$i</option>\n";
    }
    $output .= '</select>
' .  _('hours') . "
<select name=\"durationmin\" size=\"1\">\n";
    for($i = 0; $i <= 59; $i = $i + 5) {
      $output .= "<option value='$i'";
      if($durmin >= $i && $i > $durmin - 5) {
        $output .= ' selected="selected"';
      }
      $output .= sprintf(">%02d</option>\n", $i);
    }
    $output .= '</select>
' .  _('minutes') . '
</td>
  </tr>
  <tr>
    <td>' .  _('Subject') .  ' ' . _('(255 chars max)') . "</td>
    <td><input type=\"text\" name=\"subject\" value=\"$subject\" /></td>
  </tr>
  <tr>
    <td>" .  _('Description') . "</td>
    <td>
      <textarea rows=\"5\" cols=\"50\" name=\"description\">$desc</textarea>
    </td>
  </tr>
  <tr>
    <td colspan=\"2\" style=\"text-align:center\">\n";
   
    if($action == 'modify') {
      $output .= "<input type=\"hidden\" name=\"modify\" value=\"1\" />
      <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
    }

    return $output . '<input type="submit" value="' .  _("Submit Item") . '" />
    </td>
  </tr>
  </tbody>
</table>
</form>' . "\n";
}
?>

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

function del()
{
  global $QUERY_STRING;

  $database = connect_to_database();
  $del_array = explode("&", $QUERY_STRING);

  $output = "<div class=\"box\" style=\"width: 50%\">";

  while(list(, $del_value) = each($del_array)) {
    list($drop, $id) = explode("=", $del_value);

    if(preg_match("/delete$/", $drop) == 0) continue;

    if(remove_event($id)) {
      $output .= sprintf(_("Removed item: %d"), $id) . "<br />\n";
    } else {        
      $output .= sprintf(_("Could not remove item: %d"), $id) . "<br />\n";
    }
  }

  return $output . "</div>";
}
?>

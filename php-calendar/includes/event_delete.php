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

function remove_event($id)
{
	global $db;

	$result = $db->sql_query('DELETE FROM '.SQL_PREFIX
			."events WHERE id = '$id'");
	if(!$result) {
		$error = $db->sql_error();
		soft_error("SQL error $error[code]: $error[message]");
	}

	if($db->sql_affectedrows($result) > 0)
		return true;
	else
		return false;
}

function delete()
{
	global $QUERY_STRING, $config;

	if(!check_user() && $config['anon_permission'] < 2) {
		soft_error('You do not have permission to delete events.');
	}

	$del_array = explode('&', $QUERY_STRING);

	$output = "<div class=\"box\" style=\"width: 50%\">\n";

	$selected = 0;

	while(list(, $del_value) = each($del_array)) {
		list($drop, $id) = explode("=", $del_value);

		if(preg_match('/^id$/', $drop) == 0) continue;

		$selected = 1;

		if(remove_event($id)) {
			$output .= _('Removed item').": $id<br />\n";
		} else {        
			$output .= _('Could not remove item').": $id<br />\n";
		}
	}

	if(!$selected) {
		$output .= _('No items selected.');
	}

	return $output . "</div>\n";
}
?>

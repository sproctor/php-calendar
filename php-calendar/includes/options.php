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

function options()
{
	global $calno, $vars, $db;

	//Check password and username
	if(isset($vars['submit'])){

		$query = "UPDATE ".SQL_PREFIX."calendars SET\n";

		if(isset($vars['hours_24']))
			$query .= "hours_24 = 1,\n";
		else
			$query .= "hours_24 = 0,\n";

		if(isset($vars['start_monday']))
			$query .= "start_monday = 1,\n";
		else
			$query .= "start_monday = 0,\n";

		if(isset($vars['translate']))
			$query .= "translate = 1,\n";
		else
			$query .= "translate = 0,\n";

		$calendar_title = $vars['calendar_title'];

		$query .= "anon_permission = '$vars[anon_perm]',\n"
			."calendar_title = '$calendar_title';";

		if(check_user()){
			$result = $db->sql_query($query);
			if(!$result) {
				$error = $db->sql_error();
				soft_error("$error[code]: $error[message]");
			}

			$output = "<div>"._('Updated options')."</div>\n";
		} else {
			$output = "<div>"._('Permission denied')."</div>\n";
		}

	} else {
		$output = options_form();
	}

	return $output;
}


function options_form()
{
	global $config;

	$output = "<form action=\"index.php\" method=\"post\">\n"
		."<table class=\"phpc-main\">\n"
		."<caption>"._('Options')."</caption>\n"
		."<tfoot>\n"
		."<tr>\n"
		."<td colspan=\"2\">\n"
		."<input type=\"hidden\" name=\"action\" value=\"options\" />\n"
		.'<input type="submit" name="submit" value="'._('Submit')
		."\" />\n"
		."</td>\n"
		."</tr>\n"
		."</tfoot>\n"
		."<tbody>\n"
		."<tr>\n"
		."<th>"._('Start Monday').":</th>\n"
		."<td><input name=\"start_monday\" type=\"checkbox\" "
		.($config['start_monday'] ? 'checked="checked" ' : '')
		."/></td>\n"
		."</tr>\n"
		."<tr>\n"
		."<th>"._('24 hour').":</th>\n"
		."<td><input name=\"hours_24\" type=\"checkbox\" "
		.($config['hours_24'] ? 'checked="checked" ' : '')
		."/></td>\n"
		."</tr>\n"
		."<tr>\n"
		."<th>"._('Translate').":</th>\n"
		."<td><input name=\"translate\" type=\"checkbox\" "
		.($config['translate'] ? 'checked="checked" ' : '')
		."/></td>\n"
		."</tr>\n"
		."<tr>\n"
		."<th>"._('Calendar Title').":</th>\n"
		."<td><input name=\"calendar_title\" type=\"text\" "
		."value=\"$config[calendar_title]\" /></td>\n"
		."</td>\n"
		."<tr>\n"
		."<th>"._('Anonymous Permission:')."</th>\n"
		."<td><select name=\"anon_perm\" size=\"1\">\n"
		."<option value=\"0\""
		.($config['anon_permission'] == 0 ? ' selected="selected"' : '')
		.">Cannot add events</option>\n"
		."<option value=\"1\""
		.($config['anon_permission'] == 1 ? ' selected="selected"' : '')
		.">Can add but not modify events</option>\n"
		."<option value=\"2\""
		.($config['anon_permission'] == 2 ? ' selected="selected"' : '')
		.">Can add and modify events</option>\n"
		."</select></td>\n"
		."</tr>\n"
		."</tbody>\n"
		."</table>\n"
		."</form>\n";

	return $output;
}
?>

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

/*  This provides an administrative signin page allowing users to
    add, modify, and delete event entries.  It uses session variables
    so cookies must be enables (this is untrue PHP can use get/post headers
    for session info) 
*/ 

function login(){
	global $HTTP_POST_VARS, $calno, $HTTP_GET_VARS, $day, $month, $year,
	$user;

	$output = '';
	$calendarDB = connect_to_database();

	$colname_rsAuthenticate = "1";
	if (isset($HTTP_POST_VARS['username'])) {
		$colname_rsAuthenticate = (get_magic_quotes_gpc()) ? $HTTP_POST_VARS['username'] : addslashes($HTTP_POST_VARS['username']);
	}

	//Check password and username
	if (isset($HTTP_POST_VARS['submit'])){
		//mysql_select_db($database_calendarDB, $calendarDB);
		$query_rsAuthenticate = "SELECT * FROM ".SQL_PREFIX."admin WHERE UID = '$HTTP_POST_VARS[username]' AND password = PASSWORD('$HTTP_POST_VARS[password]') AND calno = $calno";
		$rsAuthenticate = mysql_query($query_rsAuthenticate, $calendarDB) or die(mysql_error());
		$row_rsAuthenticate = mysql_fetch_assoc($rsAuthenticate);
		$totalRows_rsAuthenticate = mysql_num_rows($rsAuthenticate);

		if($totalRows_rsAuthenticate > 0){
			$user = 1;
			session_register('user');
			header("Location: index.php?day=$day&month=$month&year=$year");
			$output .= '<h2>loggin in...</h2>';
			return $output;
		}

		$output .= '<h2>Sorry, Invalid Login</h2>';

	}

	return $output . login_form();
}


function login_form(){
	global $HTTP_GET_VARS, $day, $month, $year;

	$output = "<form action=\"index.php\" method=\"post\">\n"
		."<table class=\"phpc-main\">\n"
		."<caption>"._('Log in')."</caption>\n"
		."<tfoot>\n"
		."<tr>\n"
		."<td colspan=\"2\">\n"
		."<input type=\"hidden\" name=\"login\" value=\"1\" />\n"
		.'<input type="submit" name="submit" value="'._('Submit')
		."\" />\n"
		."</td>\n"
		."</tr>\n"
		."</tfoot>\n"
		."<tbody>\n"
		."<tr>\n"
		."<th>"._('Username').":</th>\n"
		."<td><input name=\"username\" type=\"text\" /></td>\n"
		."</tr>\n"
		."<tr>\n"
		."<th>"._('Password').":</th>\n"
		."<td><input name=\"password\" type=\"password\" /></td>\n"
		."</tr>\n"
		."</tbody>\n"
		."</table>\n"
		."</form>\n";

	return $output;
}
?>


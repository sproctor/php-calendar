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

/*
   connect to the database and create the global $db
   config.php must be included before this file
*/

if ( !defined('IN_PHPC') ) {
	die("Hacking attempt");
}

require_once('adodb.inc.php');

if ( !defined('SQL_TYPE') ) {
        die("Error loading DB");
}

// Make the database connection.
$db = NewADOConnection(SQL_TYPE);
if(!$db->Connect(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE)) {
	db_error(_("Could not connect to the database"));
}
?>

<?php
/***************************************************************************
 *                                 db.php
 *                            -------------------
 *   begin                : Saturday, Feb 13, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id$
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

if ( !defined('IN_PHPC') ) {
	die("Hacking attempt");
}

include_once('adodb/adodb.inc.php');

// Make the database connection.
$db = NewADOConnection(SQL_TYPE);
if(!$db->Connect(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE)) {
	db_error(_("Could not connect to the database"));
}
?>

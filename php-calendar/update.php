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

include 'miniconfig.inc.php';
include "$basedir/calendar.inc.php";

    $database = connect_to_database();

    $query = "ALTER TABLE $mysql_tablename 
  ADD duration datetime AFTER stamp,
  ADD eventtype int(4) AFTER duration;";
    $result = mysql_query($query)
        or die(mysql_error());

    $query = "UPDATE $mysql_tablename
  SET duration=stamp, eventtype=1;";
    $result = mysql_query($query)
        or die(mysql_error());

?>

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

include_once("calendar.inc");
include_once("config.inc");
include_once("index.inc");

echo top();

$currentday = date("j");
$currentmonth = date("n");
$currentyear = date("Y");

if (!isset($_GET['month'])) {
    $month = $currentmonth;
} else {
  $month = $_GET['month'];
}

if(!isset($_GET['year'])) {
    $year = $currentyear;
} else {
    $year = date("Y", mktime(0,0,0,$month,1,$_GET['year']));
}

if(!isset($_GET['day'])) {
    if($month == $currentmonth) $day = $currentday;
    else $day = 1;
} else {
    $day = ($_GET['day'] - 1) % date("t", mktime(0,0,0,$month,1,$year)) + 1;
}

while($month < 1) $month += 12;
$month = ($month - 1) % 12 + 1;

echo navbar($year, $month, $day);

echo calendar($year, $month, $day);

echo bottom();
?>

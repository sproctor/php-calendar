<?php
include_once("calendar.inc");
include_once("config.inc");
include_once("index.inc");

top();

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

$database = connect_to_database();

navbar($year, $month, $day);

calendar($year, $month, $day, $database, $mysql_tablename);

bottom();
?>

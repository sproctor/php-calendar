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
header('Content-Type: text/css');
if(isset($HTTP_GET_VARS['bgcolor1'])) {
	$bgcolor1 = $HTTP_GET_VARS['bgcolor'];
} else {
	$bgcolor1 = BG_COLOR1;
}
/* you get the idea, eventually the colors should be pickable by a user,
   but we need a real concept of users first
 */
$bgcolor2 = BG_COLOR2;
$bgcolor3 = BG_COLOR3;
$bgcolor4 = BG_COLOR4;
$bgpast = BG_PAST;
$bgfuture = BG_FUTURE;
$sepcolor = SEPCOLOR;
$textcolor1 = TEXTCOLOR1;
$textcolor2 = TEXTCOLOR2;
?>
body {
  font-family: "Times New Roman", serif;
  margin: 0 2%;
  padding: 0;
  background-color: <?php echo $bgcolor1 ?>;
  color: <?php echo $textcolor1 ?>;
}

a {
  color: <?php echo $textcolor1 ?>;
   background-color: inherit;
}

a:hover {
  color: <?php echo $bgcolor2 ?>;
  background-color: inherit;
}

h1 {
  font-size: 200%;
  text-align: center;
  font-family: sans-serif;
  color: <?php echo $textcolor1 ?>;
  background-color: inherit;
}

input[type="submit"] {
  background-color: <?php echo $bgcolor3 ?>;
  color: <?php echo $textcolor1 ?>;
  border: 1px solid <?php echo $sepcolor ?>;
}

.phpc-navbar a {
  background-color: <?php echo $bgcolor3 ?>;
  color: <?php echo $textcolor1 ?>;
  border: 1px solid <?php echo $sepcolor ?>;
}

input[type="submit"]:hover {
  background-color: <?php echo $textcolor1 ?>;
  color: <?php echo $bgcolor3 ?>;
}

.phpc-navbar a:hover {
  background-color: <?php echo $textcolor1 ?>;
  color: <?php echo $bgcolor3 ?>;
}

.phpc-navbar {
  margin: 1em 0;
  text-align: center;
}

.phpc-navbar a {
  font-size: 90%;
  text-decoration: none;
  margin: 0;
  padding: 2px;
}

.phpc-main {
  width: 100%;
  font-size: 90%;
  font-weight: bold;
  border-style: solid;
  border-collapse: collapse;
  border-color: <?php echo $sepcolor ?>;
  border-width: 2px;
  color: <?php echo $textcolor1 ?>;
  background-color: inherit;
}

caption {
  font-size: 175%;
  color: <?php echo $textcolor1 ?>;
  background-color: inherit;
  padding: 2px;
  font-weight: bolder;
}

th {
  background-color: <?php echo $bgcolor3 ?>;
  color: <?php echo $textcolor1 ?>;
}

table tr th {
  text-align: left;
}

thead, tfoot {
  text-align: center;
}

.phpc-main td, .phpc-main th {
  border-style: solid;
  border-collapse: collapse;
  border-color: <?php echo $sepcolor ?>;
  border-width: 2px;
}

.phpc-main td {
  background-color: <?php echo $bgcolor1 ?>;
  color: inherit;
}

#calendar {
  table-layout: fixed;
}

#calendar td {
  text-align: left;
  height: 80px;
  overflow: hidden;
}

td.past {
  background-color: <?php echo $bgpast ?>;
  color: inherit;
}

td.future {
  background-color: <?php echo $bgfuture ?>;
  color: inherit;
}

td.none {
  background-color: <?php echo $bgcolor2 ?>;
  color: inherit;
}

.phpc-main ul {
  margin: 2px;
  padding: 0;
  list-style-type: none;
  border-color: <?php echo $sepcolor ?>;
  border-style: solid;
  border-width: 1px 1px 0 1px;
}

.phpc-main li {
  font-size: 80%;
  font-weight: normal;
  padding: 0;
  border-color: <?php echo $sepcolor ?>;
  border-style: solid;
  border-width: 0 0 1px 0;
  margin: 0;
}

.phpc-main li a {
  display: block;
  text-decoration: none;
  padding: 2px;
}

.phpc-main li a:hover {
  background-color: <?php echo $bgcolor2 ?>;
  color: <?php echo $textcolor2 ?>;
}

.phpc-footer {
  text-align: center;
}

.phpc-button {
  text-align: center;
}

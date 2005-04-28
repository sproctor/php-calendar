<?php
/*
   Copyright 2002 - 2005 Sean Proctor, Nathan Poiro

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
   This file is loaded as a style sheet
*/

header('Content-Type: text/css');

$bgcolor1 = BG_COLOR1;
$bgcolor2 = BG_COLOR2;
$bgcolor3 = BG_COLOR3;
$bgcolor4 = BG_COLOR4;
$bgpast = BG_PAST;
$bgfuture = BG_FUTURE;
$bginactive = BG_INACTIVE;
$sepcolor = SEPCOLOR;
$textcolor1 = TEXTCOLOR1;
$textcolor2 = TEXTCOLOR2;

// use user specified values if they exist
if(isset($_GET['bgcolor1'])) $bgcolor1 = $_GET['bgcolor1'];
if(isset($_GET['bgcolor2'])) $bgcolor2 = $_GET['bgcolor2'];
if(isset($_GET['bgcolor3'])) $bgcolor3 = $_GET['bgcolor3'];
if(isset($_GET['bgcolor4'])) $bgcolor4 = $_GET['bgcolor4'];
if(isset($_GET['bgpast'])) $bgpast = $_GET['bgpast'];
if(isset($_GET['bgfuture'])) $bgfuture = $_GET['bgfuture'];
if(isset($_GET['bginactive'])) $bginactive = $_GET['bginactive'];
if(isset($_GET['sepcolor'])) $sepcolor = $_GET['sepcolor'];
if(isset($_GET['textcolor1'])) $textcolor1 = $_GET['textcolor1'];
if(isset($_GET['textcolor2'])) $textcolor2 = $_GET['textcolor2'];

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

h2 {
  font-size: 175%;
  text-align: center;
  font-family: serif;
  color: <?php echo $textcolor1 ?>;
  background-color: inherit;
}

input {
  border: 1px solid <?php echo $sepcolor ?>;
  color: <?php echo $textcolor1 ?>;
}

input[type="submit"] {
  background-color: <?php echo $bgcolor3 ?>;
}

input[type="submit"]:hover {
  background-color: <?php echo $textcolor1 ?>;
  color: <?php echo $bgcolor3 ?>;
}

.phpc-navbar a {
  background-color: <?php echo $bgcolor3 ?>;
  color: <?php echo $textcolor1 ?>;
  border: 1px solid <?php echo $sepcolor ?>;
}

.phpc-navbar a:hover {
  background-color: <?php echo $textcolor1 ?>;
  color: <?php echo $bgcolor3 ?>;
}

.phpc-navbar {
  margin: 1em 0 2em 0;
  text-align: center;
}

.phpc-navbar a {
  font-size: 90%;
  text-decoration: none;
  margin: 0;
  padding: 2px;
}

.phpc-main {
  font-size: 90%;
  border-style: solid;
  border-collapse: collapse;
  border-color: <?php echo $sepcolor ?>;
  border-width: 2px;
  color: <?php echo $textcolor1 ?>;
  background-color: <?php echo $bgcolor4 ?>;
}

table.phpc-main {
  width: 100%;
}

.phpc-main h2 {
  margin: 0;
  text-align: left;
  background-color: <?php echo $bgcolor3 ?>;
  padding: .25em; 
  border-color: <?php echo $sepcolor ?>;
  border-style: solid;
  border-width: 0 0 2px 0;
}

.phpc-main div {
  margin: .5em;
  font-weight: bold;
}

.phpc-main p {
  border-style: solid;
  border-width: 2px 0 0 0;
  border-color: <?php echo $sepcolor ?>;
  padding: .5em;
  margin: 0;
  text-align: justify;
}

caption {
  font-size: 175%;
  color: <?php echo $textcolor1 ?>;
  background-color: <?php echo $bgcolor1 ?>;
  padding: 2px;
  font-weight: bolder;
}

thead th {
  background-color: <?php echo $bgcolor3 ?>;
  color: <?php echo $textcolor1 ?>;
}

thead {
  border: 1px solid <?php echo $sepcolor ?>;
}

thead, tfoot {
  text-align: center;
}

#calendar td, #calendar th {
  border-style: solid;
  border-collapse: collapse;
  border-color: <?php echo $sepcolor ?>;
  border-width: 2px;
  padding: .5em;
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

table.phpc-main ul {
  margin: 2px;
  padding: 0;
  list-style-type: none;
  border-color: <?php echo $sepcolor ?>;
  border-style: solid;
  border-width: 1px 1px 0 1px;
}

table.phpc-main li {
  font-size: 80%;
  font-weight: normal;
  padding: 0;
  border-color: <?php echo $sepcolor ?>;
  border-style: solid;
  border-width: 0 0 1px 0;
  margin: 0;
}

table.phpc-main li a {
  display: block;
  text-decoration: none;
  padding: 2px;
}

table.phpc-main li a:hover {
  background-color: <?php echo $bgcolor2 ?>;
  color: <?php echo $textcolor2 ?>;
}

.phpc-list {
  border: 1px solid <?php echo $sepcolor ?>;
}

.phpc-footer {
  text-align: center;
}

.phpc-button {
  text-align: center;
}

.phpc-add {
  float: right;
  text-align: right;
}

textarea.phpc-description {
  width: 100%;
  border: 0;
}

.phpc-general-info, .phpc-time-info, .phpc-date-info {
  border: 2px solid <?php echo $sepcolor ?>;
  margin: 2em;
  background: <?php echo $bgcolor3 ?>;
  padding: 1em;
}

.phpc-general-info div, .phpc-time-info div, .phpc-date-info div {
}

.phpc-general-info h3, .phpc-time-info h3, .phpc-date-info h3 {
  border-width: 0 0 2px 0;
  border-style: solid;
  border-color: <?php echo $sepcolor ?>;
}

.phpc-general-info h4, .phpc-time-info h4, .phpc-date-info h4 {
  margin: 1em 0 0 0;
}

.phpc-misc-info {
  text-align: center;
}

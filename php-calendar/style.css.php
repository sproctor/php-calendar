<?php
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
  margin: 8px 2%;
  padding: 0;
  background-color: <?php echo $bgcolor1 ?>;
  color: <?php echo $textcolor1 ?>;
}

a {
color: <?php echo $textcolor1 ?>;
}

a:hover {
color: <?php echo $bgcolor2 ?>;
}

h1 {
  text-align: center;
  font-family: sans-serif;
  padding: 4px 0;
  border: 1px solid <?php echo $sepcolor ?>;
  background-color: <?php echo $bgcolor2 ?>;
  color: <?php echo $textcolor2 ?>;
  margin: 9px 0;
}

input[type="submit"], .phpc-navbar a {
  background-color: <?php echo $bgcolor3 ?>;
  color: <?php echo $textcolor1 ?>;
  border: 1px solid <?php echo $sepcolor ?>;
}

input[type="submit"]:hover, .phpc-navbar a:hover {
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

a.month {
  width: 300px;
}

.phpc-main {
  width: 100%;
  font-size: 90%;
  font-weight: bold;
  table-layout: fixed;
  border-style: none;
  background-color: <?php echo $sepcolor ?>;
  color: <?php echo $textcolor1 ?>;
}

caption {
  font-size: 140%;
  border-width: 2px 2px 0 2px;
  border-style: solid;
  background-color: <?php echo $bgcolor2 ?>;
  color: <?php echo $textcolor1 ?>;
  padding: 2px;
  font-weight: bolder;
}

th {
  background-color: <?php echo $bgcolor2 ?>;
  color: <?php echo $textcolor1 ?>;
}

td {
  background-color: <?php echo $bgcolor3 ?>;
}

tfoot {
  text-align: center;
}

.past, .future {
  text-align: left;
  height: 80px;
}

.past, .past td {
  background-color: <?php echo $bgpast ?>;
}

.future, .future td {
  background-color: <?php echo $bgfuture ?>;
}

.none {
  background-color: <?php echo $bgcolor2 ?>;
}

.phpc-main td {
  overflow: hidden;
}

.phpc-main table {
  width: 100%;
  border-spacing: 1px;
  background-color: <?php echo $sepcolor ?>;
}

.phpc-main table td {
  font-size: 80%;
  font-weight: normal;
  padding: 0;
}

.phpc-main table a {
  display: block;
  text-decoration: none;
  padding: 2px;
}

.phpc-main table a:hover {
  background-color: <?php echo $bgcolor2 ?>;
  color: <?php echo $textcolor2 ?>;
}

.phpc-footer {
  text-align: center;
}

.phpc-button {
  text-align: center;
}

.event-time {
  padding: 0 2px;
}

.description {
  text-align: justify;
}

b {
  font-size: 140%;
}

img {
  border-style: none;
}

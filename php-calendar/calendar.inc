<?php
include_once("config.inc");

// Browser
if(eregi("(opera)([0-9]{1,2}.[0-9]{1,3}){0,1}",$HTTP_USER_AGENT,$match) || 
   eregi("(opera/)([0-9]{1,2}.[0-9]{1,3}){0,1}",$HTTP_USER_AGENT,$match)) {
  $BName = "Opera"; $BVersion=$match[2];
} elseif(eregi("(konqueror)/([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match)) {
  $BName = "Konqueror"; $BVersion=$match[2];
} elseif(eregi("(lynx)/([0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2})",$HTTP_USER_AGENT,$match)) {
  $BName = "Lynx"; $BVersion=$match[2];
} elseif(eregi("(links)\(([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match)) {
  $BName = "Links"; $BVersion=$match[2];
} elseif(eregi("(msie) ?([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match)) {
  $BName = "MSIE"; $BVersion=$match[2];
} elseif(eregi("(netscape6)/(6.[0-9]{1,3})",$HTTP_USER_AGENT,$match)) {
  $BName = "Netscape"; $BVersion=$match[2];
} elseif(eregi("(mozilla)/([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match)) {
  $BName = "Netscape"; $BVersion=$match[2];
} elseif(eregi("w3m",$HTTP_USER_AGENT)) {
  $BName = "w3m"; $BVersion="Unknown";
} else {
  $BName = "Unknown"; $BVersion="Unknown";
}

function ifold($str1, $str2)
{
  if(isold()) return $str1;
  return $str2;
}

function isold()
{
  global $BName, $BVersion;

  if(($BName == "Netscape" || $BName == "MSIE") && $BVersion < 5) return true;
  else return false;
}

function doctype()
{
  if(!isold()) { echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"
        \"http://www.w3.org/TR/html4/strict.dtd\">";
  } else { echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
        \"http://www.w3.org/TR/html4/loose.dtd\">";
  }
}

function print_header()
{
  global $title;
  echo"<head>
  <title>$title</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
";
  print_style();
  echo "</head>";
}

function top()
{
  global $bordercolor, $headercolor, $header;
  doctype();
  echo "<html>\n";
  print_header();
  echo "<body>";
  if(isold()) { 
    echo <<<END
<table width="96%" cellspacing="0" cellpadding="0" border="0">
  <tr>
    <td bgcolor="$bordercolor">
  <table width="100%" cellspacing="1" cellpadding="2" border="0">
    <tr>
      <td bgcolor="$headerbgcolor">
        <center><b>$header</b></center>
      </td>
    </tr>
  </table>
    </td>
  </tr>
</table>
<br>
END;
  } else {
    echo "<h1 class=\"calendar\">$header</h1>\n"; 
  }
}

function print_footer()
{
  echo "<p>
  <a href=\"http://validator.w3.org/check/referer\">
    <img src=\"valid-html401.png\" alt=\"[HTML 4.01]\"";
  if(isold()) echo " border=0";
  echo ">
  </a>
  <a href=\"http://jigsaw.w3.org/css-validator/check/referer\">
    <img src=\"valid-css.png\" alt=\"[CSS2]\"";
  if(isold()) echo " border=0";
  echo ">
  </a>
</p>";
}

function bottom()
{
  print_footer();
  echo "\n</body>
</html>";
}

function print_style()
{
  global $title, $header, $bgcolor, $textcolor, $headercolor, $tablecolor,
    $futurecolor, $pastcolor, $nonecolor, $bordercolor, $BName, $BVersion;
  echo <<<END
<style type="text/css">
/* Your browser: $BName $BVersion */
body {
  color: $textcolor;
  background-color: $bgcolor;
  text-align: center;
  font-size: 12pt;
  font-family: "Times New Roman", serif, sans-serif;
  margin: 8px 2%;
  padding: 0;
}

img {
  border-style: none;
}

.calendar a, .display a {
  color: $textcolor;
  background-color: inherit;
}

.calendar a:hover, .display a:hover {
  color: $bgcolor;
  background-color: inherit;
}

h1.calendar {
  background-color: $headerbgcolor;
  color: $headercolor;
  text-align: center;
  font-family: sans-serif;
  padding: 4px 0;
  border: 1px solid $bordercolor;
  margin: 9px 0;
}

table.nav {
  width: 100%;
  font-size: 10pt;
  text-align: center;
  background-color: $bordercolor;
  margin: 8px 0;
  border-style: none;
  padding: 0px;
  border-spacing: 1px;
  table-layout: fixed;
  color: $headercolor;
}

table.nav th {
  background-color: $headerbgcolor;
  color: $headercolor;
  font-family: serif;
  font-size: 16pt;
  font-weight: bold;
  text-align: center;
}

table.nav td {
  padding: 0;
}

table.nav a {
  display: block;
  background-color: $tablebgcolor;
  color: $headercolor;
  text-decoration: none;
  padding: 2px 0;
END;
if($BName == "MSIE") {
    echo "  width: 100%;";
}
echo <<<END
}

table.nav a:hover {
  color: $tablebgcolor;
  background-color: $headercolor;
}

table.calendar {
  width: 100%;
  text-align: center;
  font-size: 10pt;
  font-weight: bold;
END;
if($BName != "MSIE" || $BVersion >= 6) {
    echo "  table-layout: fixed;"; 
}
echo <<<END
  background-color: $bordercolor;
  color: $headercolor;
  border-style: none;
}

table.calendar th {
  color: $textcolor;
  background-color: $headerbgcolor;
  font-size: 12pt;
}

table.calendar td {
  background-color: $tablebgcolor;
  color: inherit;
}

table.calendar td.past {
  color: inherit;
  background-color: $pastcolor;
  text-align: left;
  height: 80px;
}

table.calendar td.future {
  color: inherit;
  background-color: $futurecolor;
  text-align: left;
  height: 80px;
}

table.calendar td.none {
  background-color: $nonecolor;
  color: inherit;
}

table.future, table.past {
END;
if(!isold()) {
  echo "padding: 0;
  margin: 2px 0 0 0;";
}
echo <<<END
  width: 100%;
  background-color: $bordercolor;
  color: inherit;
  border-spacing: 1px;
}

table.future td, table.past td {
  font-size: 10pt;
  font-weight: normal;
  padding: 0;
  width: 100%;
}

table.future a, table.past a {
  display: block;
  text-decoration: none;
  padding: 2px;
END;
if($BName == "MSIE" && $BVersion < 6) {
    echo "  width: 100%;";
}
echo <<<END
}

table.past a:hover {
  color: $bgcolor;
  background-color: $futurecolor;
}

table.future a:hover {
  color: $bgcolor;
  background-color: $pastcolor;
}

table.past td {
  background-color: $pastcolor;
  color: inherit;
}

table.future td {
  background-color: $futurecolor;
  color: inherit;
}

table.display {
  width: 100%;
  text-align: center;
  font-size: 10pt;
  background-color: $bordercolor;
  color: $textcolor;
  border-style: none;
  border-spacing: 2px;
  padding: 0;
  margin: 0;
}

table.display th {
  font-weight: bold;
  font-size: 12pt;
  color: $headercolor;
  background-color: inherit;
}

table.display td {
  background-color: $tablebgcolor;
  color: inherit;
}

.description {
  text-align: justify;
}

.box {
  text-align: left;
  font-size: 10pt;
  background-color: $tablebgcolor;
  border-color: $bordercolor;
  border-style: solid;
  border-width: 1px;
  color: $textcolor;  
}

table.box {
  width: 100%;
  border-spacing: 0;
}

table.box td {
  padding: 4px;
}

table.box td:first-child {
  text-align: right;
}

table.box th {
  text-align: center;
  background-color: $headerbgcolor;
  color: $headercolor;
  margin: 0;
  font-size: 12pt;
}

div.box, a.box {
  width: 96px;
  text-align: center;
  margin: 8px auto;
  font-size: 12pt;
  padding: 4px;
}

a.box {
  text-decoration: none;
  display: block;
}

a.box:hover {
  background-color: $bordercolor;
  color: $tablebgcolor;
}

.display b {
  font-size: 16pt;
}
</style>
END;
}

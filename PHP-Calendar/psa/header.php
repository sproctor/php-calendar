<? if(!isold()) { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<? } else { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<? } ?><html lang="en">
<head>
<title><? echo "$title" ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?
global $HTTP_USER_AGENT, $BName, $BVersion, $BPlatform;

// Browser
if(eregi("(opera)([0-9]{1,2}.[0-9]{1,3}){0,1}",$HTTP_USER_AGENT,$match) || 
eregi("(opera/)([0-9]{1,2}.[0-9]{1,3}){0,1}",$HTTP_USER_AGENT,$match))
{
	$BName = "Opera"; $BVersion=$match[2];
}
elseif(eregi("(konqueror)/([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match))
{
	$BName = "Konqueror"; $BVersion=$match[2];
}
elseif(eregi("(lynx)/([0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2})",$HTTP_USER_AGENT,$match))
{
	$BName = "Lynx"; $BVersion=$match[2];
}
elseif(eregi("(links)\(([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match))
{
	$BName = "Links"; $BVersion=$match[2];
}
elseif(eregi("(msie) ?([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match))
{
	$BName = "MSIE"; $BVersion=$match[2];
}
elseif(eregi("(netscape6)/(6.[0-9]{1,3})",$HTTP_USER_AGENT,$match))
{
	$BName = "Netscape"; $BVersion=$match[2];
}
elseif(eregi("(mozilla)/([0-9]{1,2}.[0-9]{1,3})",$HTTP_USER_AGENT,$match))
{
	$BName = "Netscape"; $BVersion=$match[2];
}
elseif(eregi("w3m",$HTTP_USER_AGENT))
{
	$BName = "w3m"; $BVersion="Unknown";
}
else{$BName = "Unknown"; $BVersion="Unknown";}

function ifold($str1, $str2) {
  if(isold()) return $str1;
  return $str2;
}

function isold() {
  global $BName, $BVersion;
  if(($BName == "Netscape" || $BName == "MSIE") && $BVersion < 5) return true;
  else return false;
}
?>
<style type="text/css">
body {
  color: <? echo "$headercolor" ?>;
  background-color: <? echo "$bgcolor" ?>;
  text-align: center;
  font-size: 12pt;
  padding: 0 2%;
}

img {
  border: 0;
}

a {
  color: <? echo "$textcolor" ?>;
}

a:active {
  color: <? echo "$tablebgcolor" ?>;
}

a.plain {
  text-decoration: none;
}

a:hover {
  color: <? echo "$tablebgcolor" ?>;
}

table {
  width: 100%;
}

td {
  vertical-align: top;
  color: <? echo "$textcolor" ?>;
}

.header {
  background-color: <? echo "$headerbgcolor" ?>;
  color: <? echo "$headercolor" ?>;
  font-size: 20pt;
  font-weight: bold;
  text-align: center;
  font-family: sans-serif;
  padding: 4px 0;
  border: 1px solid <? echo "$bordercolor" ?>;
}

.title {
  background-color: <? echo "$headerbgcolor" ?>;
  color: <? echo "$headercolor" ?>;
  font-family: serif;
  font-size: 16pt;
  font-weight: bold;
  text-align: center;
}

ul {
  margin: 0;
  padding: 0 0 0 16px;
}

.mod {
  font-family: monospace;
  font-size: 10pt;
}

.blurb {
  margin: 2em 12%;
  font-size: 14pt;
  font-family: serif;
  text-align: justify;
}

table.display {
  padding: 0px;
  background-color: <? echo "$bordercolor" ?>;
  color: inherit;
}

table.display td {
  background-color: <? echo "$tablebgcolor" ?>;
  color: <? echo "$textcolor" ?>;
  text-align: left;
}

table.display td.title {
  text-align: center;
  background-color: <? echo "$headerbgcolor" ?>;
  color: <? echo "$headercolor" ?>;
}

table.calendar {
  text-align: center;
  font-size: 10pt;
  background-color: <? echo "$bordercolor" ?>;
  color: <? echo "$headercolor" ?>
}

table.nav {
  font-size: 10pt;
  text-align: center;
  background-color: <? echo "$bordercolor" ?>;
  margin: 4px 0;
  border-style: none;
  padding: 0px;
  border-spacing: 1px;
  table-layout: fixed;
  color: <? echo "$headercolor" ?>;
}

table.nav td {
  background-color: <? echo "$tablebgcolor" ?>;
  color: inherit;
  padding: 0;
}

table.nav .title {
  background-color: <? echo "$headerbgcolor" ?>;
  color: inherit;
}

table.nav a {
  display: block;
  background-color: <? echo "$tablebgcolor" ?>;
  color: <? echo "$headercolor" ?>;
  text-decoration: none;
  padding: 2px;
<? if($BName == "MSIE") echo "width: 100%;" ?>
}

table.nav a:hover {
  color: <? echo "$tablebgcolor" ?>;
  background-color: <? echo "$headercolor" ?>;
}

table.calendar td {
  font-size: 12pt;
  font-weight: bold;
}

td.past {
  color: inherit;
  background-color: <? echo "$pastcolor" ?>;
  text-align: left;
  height: 80px;
}

td.future {
  color: inherit;
  background-color: <? echo "$futurecolor" ?>;
  text-align: left;
  height: 80px;
}

td.none {
  background-color: <? echo "$nonecolor" ?>;
}

table.list { <? if(!isold()) echo "  padding: 0;
  margin: 2px 0 0 0;\n"; ?>
  background-color: <? echo "$bordercolor" ?>;
  color: inherit;
  border-spacing: 1px;
}

table.list td {
  font-size: 8pt;
  font-weight: normal;
  padding: 0;
}

table.list a {
  display: block;
  text-decoration: none;
  padding: 2px;
<? if($BName == "MSIE") echo "width: 100%;" ?>
}

td.past table.list a:hover {
  color: <? echo "$textcolor" ?>;
  background-color: <? echo "$futurecolor" ?>;
}

td.future table.list a:hover {
  color: <? echo "$textcolor" ?>;
  background-color: <? echo "$pastcolor" ?>;
}

td.past table.list td {
  background-color: <? echo "$pastcolor" ?>;
  color: inherit;
}

td.future table.list td {
  background-color: <? echo "$futurecolor" ?>;
  color: inherit;
}

table.edit {
  background-color: <? echo "$tablebgcolor" ?>;
  color: <? echo "$textcolor" ?>;
  border: 1px solid <? echo "$bordercolor" ?>;
  width: 100%;
}

table.calendar thead td {
  background-color: <? echo "$headerbgcolor" ?>;
  font-weight: bold;
}

table.edit td {
  text-align: left;
}

.bold {
  font-weight: bolder;
  font-size: 16pt;
}

tr.title {
  border-width: 0 0 1px 0;
  border-style: solid;
  border-color: <? echo "$bordercolor" ?>;
}

TT, KBD, PRE { font-family: monospace; }

TH, TD { font-style: normal; }
</style>
</head>
<body>
<? if(isold()) { echo "<table width=\"96%\" cellspacing=0 cellpadding=0 border=0>
<tr><td bgcolor=\"$bordercolor\">
<table width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\">
<tr><td bgcolor=\"$headerbgcolor\" class=\"title\">$header</td></tr>
</table>
</td></tr></table><br>\n"; }
else { echo "<div class=\"header\">$header</div>\n"; } ?>

<?php if(!isold()) { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<?php } else { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<?php } ?><html lang="en">
<head>
<title><?php echo "$title" ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?
global $BName, $BVersion;

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

echo <<<END
<style type="text/css">
/* Your browser: $BName $BVersion */
body {
  color: $headercolor;
  background-color: $bgcolor;
  text-align: center;
  font-size: 12pt;
  font-family: "Times New Roman", serif, sans-serif;
  padding: 0 2%;
}

img {
  border: 0;
}
END;
if(!isold()) {
echo <<<END
a {
  color: $textcolor;
  background-color: inherit;
}

a:active {
  color: $bgcolor;
  background-color: inherit;
}

a.plain {
  text-decoration: none;
}

a:hover {
  color: $bgcolor;
  background-color: inherit;
}

table {
  width: 100%;
}

td {
  vertical-align: top;
  color: $textcolor;
  background-color: inherit;
}

END;
}
echo <<<END
.header {
  background-color: $headerbgcolor;
  color: $headercolor;
  font-size: 20pt;
  font-weight: bold;
  text-align: center;
  font-family: sans-serif;
  padding: 4px 0;
  border: 1px solid $bordercolor;
}

.title {
  background-color: $headerbgcolor;
  color: $headercolor;
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
  margin-top: 4px;
  background-color: $bordercolor;
  color: inherit;
}

table.display td {
  background-color: $tablebgcolor;
  color: $textcolor;
  text-align: left;
}

table.display td.title {
  text-align: center;
  background-color: $headerbgcolor;
  color: $headercolor;
}

table.calendar {
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
}

table.nav {
  font-size: 10pt;
  text-align: center;
  background-color: $bordercolor;
  margin: 4px 0;
  border-style: none;
  padding: 0px;
  border-spacing: 1px;
  table-layout: fixed;
  color: $headercolor;
}

table.nav td {
  background-color: $tablebgcolor;
  color: inherit;
  padding: 0;
}

table.nav .title {
  background-color: $headerbgcolor;
  color: inherit;
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

td.past {
  color: inherit;
  background-color: $pastcolor;
  text-align: left;
  height: 80px;
}

td.future {
  color: inherit;
  background-color: $futurecolor;
  text-align: left;
  height: 80px;
}

td.none {
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
  background-color: $bordercolor;
  color: inherit;
  border-spacing: 1px;
  width: 100%;
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

table.edit {
  background-color: $tablebgcolor;
  color: $textcolor;
  border: 1px solid $bordercolor;
  width: 100%;
}

table.calendar thead td {
  color: $textcolor;
  background-color: $tablebgcolor;
  font-size: 12pt;
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
  border-color: $bordercolor;
}

TT, KBD, PRE { font-family: monospace; }

TH, TD { font-style: normal; }
</style>
</head>
<body>
END;
if(isold()) { 
    echo <<<END
<table width="96%" cellspacing="0" cellpadding="0" border="0">
  <tr>
    <td bgcolor="$bordercolor">
  <table width="100%" cellspacing="1" cellpadding="2" border="0">
    <tr>
      <td bgcolor="$headerbgcolor" class="title">$header</td></tr>
  </table>
    </td>
  </tr>
</table>
<br>
END;
} else {
    echo "<div class=\"header\">$header</div>\n"; 
} 
?>

<?php
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

<?php
include_once("config.inc");

// Browser
if(eregi("(opera)([0-9]{1,2}.[0-9]{1,3}){0,1}",$_SERVER['HTTP_USER_AGENT'],$match) || 
   eregi("(opera/)([0-9]{1,2}.[0-9]{1,3}){0,1}",$_SERVER['HTTP_USER_AGENT'],$match)) {
  $BName = "Opera"; $BVersion=$match[2];
} elseif(eregi("(konqueror)/([0-9]{1,2}.[0-9]{1,3})",$_SERVER['HTTP_USER_AGENT'],$match)) {
  $BName = "Konqueror"; $BVersion=$match[2];
} elseif(eregi("(lynx)/([0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2})",$_SERVER['HTTP_USER_AGENT'],$match)) {
  $BName = "Lynx"; $BVersion=$match[2];
} elseif(eregi("(links)\(([0-9]{1,2}.[0-9]{1,3})",$_SERVER['HTTP_USER_AGENT'],$match)) {
  $BName = "Links"; $BVersion=$match[2];
} elseif(eregi("(msie) ?([0-9]{1,2}.[0-9]{1,3})",$_SERVER['HTTP_USER_AGENT'],$match)) {
  $BName = "MSIE"; $BVersion=$match[2];
} elseif(eregi("(netscape6)/(6.[0-9]{1,3})",$_SERVER['HTTP_USER_AGENT'],$match)) {
  $BName = "Netscape"; $BVersion=$match[2];
} elseif(eregi("(mozilla)/([0-9]{1,2}.[0-9]{1,3})",$_SERVER['HTTP_USER_AGENT'],$match)) {
  $BName = "Netscape"; $BVersion=$match[2];
} elseif(eregi("w3m",$_SERVER['HTTP_USER_AGENT'])) {
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
  echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
        "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">\n
END;
}

function print_header()
{
  global $title;
  echo"<head>
  <title>$title</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />
";
  print_style();
  echo "</head>";
}

function translate()
{
  if(isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
    $lang = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
  } else {
    $lang = "en-us";
  }

  setlocale(LC_ALL, "de_DE");
  bindtextdomain("messages", "./locale");
  textdomain("messages");
}

function top()
{
  global $header;
  translate();
  doctype();
  echo "<html xml:lang=\"en\">\n";
  print_header();
  echo "<body>
  <h1>$header</h1>\n"; 
}

function print_footer()
{
  echo "<p>
  [<a href=\"http://validator.w3.org/check/referer\">Valid XHTML 1.1</a>]
  [<a href=\"http://jigsaw.w3.org/css-validator/check/referer\">Valid CSS2</a>]
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

  global $BName, $BVersion;
  echo <<<END
<!-- Your browser: $BName $BVersion -->
<link rel="stylesheet" type="text/css" href="generic.css" />
<link rel="stylesheet" type="text/css" href="style0.css" />
END;
}

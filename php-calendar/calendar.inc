<?php
include_once("config.inc");

if(empty($translate)) {
  function _($str) { return $str; }
}

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

function connect_to_database()
{
  global $mysql_hostname, $mysql_username, $mysql_password, $mysql_database;

  $database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password)
     or die("couldn't connect to database");
  mysql_select_db($mysql_database, $database)
     or die("Couldn't select database");

  return $database;
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
  if(!empty($translate)) {
    if(isset($_GET['lang'])) {
      $lang = substr($_GET['lang'], 0, 2);
      setcookie('lang', $lang);
    } elseif(isset($_COOKIE['lang'])) {
      $lang = substr($_COOKIE['lang'], 0, 2);
    } elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    } else {
      $lang = 'en';
    }

    switch($lang) {
     case 'de':
      setlocale(LC_ALL, 'de_DE');
      break;
     case 'en':
      setlocale(LC_ALL, 'en_US');
      break;
    }

    bindtextdomain('messages', './locale');
    textdomain('messages');
  }
}

function month_name($month)
{
  $month = ($month - 1) % 12 + 1;
  switch($month) {
    case 1:  return _("January");
    case 2:  return _("February");
    case 3:  return _("March");
    case 4:  return _("April");
    case 5:  return _("May");
    case 6:  return _("June");
    case 7:  return _("July");
    case 8:  return _("August");
    case 9:  return _("September");
    case 10: return _("October");
    case 11: return _("November");
    case 12: return _("December");
  }
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

function lang_link($lang)
{
  $str = "[<a href=\"" . $_SERVER['PHP_SELF'] . '?';
  if(!empty($_SERVER['QUERY_STRING'])) {
    $str .= $_SERVER['QUERY_STRING'] . '&amp;';
  }

  $str .= "lang=$lang\">$lang</a>]";
  return $str;
}

function print_footer()
{
  if(!empty($translate)) {
    echo "
<div>
  " . lang_link('en') . "
  " . lang_link('de') . "
</div>\n";
  }

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

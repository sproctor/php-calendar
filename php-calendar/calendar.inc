<?php
/*
    Copyright 2002 Sean Proctor

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

include("config.inc");

function soft_error($str)
{
  echo "<html><head><title>Error</title></head><body><h1>Software Error</h1><p>$str</p></body></html>";
  exit;
}

//if(empty($translate)) {
//  function _($str) { return $str; }
//}

function browser()
{
  global $HTTP_USER_AGENT, $BName, $BVersion;

  if(eregi('opera/?([0-9]+(\.[0-9]+)*)?', $HTTP_USER_AGENT, $match)) {
    $BName = 'Opera';
    $BVersion = $match[1];
  } elseif(eregi('konqueror/([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
    $BName = "Konqueror";
    $BVersion = $match[1];
  } elseif(eregi('lynx/([0-9]+.[0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
    $BName = 'Lynx';
    $BVersion = $match[1];
  } elseif(eregi('links\(([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
    $BName = 'Links';
    $BVersion = $match[1];
  } elseif(eregi('msie ?([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
    $BName = 'MSIE';
    $BVersion = $match[1];
  } elseif(eregi('(netscape6|mozilla)/([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
    $BName = 'Netscape';
    $BVersion = $match[2];
  } elseif(eregi('w3m', $HTTP_USER_AGENT)) {
    $BName = 'w3m';
    $BVersion = 'Unknown';
  } else {
    $BName = 'Unknown';
    $BVersion = 'Unknown';
  }
}

function ifold($str1, $str2)
{
  if(isold()) return $str1;
  return $str2;
}

function isold()
{
  global $BName, $BVersion;

  if(($BName == 'Netscape' || $BName == 'MSIE') && $BVersion < 5) return true;
  else return false;
}

function connect_to_database()
{
  global $sql_hostname, $sql_username, $sql_password, $sql_database;

  $database = mysql_connect($sql_hostname, $sql_username, $sql_password)
     or die("couldn't connect to database");
  mysql_select_db($sql_database, $database)
     or die("Couldn't select database");

  return $database;
}

function translate()
{
  global $translate, $HTTP_ACCEPT_LANGUAGE, $HTTP_GET_VARS, $HTTP_COOKIE_VARS;

  if(!empty($translate)) {
    if(isset($HTTP_GET_VARS['lang'])) {
      $lang = substr($HTTP_GET_VARS['lang'], 0, 2);
      setcookie('lang', $lang);
    } elseif(isset($HTTP_COOKIE_VARS['lang'])) {
      $lang = substr($HTTP_COOKIE_VARS['lang'], 0, 2);
    } elseif(isset($HTTP_ACCEPT_LANGUAGE)) {
      $lang = substr($HTTP_ACCEPT_LANGUAGE, 0, 2);
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
  global $header, $title;
  translate();
  browser();
  $output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
        "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xml:lang="en">
<head>
  <title>' . $title . '</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
';

// FIXME: add dynamic style stuff below

  $output .= "<!-- Your browser: $BName $BVersion -->";
  $output .= '<link rel="stylesheet" type="text/css" href="generic.css" />
<link rel="stylesheet" type="text/css" href="style0.css" />
';

  $output .= '</head>
<body>
  <h1>' . $header . '</h1>
';
  return $output;
}

function lang_link($lang)
{
  global $PHP_SELF, $QUERY_STRING;

  $str = '[<a href="' . $PHP_SELF . '?';
  if(!empty($QUERY_STRING)) {
    $str .= $QUERY_STRING . '&amp;';
  }

  $str .= "lang=$lang\">$lang</a>]";
  return $str;
}

function print_footer()
{
  global $translate, $SERVER_NAME, $SCRIPT_NAME, $QUERY_STRING;
  $output = '';

  if(!empty($translate)) {
    $output .= "
<div>
  " . lang_link('en') . "
  " . lang_link('de') . "
</div>\n";
  }

  return $output . "<p>
  [<a href=\"http://validator.w3.org/check?url=http://$SERVER_NAME$SCRIPT_NAME?$QUERY_STRING\">
    Valid XHTML 1.1</a>]
  [<a href=\"http://jigsaw.w3.org/css-validator/check/referer\">Valid CSS2</a>]
</p>\n";
}

function bottom()
{
  return print_footer() . "</body>
</html>";
}

function print_style()
{

  global $BName, $BVersion;
}

function back_to_calendar()
{
  global $HTTP_GET_VARS;

  if(!isset($HTTP_GET_VARS['day'])) $day = date("j");
  else $day = $HTTP_GET_VARS['day'];

  if(!isset($HTTP_GET_VARS['month'])) $month = date("n");
  else $month = $HTTP_GET_VARS['month'];

  if(!isset($HTTP_GET_VARS['year'])) $year = date("Y");
  else $year = $HTTP_GET_VARS['year'];

  return "<div>
  <a class=\"box\" href=\"display.php?month=$month&amp;year=$year&amp;day=$day\">" .
_("View date")
 . "</a>
  <a class=\"box\" href=\"index.php?month=$month&amp;year=$year\">"
  . _("Back to Calendar")
 . "</a>
</div>";
}
?>

<?php
include_once("config.php");
include_once("func.php");
if(!isold()) { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<?php } else { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<?php } ?><html lang="en">
<head>
<title><?php echo "$title" ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?
include("style.php");
echo "
</head>
<body>";
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
    echo "<h1>$header</h1>\n"; 
} 
?>

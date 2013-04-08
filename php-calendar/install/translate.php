<?php
require("php-mo.php");
$locale_dir = "../locale";

$handle = opendir($locale_dir);

if(!$handle) {
	echo "Error reading locale directory.";
	exit;
}

while(($filename = readdir($handle)) !== false) {
	$pathname = "$locale_dir/$filename";
	if(strncmp($filename, ".", 1) == 0 || !is_dir($pathname))
		continue;
	$msgs_path = "$pathname/LC_MESSAGES";
	phpmo_convert("$msgs_path/messages.po", "$msgs_path/messages.mo", true);
	echo "Translating \"$filename\" using phpmo\n";
}

closedir($handle);
?>

<?php

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
	$cmd = "msgfmt -o$msgs_path/messages.mo $msgs_path/messages.po";
	echo "Translating \"$filename\" using command \"$cmd\"\n";
	passthru($cmd);
}

closedir($handle);

?>

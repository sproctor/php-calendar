<?php
require("msgfmt-functions.php");
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

	$hash=	parse_po_file("$msgs_path/messages.po");
		if ($hash === FALSE) {
			print(nl2br("Error reading '$msgs_path/messages.po', aborted.\n"));
		}
		else {
		$out="$msgs_path/messages.mo";
			write_mo_file($hash, $out);
		}
			echo nl2br("Translating \"$filename\" using msgfmt\n");
}

closedir($handle);
?>

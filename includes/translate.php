<?php
/*
 * Copyright 2013 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

require_once("$phpc_includes_path/msgfmt-functions.php");

function translate() {
	global $phpc_locale_path;

	if(!is_admin()) {
		permission_error(__('Need to be admin'));
		exit;
	}

	$handle = opendir($phpc_locale_path);

	if(!$handle) {
		return soft_error("Error reading locale directory.");
	}

	$output_tag = tag('div', tag('h2', __('Translate')));
	while(($filename = readdir($handle)) !== false) {
		$pathname = "$phpc_locale_path/$filename";
		if(strncmp($filename, ".", 1) == 0 || !is_dir($pathname))
			continue;
		$msgs_path = "$pathname/LC_MESSAGES";

		$hash=	parse_po_file("$msgs_path/messages.po");
		if ($hash === FALSE) {
			print(nl2br("Error reading '$msgs_path/messages.po', aborted.\n"));
		} else {
			$out="$msgs_path/messages.mo";
			write_mo_file($hash, $out);
		}
		$output_tag->add(tag('div', sprintf(__('Translated "%s"'),
					$filename)));
	}

	closedir($handle);

	return $output_tag;
}
?>

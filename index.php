<?php
/*
 * Copyright 2016 Sean Proctor
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

namespace PhpCalendar;

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

define('PHPC_CONFIG_FILE', __DIR__ . '/config.yml');

define('PHPC_DEBUG', 1);

//if(!defined('PHPC_HOME_URL'))
//	define('PHPC_HOME_URL', PHPC_PROTOCOL . '://' . PHPC_SERVER . PHPC_SCRIPT);
//$url = PHPC_HOME_URL . (empty($_SERVER['QUERY_STRING']) ? ''
//		: '?' . $_SERVER['QUERY_STRING']);

require_once __DIR__ . '/src/helpers.php';


try {
	$context = new Context();


$min = defined('PHPC_DEBUG') ? '' : '.min';

$theme = $context->getCalendar()->theme;
if(empty($theme))
	$theme = 'smoothness';
$jquery_version = "1.12.2";
$jqueryui_version = "1.11.4";
$fa_version = "4.5.0";

if(!isset($jqui_path))
	$jqui_path = "//ajax.googleapis.com/ajax/libs/jqueryui/$jqueryui_version";
if(!isset($fa_path))
	$fa_path = "//maxcdn.bootstrapcdn.com/font-awesome/$fa_version";
if(!isset($jq_file))
	$jq_file = "//ajax.googleapis.com/ajax/libs/jquery/$jquery_version/jquery$min.js";

if($context->getLang() != 'en') {
	$translator = new Translator($context->getLang(), new MessageSelector());
	$translator->addLoader('mo', new MoFileLoader());
	$translator->addResource('mo', __DIR__ . "locale/" . $context->getLang() . "/LC_MESSAGES/messages.mo",
		$context->getLang());
}

if (isset($_REQUEST["content"]) && $_REQUEST["content"] == "json") {
	header("Content-Type: application/json; charset=UTF-8");
	echo display_phpc($context)->toString();
} else {
	header("Content-Type: text/html; charset=UTF-8");

	// This sets global variables that determine the title in the header
	$content = display_phpc($context);
	$embed_script = '';
	if(isset($_REQUEST["content"]) && $_REQUEST["content"] == "embed") {
		$underscore_version = "1.5.2";
		$embed_script = array(tag("script",
					new AttributeList('src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/'
						."$underscore_version/underscore-min.js\""), ''),
				tag('script', new AttributeList('src="static/embed.js"'), ''));
	}

	$html = tag('html', new AttributeList("lang=\"" . $context->getLang() . "\""),
			tag('head',
				tag('title', $context->getCalendar()->get_title()),
				tag('link', new AttributeList('rel="icon"',
						'href="static/office-calendar.png"')),
				tag('meta', new AttributeList('http-equiv="Content-Type"',
						'content="text/html; charset=UTF-8"')),
				tag('link', new AttributeList('rel="stylesheet"', 'href="static/phpc.css"')),
				tag('link', new AttributeList('rel="stylesheet"', "href=\"$jqui_path/themes/$theme/jquery-ui$min.css\"")),
				tag('link', new AttributeList('rel="stylesheet"', 'href="static/jquery-ui-timepicker.css"')),
				tag('link', new AttributeList('rel="stylesheet"', "href=\"$fa_path/css/font-awesome$min.css\"")),
				tag("script", new AttributeList("src=\"$jq_file\""), ''),
				tag("script", new AttributeList("src=\"$jqui_path/jquery-ui$min.js\""), ''),
				tag('script', new AttributeList('src="static/phpc.js"'), ''),
				tag("script", new AttributeList('src="static/jquery.ui.timepicker.js"'), ''),
				tag("script", new AttributeList('src="static/farbtastic.min.js"'), ''),
				tag('link', new AttributeList('rel="stylesheet"', 'href="static/farbtastic.css"'))
			),
			tag('body', $embed_script, $content));

	echo "<!DOCTYPE html>\n", $html->toString();
}
} catch(\Exception $e) {
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!DOCTYPE html>\n";
	echo display_exception($e)->toString();
	exit;
}
?>

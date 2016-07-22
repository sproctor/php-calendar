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
require_once __DIR__ . '/src/helpers.php';

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

define('PHPC_CONFIG_FILE', __DIR__ . '/config.php');

define('PHPC_DEBUG', 1);

//if(!defined('PHPC_HOME_URL'))
//	define('PHPC_HOME_URL', PHPC_PROTOCOL . '://' . PHPC_SERVER . PHPC_SCRIPT);
//$url = PHPC_HOME_URL . (empty($_SERVER['QUERY_STRING']) ? ''
//		: '?' . $_SERVER['QUERY_STRING']);

try {
	$context = new Context();


	$min = defined ( 'PHPC_DEBUG' ) ? '' : '.min';
	
	$jquery_version = "1.12.2";
	$jqueryui_version = "1.11.4";
	$fa_version = "4.5.0";
	
	if (! isset ( $jqui_path ))
		$jqui_path = "//ajax.googleapis.com/ajax/libs/jqueryui/$jqueryui_version";
	if (! isset ( $fa_path ))
		$fa_path = "//maxcdn.bootstrapcdn.com/font-awesome/$fa_version";
	if (! isset ( $jq_file ))
		$jq_file = "//ajax.googleapis.com/ajax/libs/jquery/$jquery_version/jquery$min.js";
	
	if ($context->getLang () != 'en') {
		$translator = new Translator ( $context->getLang (), new MessageSelector () );
		$translator->addLoader ( 'mo', new MoFileLoader () );
		$translator->addResource ( 'mo', __DIR__ . "locale/" . $context->getLang () . "/LC_MESSAGES/messages.mo", $context->getLang () );
	}
	
	if (isset ( $_REQUEST ["content"] ) && $_REQUEST ["content"] == "json") {
		header ( "Content-Type: application/json; charset=UTF-8" );
		echo display_phpc ( $context )->toString ();
	} else {
		header ( "Content-Type: text/html; charset=UTF-8" );
		
		echo get_page($context->getAction())->display( $context, array(
				'context' => $context,
				'calendar' => $context->getCalendar(),
				'user' => $context->getUser(),
				'script' => $context->script,
				'embed' => isset ( $_REQUEST ["content"] ) && $_REQUEST ["content"] == "embed",
				'lang' => $context->getLang (),
				'title' => $context->getCalendar ()->get_title (),
				'theme' => $context->getCalendar ()->get_theme (),
				'min' => $min,
				'query_string' => $_SERVER ['QUERY_STRING'] 
		) );
	}
} catch(PermissionException $e) {
	$msg = __ ( 'You do not have permission to do that: ' ) . $e->getMessage ();
	if ($context->getUser ()->is_user ())
		echo error_message_redirect ( $context, $msg, $context->script );
	else
		echo error_message_redirect ( $context, $msg, "{$context->script}?action=login");
} catch(InvalidInputException $e) {
	echo error_message_redirect($context, $e->getMessage(), $e->target);
} catch(\Exception $e) {
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!DOCTYPE html>\n";
	echo display_exception($e)->toString();
	exit;
}
?>

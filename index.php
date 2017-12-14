<?php declare(strict_types=1);
/*
 * Copyright 2017 Sean Proctor
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

require_once 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

define('PHPC_CONFIG_FILE', __DIR__ . '/config.php');

define('PHPC_DEBUG', 1);
error_reporting(-1);
ini_set('display_errors', '1');

$request = Request::createFromGlobals();

try {
	$context = new Context($request);
	
	if ($context->getLang () != 'en') {
		$translator = new Translator($context->getLang(), new MessageSelector());
		$translator->addLoader('mo', new MoFileLoader());
		$translator->addResource('mo', __DIR__ . "locale/" . $context->getLang() . "/LC_MESSAGES/messages.mo", $context->getLang());
	}
	
	if ($request->get("content") == "json") {
		header("Content-Type: application/json; charset=UTF-8");
		echo display_phpc($context)->toString();
	} else {
		$page = $context->getPage();
		$response = $page->action($context, array(
				'context' => $context,
				'calendar' => $context->getCalendar(),
				'user' => $context->getUser(),
				'script' => $context->script,
				'embed' => $request->get("content") == "embed",
				'lang' => $context->getLang(),
				'title' => $context->getCalendar()->getTitle(),
				//'theme' => $context->getCalendar()->get_theme(),
				'minified' => defined('PHPC_DEBUG') ? '' : '.min',
				'query_string' => $request->getQueryString() 
		) );
		$response->send();
	}
} catch(PermissionException $e) {
	$msg = __('You do not have permission to do that: ') . $e->getMessage();
	if ($context->getUser()->is_user())
		echo error_message_redirect($context, $msg, $context->script);
	else
		echo error_message_redirect($context, $msg, "{$context->script}?action=login");
} catch(InvalidConfigException $e) {
	(new RedirectResponse("/install"))->send();
} catch(InvalidInputException $e) {
	echo error_message_redirect($context, $e->getMessage(), $e->target);
} catch(\Exception $e) {
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!doctype html>\n";
	echo display_exception($e)->toString();
	exit;
}

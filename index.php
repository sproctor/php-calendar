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

namespace PhpCalendar;

$base_path = __DIR__;

// Displayed in admin
$version = "2.1";

require_once 'vendor/autoload.php';

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\ClassLoader\Psr4ClassLoader;

if(defined('PHPC_DEBUG')) {
	$min = '';
} else {
	$min = '.min';
}

// Set this to the correct location if you've moved your config
require_once ("$base_path/src/helpers.php");

$loader = new Psr4ClassLoader();
$loader->addPrefix("PhpCalendar\\", "$base_path/src");
$loader->register();

mb_internal_encoding('UTF-8');
mb_http_output('pass');


if($debug) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('html_errors', 1);
}

$cookie_prefix = "phpc_" . SQL_PREFIX . SQL_DATABASE;

if(empty($sql_porti)) {
	$sql_port = ini_get("mysqli.default_port");
}

$db = new Database(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE, SQL_PORT);

session_start();

require_once("$base_path/src/schema.php");
if ($db->get_config('version') < PHPC_DB_VERSION) {
	if(isset($_GET['update'])) {
		phpc_updatedb($db->dbh);
	} else {
		print_update_form();
	}
	exit;
}

$request = Request::createFromGlobals();

if(empty($_SESSION["{$prefix}uid"])) {
	if(!empty($_COOKIE["{$prefix}login"]) && !empty($_COOKIE["{$prefix}uid"])
			&& !empty($_COOKIE["{$prefix}login_series"])) {
		// Cleanup before we check their token so they can't login with
		//   an ancient token
		$db->cleanup_login_tokens();

		// FIXME should this be _SESSION below?
		$uid = $_COOKIE["{$prefix}uid"];
		$login_series = $_COOKIE["{$prefix}login_series"];
		$token = $db->get_login_token($uid,
				$login_series);
		if($token) {
			if($token == $_COOKIE["{$prefix}login"]) {
				$user = $db->get_user($uid);
				do_login($user, $login_series);
			} else {
				$db->remove_login_tokens($uid);
				soft_error(__("Possible hacking attempt on your account."));
			}
		} else {
			$uid = 0;
		}
	}
} else {
	$token = $_SESSION["{$prefix}login"];
}

if(empty($token)) {
	$token = '';
}

$user = false;
if(!empty($_SESSION["{$prefix}uid"])) {
	$user = $db->get_user($_SESSION["{$prefix}uid"]);
}

if ($user === false) {
	$uid = 0;
	$anonymous = array('uid' => 0,
			'username' => 'anonymous',
			'password' => '',
			'admin' => false,
			'password_editable' => false,
			'default_cid' => NULL,
			'timezone' => NULL,
			'language' => NULL,
			'disabled' => 0,
			);
	if(isset($_COOKIE["{$prefix}tz"])) {
		$_tz = $_COOKIE["{$prefix}tz"];
		// If we have a timezone, make sure it's valid
		if(in_array($_tz, timezone_identifiers_list())) {
			$anonymous['timezone'] = $_tz;
		} else {
			$anonymous['timezone'] = '';
		}
	}
	if(isset($_COOKIE["{$prefix}lang"]))
		$anonymous['language'] = $_COOKIE["{$prefix}lang"];
	$user = new User($anonymous);
}

$user_tz = $user->get_timezone();

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

$translator = new Translator($lang, new MessageSelector());
$translator->addLoader('mo', new MoFileLoader());
if($lang != 'en') {
	$translator->addResource('mo', "$locale_path/$lang/LC_MESSAGES/messages.mo", $lang);
}

if(!empty($vars['clearmsg'])) {
	$_SESSION["{$prefix}messages"] = NULL;
}

$messages = array();

if(!empty($_SESSION["{$prefix}messages"])) {
	foreach($_SESSION["{$prefix}messages"] as $message) {
		$messages[] = $message;
	}
}

if(!empty($user_tz)) {
	$tz = $user_tz;
} else {
	$tz = $cal->timezone;
}

if(!empty($tz)) {
	date_default_timezone_set($tz); 
}
$tz = date_default_timezone_get();

$theme = $cal->theme;
if(empty($theme))
	$theme = 'smoothness';
	$jquery_version = "1.11.1";
	$jqueryui_version = "1.11.2";
	$fa_version = "4.2.0";

if(!isset($jqui_path))
	$jqui_path = "//ajax.googleapis.com/ajax/libs/jqueryui/$jqueryui_version";
if(!isset($fa_path))
	$fa_path = "//maxcdn.bootstrapcdn.com/font-awesome/$fa_version";
if(!isset($jq_file))
	$jq_file = "//ajax.googleapis.com/ajax/libs/jquery/$jquery_version/jquery$min.js";

$loader = new \Twig_Loader_Filesystem($templates_path);
$twig = new \Twig_Environment($loader, array(
			//'cache' => $comp_cache
			));
$twig->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($translator));
//$twig->addExtension(new \Symfony\Bridge\Twig\Extension\RoutingExtension());

if(isset($cal)) {
	$title = $cal->get_title();
} else {
	$title = _("(No calendars)");
}

$context = array(
		'lang' => $lang,
		'page_title' => $title,
		'static_path' => $static_path,
		'jq_file' => $jq_file,
		'jqui_path' => $jqui_path,
		'fa_path' => $fa_path,
		'users' => _p('Week', 'W'),
		'calendars' => $db->get_calendars(),
		);
$routes = new RouteCollection();
$routes->add('month_view', new Route('/{cid}/month/{year}/{month}',
			array('_controller' => function(Request $request) {
				global $context, $twig;
				$year = $request->get('year');
				$month = $request->get('month');
				$cid = $request->get('cid');
				return MonthController::showAction($twig, $context, $cid, $year, $month);
				}),
			array('year' => '\d+', 'month' => '\d+')));
$routes->add('index', new Route('/',
			array('_controller' => function (Request $request) {
				global $context, $twig, $user;
				if ($user->get_default_cid() !== false) {
					$cid = $user->get_default_cid();
				} else {
					$cid = $db->get_config('default_cid');
				}
				$year = date('Y');
				$month = date('n');
				return MonthController::showAction($twig, $context, $cid, $year, $month);
				})));

$framework = new Framework($routes);

check_config("$base_path/config.php");
require_once ("$base_path/config.php");

$framework->handle($request)->send();

?>

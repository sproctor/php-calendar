<?php
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

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\CsrfTokenManager;


class Context {
	/** @var Calendar $calendar */
	private $calendar;
	/** @var User $user */
	private $user;
	private $session;
	private $formFactory;
	private $year;
	private $month;
	private $day;
	/** @var Request $request */
	public $request;
	public $config;
	private $lang;
	/** @var Database $db*/
	public $db;
	public $token;
	public $script;
	public $url_path;
	public $host_name;
	public $proto;
	/** @var  \Twig_Environment */
	public $twig;

	/**
	 * Context constructor.
     */
	function __construct(Request $request) {

		ini_set('arg_separator.output', '&amp;');
		mb_internal_encoding('UTF-8');
		mb_http_output('pass');

		if(defined('PHPC_DEBUG')) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('html_errors', 1);
		}

		$this->request = $request;

		$this->session = new Session();
		$this->session->start();

		$this->initVars();
		$this->config = $this->loadConfig(PHPC_CONFIG_FILE);
		$this->db = new Database($this->config);

		require_once(__DIR__ . '/schema.php');
		if ($this->db->get_config('version') < PHPC_DB_VERSION) {
			if(isset($_GET['update'])) {
				$this->db->update();
				if (!$this->db->update())
					$this->addMessage(__('Already up to date.'));
				return redirect($this, $this->script);
			} else {
				return print_update_form();
			}
		}

		$this->read_login_token();

		$this->initTimezone();
		$this->initLang($request);

		// set day/month/year - This needs to be done after the timezone is set.
		$this->initDate();

		$this->initTwig();
	}

	/**
	 * @param string $filename
	 * @return string[]
	 */
	private function loadConfig($filename) {
		// Run the installer if we have no config file
		// This doesn't work when embedded from outside
		if(!file_exists($filename)) {
			throw new InvalidConfigException();
		}
		$config = include $filename;

		if(!isset($config["sql_host"])) {
			throw new InvalidConfigException();
		}

		return $config;
	}

	private function initTwig() {
		
		$appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
		$vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());
		$this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(array(
			realpath(__DIR__ . '/../templates'),
			$vendorTwigBridgeDir.'/Resources/views/Form'))
			//, array('cache' => __DIR__ . '/cache',)
		);

		$csrfGenerator = new UriSafeTokenGenerator();
		$csrfStorage = new SessionTokenStorage($this->session);
		$csrfManager = new CsrfTokenManager($csrfGenerator, $csrfStorage);

		$formTheme = 'bootstrap_4_layout.html.twig';
		$formEngine = new TwigRendererEngine(array($formTheme), $this->twig);
		$this->twig->addRuntimeLoader(new \Twig_FactoryRuntimeLoader(array(
			FormRenderer::class => function () use ($formEngine, $csrfManager) {
				return new FormRenderer($formEngine, $csrfManager);
			},
		)));
		//$this->twig->addExtension(new TranslationExtension());
		$this->twig->addExtension(new FormExtension());

		$this->formFactory = Forms::createFormFactoryBuilder()
			->addExtension(new HttpFoundationExtension())
			->addExtension(new CsrfExtension($csrfManager))
			->getFormFactory();
		
		$this->twig->addGlobal('context', $this);
		$this->twig->addGlobal('calendar', $this->getCalendar());
		$this->twig->addGlobal('user', $this->getUser());
		$this->twig->addGlobal('script', $this->script);
		$this->twig->addGlobal('embed', $this->request->get("content") == "embed");
		$this->twig->addGlobal('title', $this->getCalendar()->getTitle());
		$this->twig->addGlobal('messages', $this->getMessages());
		//'theme' => $context->getCalendar()->get_theme(),
		$this->twig->addGlobal('minified', defined('PHPC_DEBUG') ? '' : '.min');
		$this->twig->addGlobal('query_string', $this->request->getQueryString());
		$this->twig->addGlobal('languages', get_languages());

		$this->twig->addFunction(new \Twig_SimpleFunction('dropdown', '\PhpCalendar\create_dropdown', array('is_safe' => array('html'))));
		$this->twig->addFilter(new \Twig_SimpleFilter('trans', '\PhpCalendar\__'));
		$this->twig->addFunction(new \Twig_SimpleFunction('_p', '\PhpCalendar\__p'));
		$this->twig->addFunction(new \Twig_SimpleFunction('day_name', '\PhpCalendar\day_name'));
		$this->twig->addFunction(new \Twig_SimpleFunction('short_day_name', '\PhpCalendar\short_day_name'));
		$this->twig->addFunction(new \Twig_SimpleFunction('index_of_date', '\PhpCalendar\index_of_date'));
		$this->twig->addFunction(new \Twig_SimpleFunction('week_link',
				function(Context $context, \DateTimeInterface $date) {
					list($week, $year) = week_of_year($date, $context->getCalendar()->getWeekStart());
					$url = action_url($context, 'display_week', ['week' => $week, 'year' => $year]);
					return "<a href=\"$url\">$week</a>";
				}));
		$this->twig->addFunction(new \Twig_SimpleFunction('add_days',
				function (\DateTime $date, $days) {
					$date->add ( new \DateInterval ( "P{$days}D" ) );
					return $date;
			}));
		$this->twig->addFunction(new \Twig_SimpleFunction('is_date_in_month',
				function(Context $context, \DateTimeInterface $date) {
					$currentDate = new \DateTime();
					return $context->getAction() == 'display_month'
							&& $date->format('m') == $context->getMonth()
							&& $date->format('Y') == $context->getYear();
		}));
		$this->twig->addFunction(new \Twig_SimpleFunction('is_today', '\PhpCalendar\is_today'));
		$this->twig->addFunction(new \Twig_SimpleFunction('action_date_url', '\PhpCalendar\action_date_url_from_datetime'));
		$this->twig->addFunction(new \Twig_SimpleFunction('action_url', '\PhpCalendar\action_url'));
		$this->twig->addFunction(new \Twig_SimpleFunction('action_event_url', '\PhpCalendar\action_event_url'));
		$this->twig->addFunction(new \Twig_SimpleFunction('action_occurrence_url', '\PhpCalendar\action_occurrence_url'));
		$this->twig->addFunction(new \Twig_SimpleFunction('change_lang_url', '\PhpCalendar\change_lang_url'));
		$this->twig->addFunction(new \Twig_SimpleFunction('day',
				function(\DateTimeInterface $date) { return $date->format('j'); }));
		$this->twig->addFunction(new \Twig_SimpleFunction('can_write',
				function(User $user, Calendar $calendar) { return $calendar->canWrite($user); }));
		$this->twig->addFunction(new \Twig_SimpleFunction('occurrences_for_date',
			function($occurrences, \DateTimeInterface $date) {
				$key = index_of_date($date);
				if(array_key_exists($key, $occurrences))
					return $occurrences[index_of_date($date)];
				return null;
			}));
		$this->twig->addFunction(new \Twig_SimpleFunction('menu_item', '\PhpCalendar\menu_item', array('is_safe' => array('html'))));
	}

	/**
	 * @return string
     */
	public function getAction() {
		return $this->request->get('action', 'display_month');
	}

	private function initVars() {
		$this->script = htmlentities($_SERVER['SCRIPT_NAME']);
		$this->url_path = dirname($_SERVER['SCRIPT_NAME']);
		$port = empty($_SERVER["SERVER_PORT"]) || $_SERVER["SERVER_PORT"] == 80 ? ""
			: ":{$_SERVER["SERVER_PORT"]}";
		$this->host_name = isset($_SERVER['HOST_NAME']) ? $_SERVER['HOST_NAME'] :
			(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] . $port : "localhost");
		$this->proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'
			|| isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443
			|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
			|| isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
			?  "https"
			: "http";
	}

	private function initCurrentCalendar() {
		// Find current calendar
		$current_cid = $this->getCurrentCID();

		$this->calendar = $this->db->getCalendar($current_cid);
		if(empty($this->calendar))
			soft_error(__("Bad calendar ID."));
	}

	/**
	 * @return int
	 * @throws \Exception
     */
	private function getCurrentCID() {
		$phpcid = $this->request->get('phpcid');
		if(isset($phpcid)) {
			if(!is_numeric($phpcid))
				soft_error(__("Invalid calendar ID."));
			return $phpcid;
		}
		
		$eid = $this->request->get('eid');
		if(isset($eid)) {
			if(is_array($eid)) {
				$eid = $eid[0];
			}
			$event = $this->db->get_event_by_eid($eid);
			if($event != null)
				return $event->getCalendar()->getCID();
		}
		
		$oid = $this->request->get('oid');
		if(isset($oid)) {
			$event = $this->db->get_event_by_oid($_REQUEST['oid']);
			if($event != null)
				return $event->getCalendar()->getCID();
		}
		
		$calendars = $this->db->getCalendars();
		if(empty($calendars)) {
			throw new \Exception(escape_entities("There are no calendars."));
			// TODO: create a page to fix this
		} else {
			if ($this->getUser()->defaultCID() !== false)
				$default_cid = $this->getUser()->defaultCID();
			else
				$default_cid = $this->db->get_config('default_cid');
			if (!empty($calendars[$default_cid]))
				return $default_cid;
			else
				return reset($calendars)->getCID();
		}
	}

	private function initTimezone() {
		// Set timezone
		$tz = $this->getUser()->getTimezone();
		if(empty($tz))
			$tz = $this->getCalendar()->getTimezone();

		if(!empty($tz))
			date_default_timezone_set($tz);
	}

	private function initDate() {
		if(isset($_REQUEST['month']) && is_numeric($_REQUEST['month'])) {
			$this->month = $_REQUEST['month'];
			if($this->month < 1 || $this->month > 12)
				soft_error(__("Month is out of range."));
		} else {
			$this->month = date('n');
		}

		if(isset($_REQUEST['year']) && is_numeric($_REQUEST['year'])) {
			$time = mktime(0, 0, 0, $this->month, 1, $_REQUEST['year']);
			if(!$time || $time < 0) {
				soft_error(__('Invalid year') . ": {$_REQUEST['year']}");
			}
			$this->year = date('Y', $time);
		} else {
			$this->year = date('Y');
		}

		if(isset($_REQUEST['day']) && is_numeric($_REQUEST['day'])) {
			$this->day = ($_REQUEST['day'] - 1) % date('t', mktime(0, 0, 0, $this->month, 1, $this->year)) + 1;
		} else {
			if($this->month == date('n') && $this->year == date('Y')) {
				$this->day = date('j');
			} else {
				$this->day = 1;
			}
		}
	}

	/**
	 * @param string $message
     */
	function addMessage($message) {
		$this->session->getFlashBag()->add('message', $message);
	}

	/**
	 * @return string[]
     */
	function getMessages() {
		return $this->session->getFlashBag()->get('message');
	}

	/**
	 * @return User
	 */
	public function getUser() {
		if (!isset($this->user))
			$this->user = User::createAnonymous($this->db, $this->request);
		return $this->user;
	}

	/**
	 * @param User $user
	 */
	public function setUser(User $user) {
		$this->user = $user;
	}

	/**
	 * @return Calendar
	 */
	public function getCalendar() {
		if(!isset($this->calendar))
			$this->initCurrentCalendar();

		return $this->calendar;
	}

	/**
	 * @return int
	 */
	public function getYear() {
		return $this->year;
	}

	/**
	 * @return int
	 */
	public function getMonth() {
		return $this->month;
	}

	/**
	 * @return int
	 */
	public function getDay() {
		return $this->day;
	}

	private function initLang(Request $request) {
		// setup translation stuff
		$lang = $request->get('lang', $this->getUser()->getLanguage());
		if (empty($lang)) {
			$lang = $this->getCalendar()->getLanguage();
			if (empty($lang)) {
				$lang = substr($request->getLocale(), 0, 2);
				if (empty($lang))
					$lang = 'en';
			}
		}

		// Require a 2 letter language
		if(!preg_match('/^\w+$/', $lang, $matches))
			$lang = 'en';

		$this->lang = $lang;
	}

	public function getLang() {
		return $this->lang;
	}

	public function getFormFactory() {
		return $this->formFactory;
	}

	/**
 	* @param Context $context
 	* @param string $page
 	* @return RedirectResponse
 	*/
	public function redirect(Context $context, $page) {
		$dir = $page{0} == '/' ?  '' : dirname($context->script) . '/';
		$url = $context->proto . '://'. $context->host_name . $dir . $page;

		return new RedirectResponse($url);
	}

	private function read_login_token() {
		if(isset($_COOKIE["identity"])) {

			$decoded = \Firebase\JWT\JWT::decode($_COOKIE["identity"], $this->config["token_key"], array('HS256'));
			$decoded_array = (array) $decoded;
			$data = (array) $decoded_array["data"];

			$uid = $data["uid"];
			$user = $this->db->get_user($uid);
			$this->setUser($user);
		}
	}

	function getPage()
	{
		switch($this->getAction()) {
			case 'event_form':
				return new EventFormPage;
			case 'display_event':
				return new EventPage;
			case 'display_month':
				return new MonthPage;
			case 'display_day':
				return new DayPage;
			case 'login':
				return new LoginPage;
			case 'logout':
				return new LogoutPage;
			default:
				throw new \Exception(__('Invalid action'));
		}
	}

}

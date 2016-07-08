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

class Context {
	private $calendar;
	private $user;
	private $messages;
	private $tz;
	private $year;
	private $month;
	private $day;
	public $config;
	private $lang;
	public $db;
	public $token;
	public $script;
	public $url_path;
	public $port;
	public $server;
	public $proto;

	/**
	 * Context constructor.
     */
	function __construct() {

		ini_set('arg_separator.output', '&amp;');
		mb_internal_encoding('UTF-8');
		mb_http_output('pass');

		if(defined('PHPC_DEBUG')) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('html_errors', 1);
		}

		$this->initVars();
		$this->config = load_config($this, PHPC_CONFIG_FILE);
		$this->db = new Database($this->config);

		require_once(__DIR__ . '/schema.php');
		if ($this->db->get_config('version') < PHPC_DB_VERSION) {
			if(isset($_GET['update'])) {
				phpc_updatedb($this);
			} else {
				print_update_form();
			}
			exit;
		}

		read_login_token($this);

		if(!empty($_REQUEST['clearmsg'])) {
			$this->clearMessages();
		}

		$this->messages = array();

		if(!empty($_COOKIE["messages"])) {
			$this->messages = json_decode($_COOKIE["messages"]);
		}

		$this->initTimezone();
		$this->initLang();

		// set day/month/year - This needs to be done after the timezone is set.
		$this->initDate();
	}

	public function clearMessages() {
		setcookie("messages", "", time() - 3600);
		unset($_COOKIE["messages"]);
		$this->messages = array();
	}

	/**
	 * @return string
     */
	public function getAction() {
		return empty($_REQUEST['action']) ? 'display_month' : $_REQUEST['action'];
	}

	private function initVars() {
		$this->script = htmlentities($_SERVER['SCRIPT_NAME']);
		$this->url_path = dirname($_SERVER['SCRIPT_NAME']);
		$this->port = empty($_SERVER["SERVER_PORT"]) || $_SERVER["SERVER_PORT"] == 80 ? ""
			: ":{$_SERVER["SERVER_PORT"]}";
		$this->server = $_SERVER['SERVER_NAME'] . $this->port;
		$this->proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'
			|| $_SERVER['SERVER_PORT'] == 443
			|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
			|| isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
			?  "https"
			: "http";
	}

	private function initCurrentCalendar() {
		// Find current calendar
		$current_cid = $this->getCurrentCID();

		$this->calendar = $this->db->get_calendar($current_cid);
		if(empty($this->calendar))
			soft_error(__("Bad calendar ID."));
	}

	/**
	 * @return int
	 * @throws \Exception
     */
	private function getCurrentCID() {
		if(!empty($_REQUEST['phpcid'])) {
			if(!is_numeric($_REQUEST['phpcid']))
				soft_error(__("Invalid calendar ID."));
			return $_REQUEST['phpcid'];
		}
		
		if(!empty($_REQUEST['eid'])) {
			if(is_array($_REQUEST['eid'])) {
				$eid = $_REQUEST['eid'][0];
			} else {
				$eid = $_REQUEST['eid'];
			}
			$event = $this->db->get_event_by_eid($eid);
			if(empty($event))
				soft_error(__("Invalid event ID."));

			return $event['cid'];
		}
		
		if(!empty($_REQUEST['oid'])) {
			$event = $this->db->get_event_by_oid($_REQUEST['oid']);
			if(empty($event))
				soft_error(__("Invalid occurrence ID."));

			return $event['cid'];
		}
			$calendars = $this->db->get_calendars();
			if(empty($calendars)) {
				// TODO: create a page to fix this
				soft_error("There are no calendars.");
			} else {
				if ($this->getUser()->get_default_cid() !== false)
					$default_cid = $this->getUser()->get_default_cid();
				else
					$default_cid = $this->db->get_config('default_cid');
				if (!empty($calendars[$default_cid]))
					return $default_cid;
				else
					return reset($calendars)->get_cid();
		}
	}

	private function initTimezone() {
		// Set timezone
		if(!empty($this->getUser()->get_timezone()))
			$this->tz = $this->getUser()->get_timezone();
		else
			$this->tz = $this->getCalendar()->timezone;

		if(!empty($tz))
			date_default_timezone_set($this->tz);
		$this->tz = date_default_timezone_get();
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
	 * @return mixed
     */
	public function getTimezone() {
		return $this->tz;
	}

	/**
	 * @param string $message
     */
	function addMessage($message) {
		$this->messages[] = $message;
	}

	/**
	 * @return string[]
     */
	function getMessages() {
		return $this->messages;
	}

	/**
	 * @return User
	 */
	public function getUser() {
		if (!isset($this->user))
			$this->user = User::createAnonymous($this->db);
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

	private function initLang() {
		// setup translation stuff
		if(!empty($_REQUEST['lang'])) {
			$lang = $_REQUEST['lang'];
		} elseif(!empty($this->getUser()->get_language())) {
			$lang = $this->getUser()->get_language();
		} elseif(!empty($this->getCalendar()->language)) {
			$lang = $this->getCalendar()->language;
		} elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$lang = substr(htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']),
				0, 2);
		} else {
			$lang = 'en';
		}

		// Require a 2 letter language
		if(!preg_match('/^\w+$/', $lang, $matches))
			$lang = 'en';

		$this->lang = $lang;
	}

	public function getLang() {
		return $this->lang;
	}
}

?>
<?php
/*
 * Copyright 2009 Sean Proctor
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

class User {
	private $uid;
	private $username;
	private $password;
	private $admin;
	private $password_editable;
	private $default_cid;
	private $timezone;
	private $language;
	private $groups;
	private $disabled;
	private $db;

	private function __construct(Database $db)
	{
		$this->db = $db;
	}

	public static function createFromMap(Database $db, $map) {
		$user = new User($db);

		$user->uid = $map['uid'];
		$user->username = $map['username'];
		$user->password = $map['password'];
		$user->admin = $map['admin'];
		$user->password_editable = $map['password_editable'];
		$user->default_cid = $map['default_cid'];
		$user->timezone = $map['timezone'];
		$user->language = $map['language'];
		$user->disabled = $map['disabled'];

		return $user;
	}

	public static function createAnonymous(Database $db) {
		$user = new User($db);

		$user->uid = 0;
		$user->username = 'anonymous';
		$user->admin = false;
		$user->password_editable = false;
		$user->timezone = getAnonymousTimezone();
		$user->language = getAnonymousLanguage();
		$user->disabled = false;

		return $user;
	}

	function get_username()
	{
		return $this->username;
	}

	function get_uid()
	{
		return $this->uid;
	}

	function get_password() {
		return $this->password;
	}

	function is_password_editable() {
		return $this->password_editable;
	}

	function get_timezone() {
		return $this->timezone;
	}

	function get_language() {
		return $this->language;
	}
	
	function get_groups() {
		if(!isset($this->groups))
			$this->groups = $this->db->get_user_groups($this->uid);

		return $this->groups;
	}

	function is_disabled() {
		return $this->disabled;
	}

	function is_admin() {
		return $this->admin;
	}

	function get_default_cid() {
		return $this->default_cid;
	}

	function is_user()
	{
		return $this->uid > 0;
	}
}

function getAnonymousTimezone() {
	if(isset($_REQUEST['tz'])) {
		$tz = $_COOKIE[$_REQUEST["tz"]];
		// If we have a timezone, make sure it's valid
		if(in_array($tz, timezone_identifiers_list())) {
			return $tz;
		}
	}

	return '';
}

function getAnonymousLanguage() {
	if(isset($_REQUEST['lang']))
		return $_REQUEST['lang'];
	else
		return NULL;
}

?>

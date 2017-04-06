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

class User {
	/** @var int */
	private $uid;
	/** @var string */
	private $username;
	/** @var string */
	private $hash;
	/** @var string */
	private $admin;
	/** @var bool */
	private $password_editable;
	/** @var int */
	private $default_cid;
	/** @var string */
	private $timezone;
	/** @var string */
	private $language;
	private $groups;
	/** @var bool */
	private $disabled;
	/** @var Database */
	private $db;

    /**
     * User constructor.
     * @param Database $db
     */
	private function __construct(Database $db)
	{
		$this->db = $db;
	}

    /**
     * @param Database $db
     * @param $map
     * @return User
     */
	public static function createFromMap(Database $db, $map) {
		$user = new User($db);

		$user->uid = $map['uid'];
		$user->username = $map['username'];
		$user->hash = $map['password'];
		$user->admin = $map['admin'];
		$user->password_editable = $map['password_editable'];
		$user->default_cid = $map['default_cid'];
		$user->timezone = $map['timezone'];
		$user->language = $map['language'];
		$user->disabled = $map['disabled'];

		return $user;
	}

    /**
     * @param Database $db
     * @return User
     */
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

    /**
     * @return string
     */
	function get_username()
	{
		return $this->username;
	}

    /**
     * @return int
     */
	function get_uid()
	{
		return $this->uid;
	}

    /**
     * @return string
     */
	function getPasswordHash() {
		return $this->hash;
	}

    /**
     * @return bool
     */
	function is_password_editable() {
		return $this->password_editable;
	}

    /**
     * @return string
     */
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
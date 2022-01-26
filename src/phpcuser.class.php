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

class PhpcUser {
    /** @var int */
	var $uid;
    /** @var string */
	var $username;
    /** @var string */
	var $password;
    /** @var bool */
	var $admin;
    /** @var bool */
	var $password_editable;
    /** @var string */
    var $timezone;
    /** @var string */
	var $language;
    /** @var array */
	var $groups;

    /**
     * PhpcUser constructor.
     * @param string[] $result
     */
    function __construct($result)
	{
        $this->uid = intval($result['uid']);
		$this->username = $result['username'];
		$this->password = $result['password'];
        $this->admin = intval($result['admin']) != 0;
        $this->password_editable = intval($result['password_editable']) != 0;
		$this->timezone = $result['timezone'];
		$this->language = $result['language'];
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
	function get_password() {
		return $this->password;
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

    /**
     * @return string
     */
	function get_language() {
		return $this->language;
	}

    /**
     * @return array
     */
	function get_groups() {
		global $phpcdb;

		if(!isset($this->groups))
			$this->groups = $phpcdb->get_user_groups($this->uid);

		return $this->groups;
	}
}

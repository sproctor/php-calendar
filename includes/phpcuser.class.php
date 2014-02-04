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
	var $uid;
	var $username;
	var $password;
	var $admin;
	var $password_editable;
	var $timezone;	
	var $language;
	var $groups;

	function PhpcUser($result)
	{
		$this->uid = $result['uid'];
		$this->username = $result['username'];
		$this->password = $result['password'];
		$this->admin = $result['admin'];
		$this->password_editable = $result['password_editable'];
		$this->timezone = $result['timezone'];
		$this->language = $result['language'];
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
		global $phpcdb;

		if(!isset($this->groups))
			$this->groups = $phpcdb->get_user_groups($this->uid);

		return $this->groups;
	}
}

?>

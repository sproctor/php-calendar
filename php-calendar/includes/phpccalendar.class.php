<?php
/*
 * Copyright 2012 Sean Proctor
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

class PhpcCalendar {
	var $cid;
	var $title;
	var $config;
	var $user_perms;
	var $categories;

	function PhpcCalendar($result, $config)
	{
		$this->cid = $result['cid'];
		$this->title = $config['calendar_title'];
		$this->config = $config;
	}

	function get_title()
	{
		if(empty($this->title))
			return _('(No title)');

		return htmlspecialchars($this->title);
	}

	function get_cid()
	{
		return $this->cid;
	}

	function get_config($option = false, $default = '') {
		// if no option is given, return all
		if($option === false)
			return $this->config;

		if(!isset($this->config[$option])) {
			if(defined('PHPC_DEBUG'))
				soft_error("Undefined config option \"$option\".");
			return $default;
		}
		return $this->config[$option];
	}

	function can_read()
	{
		if ($this->get_config('anon_permission') >= 1)
			return true;

		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin() || !empty($this->user_perms["read"]);
	}

	function can_write()
	{
		if ($this->get_config('anon_permission') >= 2)
			return true;

		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin() || !empty($this->user_perms["write"]);
	}

	function can_admin()
	{
		if (!is_user())
			return false;

		$this->require_user_perms();

		return is_admin() || !empty($this->user_perms["admin"]);
	}

	function require_user_perms() {
		global $phpcdb;

		if(!isset($this->user_perms))
			$this->user_perms = $phpcdb->get_permissions($this->cid,
					$_SESSION["phpc_uid"]);

	}

	function can_modify()
	{
		if ($this->get_config('anon_permission') >= 3)
			return true;

		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin()
			|| !empty($$this->user_perms["modify"]);
	}

	function can_create_readonly()
	{
		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin()
			|| !empty($this->user_perms["readonly"]);
	}

	function get_categories() {
		global $phpcdb;

		if(!isset($this->categories))
			$this->categories = $phpcdb->get_categories($this->cid);

		return $this->categories;
	}
}

?>

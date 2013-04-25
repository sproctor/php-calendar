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
	var $user_perms;
	var $categories;
	var $hours_24;
	var $date_format;
	var $week_start;
	var $subject_max;
	var $events_max;
	var $anon_permission;
	var $timezone;
	var $language;
	var $theme;
	var $groups;

	function PhpcCalendar($result) {
		$this->cid = $result['cid'];
		$this->title = $result['title'];
		$this->hours_24 = $result['hours_24'];
		$this->date_format = $result['date_format'];
		$this->week_start = $result['week_start'];
		$this->subject_max = $result['subject_max'];
		$this->events_max = $result['events_max'];
		$this->anon_permission = $result['anon_permission'];
		$this->timezone = $result['timezone'];
		$this->language = $result['language'];
		$this->theme = $result['theme'];
	}

	function get_title()
	{
		if(empty($this->title))
			return __('(No title)');

		return htmlspecialchars($this->title);
	}

	function get_cid()
	{
		return $this->cid;
	}

	function can_read()
	{
		if ($this->anon_permission >= 1)
			return true;

		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin() || !empty($this->user_perms["read"]);
	}

	function can_write()
	{
		if ($this->anon_permission >= 2)
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
		global $phpcdb, $phpc_user;

		if(!isset($this->user_perms))
			$this->user_perms = $phpcdb->get_permissions($this->cid,
					$phpc_user->get_uid());

	}

	function can_modify()
	{
		if ($this->anon_permission >= 3)
			return true;

		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin()
			|| !empty($this->user_perms["modify"]);
	}

	function can_create_readonly()
	{
		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin()
			|| !empty($this->user_perms["readonly"]);
	}

	function get_visible_categories($uid) {
		global $phpcdb;

		return $phpcdb->get_visible_categories($uid, $this->cid);
	}
		
	function get_categories() {
		global $phpcdb;

		if(!isset($this->categories)) {
			$this->categories = $phpcdb->get_categories($this->cid);
		}
		return $this->categories;
	}

	function get_groups() {
		global $phpcdb;

		if(!isset($this->groups)) {
			$this->groups = $phpcdb->get_groups($this->cid);
		}
		return $this->groups;
	}
}

?>

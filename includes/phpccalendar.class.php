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
    /** @var int */
	var $cid;
    /** @var string */
	var $title;
    /** @var string[] */
	var $user_perms;
    /** @var array */
	var $categories;
    /** @var bool */
	var $hours_24;
    /** @var int */
	var $date_format;
    /** @var int */
	var $week_start;
    /** @var int */
	var $subject_max;
    /** @var int */
	var $events_max;
    /** @var int */
	var $anon_permission;
    /** @var string */
	var $timezone;
    /** @var string */
	var $language;
    /** @var string */
	var $theme;
    /** @var array */
	var $groups;

    /**
     * PhpcCalendar constructor.
     * @param string[] $result
     */
    function __construct($result)
    {
        $this->cid = intval($result['cid']);
		$this->title = $result['title'];
        $this->hours_24 = intval($result['hours_24']) != 0;
        $this->date_format = intval($result['date_format']);
        $this->week_start = intval($result['week_start']);
        $this->subject_max = intval($result['subject_max']);
        $this->events_max = intval($result['events_max']);
        $this->anon_permission = intval($result['anon_permission']);
		$this->timezone = $result['timezone'];
		$this->language = $result['language'];
		$this->theme = $result['theme'];
	}

    /**
     * @return string
     */
	function get_title()
	{
		if(empty($this->title))
			return __('(No title)');

		return phpc_html_escape($this->title);
	}

    /**
     * @return int
     */
	function get_cid()
	{
		return $this->cid;
	}

    /**
     * @return bool
     */
	function can_read()
	{
		if ($this->anon_permission >= 1)
			return true;

		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin() || !empty($this->user_perms["read"]);
	}

    /**
     * @return bool
     */
	function can_write()
	{
		if ($this->anon_permission >= 2)
			return true;

		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin() || !empty($this->user_perms["write"]);
	}

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
	function can_create_readonly()
	{
		if (!is_user())
			return false;

		$this->require_user_perms();

		return $this->can_admin()
			|| !empty($this->user_perms["readonly"]);
	}

    /**
     * @param int $uid
     * @return array
     */
	function get_visible_categories($uid) {
		global $phpcdb;

		return $phpcdb->get_visible_categories($uid, $this->cid);
	}

    /**
     * @return array
     */
	function get_categories() {
		global $phpcdb;

		if(!isset($this->categories)) {
			$this->categories = $phpcdb->get_categories($this->cid);
		}
		return $this->categories;
	}

    /**
     * @return array
     */
	function get_groups() {
		global $phpcdb;

		if(!isset($this->groups)) {
			$this->groups = $phpcdb->get_groups($this->cid);
		}
		return $this->groups;
	}
}


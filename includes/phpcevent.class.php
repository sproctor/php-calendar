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

class PhpcEvent {
    /** @var int */
	var $eid;
    /** @var int */
	var $cid;
    /** @var int */
	var $uid;
    /** @var string */
	var $author;
    /** @var string */
	var $subject;
    /** @var string */
	var $desc;
    /** @var bool */
	var $readonly;
    /** @var string */
	var $category;
    /** @var string */
	var $bg_color;
    /** @var string */
	var $text_color;
    /** @var int */
	var $catid;
    /** @var int */
	var $gid;
	var $ctime;
	var $mtime;
    /** @var PhpcCalendar */
	var $cal;

    /**
     * PhpcEvent constructor.
     * @param string[] $event
     */
	function __construct($event)
	{
        global $phpcdb;

        $this->eid = intval($event['eid']);
        $this->cid = intval($event['cid']);
        $this->uid = intval($event['owner']);
		if(empty($event['owner']))
			$this->author = __('anonymous');
		elseif(empty($event['username']))
			$this->author = __('unknown');
		else
			$this->author = $event['username'];
		$this->subject = $event['subject'];
		$this->desc = $event['description'];
        $this->readonly = intval($event['readonly']) != 0;
		$this->category = $event['name'];
		$this->bg_color = $event['bg_color'];
		$this->text_color = $event['text_color'];
        $this->catid = intval($event['catid']);
        $this->gid = intval($event['gid']);
		$this->ctime = $event['ctime'];
		$this->mtime = $event['mtime'];
        $this->cal = $phpcdb->get_calendar($this->cid);
	}

    /**
     * @return string
     */
	function get_raw_subject() {
		return phpc_html_escape($this->subject);
	}

    /**
     * @return string
     */
	function get_subject()
	{
		if(empty($this->subject))
			return __('(No subject)');

		return phpc_html_escape(stripslashes($this->subject));
	}

    /**
     * @return string
     */
	function get_author()
	{
		return $this->author;
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
	function get_raw_desc() {
		// Don't allow tags and make the description HTML-safe
		return phpc_html_escape($this->desc);
	}

    /**
     * @return string
     */
	function get_desc()
	{
		return $this->desc;
	}

    /**
     * @return int
     */
	function get_eid()
	{
		return $this->eid;
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
	function is_readonly()
	{
		return $this->readonly;
	}

    /**
     * @return string
     */
	function get_text_color()
	{
		return phpc_html_escape($this->text_color);
	}

    /**
     * @return string
     */
	function get_bg_color()
	{
		return phpc_html_escape($this->bg_color);
	}

    /**
     * @return string
     */
	function get_category()
	{
		if(empty($this->category))
			return $this->category;
		return phpc_html_escape($this->category);
	}

    /**
     * @return bool
     */
	function is_owner() {
		global $phpc_user;
        /** @var PhpcUser $phpc_user */

		return $phpc_user->get_uid() == $this->get_uid();
	}

	// returns whether or not the current user can modify $event
	function can_modify()
	{
		return $this->cal->can_admin() || $this->is_owner()
			|| ($this->cal->can_modify() && !$this->is_readonly());
	}

	// returns whether or not the current user can read $event
	function can_read() {
		global $phpcdb, $phpc_user;
        /** @var PhpcUser $phpc_user */

		$visible_category = empty($this->gid) || !isset($this->catid)
			|| $phpcdb->is_cat_visible($phpc_user->get_uid(),
					$this->catid);
		return $this->cal->can_read() && $visible_category;
	}

	function get_ctime_string() {
		return format_timestamp_string($this->ctime,
				$this->cal->date_format,
				$this->cal->hours_24);
	}

	function get_mtime_string() {
		return format_timestamp_string($this->mtime,
				$this->cal->date_format,
				$this->cal->hours_24);
	}
}

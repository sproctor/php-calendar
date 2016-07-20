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

namespace PhpCalendar;

class Event {
	var $eid;
	var $cid;
	var $uid;
	var $author;
	var $subject;
	var $desc;
	var $readonly;
	var $category;
	var $bg_color;
	private $text_color;
	var $catid;
	var $gid;
	var $ctime;
	var $mtime;
	var $cal;
	var $fields;
	var $db;

	/**
	 * Event constructor.
	 * @param Database $db
	 * @param \string[] $event
	 */
	function __construct(Database $db, $event)
	{
		$this->db = $db;
		$this->eid = $event['eid'];
		$this->cid = $event['cid'];
		$this->uid = $event['owner'];
		if(empty($event['owner']))
			$this->author = __('anonymous');
		elseif(empty($event['username']))
			$this->author = __('unknown');
		else
			$this->author = $event['username'];
		$this->subject = $event['subject'];
		$this->desc = $event['description'];
		$this->readonly = $event['readonly'];
		$this->category = $event['name'];
		$this->bg_color = $event['bg_color'];
		$this->text_color = $event['text_color'];
		$this->catid = $event['catid'];
		$this->gid = $event['gid'];
		$this->ctime = $event['ctime'];
		$this->mtime = $event['mtime'];
		$this->cal = $db->get_calendar($this->cid);
	}

	function get_raw_subject() {
		return htmlspecialchars($this->subject, ENT_COMPAT, "UTF-8");
	}

	function get_subject()
	{
		if(empty($this->subject))
			return __('(No subject)');

		return htmlspecialchars(stripslashes($this->subject),
				ENT_COMPAT, "UTF-8");
	}

	function get_author()
	{
		return $this->author;
	}

	function get_uid()
	{
		return $this->uid;
	}

	function get_raw_desc() {
		// Don't allow tags and make the description HTML-safe
		return htmlspecialchars($this->desc, ENT_COMPAT, "UTF-8");
	}

	function get_desc()
	{
		return parse_desc($this->desc);
	}

	function get_eid()
	{
		return $this->eid;
	}

	function get_cid()
	{
		return $this->cid;
	}

	function is_readonly()
	{
		return $this->readonly;
	}

	/**
	 * @return NULL|string
	 */
	public function getTextColor() {
		if (empty($this->text_color))
			return null;
		return htmlspecialchars ( $this->text_color, ENT_COMPAT, "UTF-8" );
	}

	/**
	 * @return NULL|string
	 */
	function getBgColor() {
		if (empty($this->bg_color))
			return null;
		return htmlspecialchars($this->bg_color, ENT_COMPAT, "UTF-8");
	}

	function get_category()
	{
		if(empty($this->category))
			return $this->category;
		return htmlspecialchars($this->category, ENT_COMPAT, "UTF-8");
	}

	function is_owner(User $user) {
		return $user->get_uid() == $this->get_uid();
	}

	// returns whether or not the current user can modify $event
	function can_modify(User $user)
	{
		return $this->cal->can_admin($user) || $this->is_owner($user)
			|| ($this->cal->can_modify() && !$this->is_readonly());
	}

	// returns whether or not the user can read this event
	function can_read(User $user) {
		$visible_category = empty($this->gid) || !isset($this->catid)
			|| $this->db->is_cat_visible($user->get_uid(), $this->catid);
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

	function get_fields() {
		if(!isset($this->fields))
			$this->fields = $this->db->get_event_fields($this->eid);

		return $this->fields;
	}
}
?>

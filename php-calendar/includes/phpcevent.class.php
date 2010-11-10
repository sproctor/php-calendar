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

class PhpcEvent {
	var $eid;
	var $cid;
	var $uid;
	var $username;
	var $subject;
	var $desc;
	var $readonly;
	var $category;
	var $bg_color;
	var $text_color;
	var $catid;

	function __construct($event)
	{
		$this->eid = $event['eid'];
		$this->cid = $event['cid'];
		$this->uid = $event['owner'];
		if(empty($event['owner']))
			$this->username = _('anonymous');
		elseif(empty($event['username']))
			$this->username = _('unknown');
		else
			$this->username = $event['username'];
		$this->subject = $event['subject'];
		$this->desc = $event['description'];
		$this->readonly = $event['readonly'];
		$this->category = $event['name'];
		$this->bg_color = $event['bg_color'];
		$this->text_color = $event['text_color'];
		$this->catid = $event['catid'];
	}

	function get_raw_subject() {
		return htmlspecialchars($this->subject, ENT_COMPAT, "UTF-8");
	}

	function get_subject()
	{
		if(empty($this->subject))
			return _('(No subject)');

		return htmlspecialchars(stripslashes($this->subject),
				ENT_COMPAT, "UTF-8");
	}

	function get_username()
	{
		return $this->username;
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

	function get_text_color()
	{
		return htmlspecialchars($this->text_color, ENT_COMPAT, "UTF-8");
	}

	function get_bg_color()
	{
		return htmlspecialchars($this->bg_color, ENT_COMPAT, "UTF-8");
	}
}
?>

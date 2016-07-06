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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function event_delete(Context $context)
{
	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($_REQUEST["eid"])) {
		$message = __('No event selected.');
		$html->add(tag('div', $message));
		return $html;
	}

	if (is_array($_REQUEST["eid"])) {
		$eids = $_REQUEST["eid"];
	} else {
		$eids = array($_REQUEST["eid"]);
	}

	$removed_events = array();
	$unremoved_events = array();
	$permission_denied = array();

	foreach($eids as $eid) {
		$entry = $context->db->get_event_by_eid($eid);
		if(!$entry) {
			continue;
		}
		$event = new PhpcEvent($entry);
		if(!$event->can_modify()) {
			$permission_denied[] = $eid;
		} else {
			if($context->db->delete_event($eid)) {
				$removed_events[] = $eid;
			} else {
				$unremoved_events[] = $eid;
			}
		}
	}

	if(sizeof($removed_events) > 0) {
		if(sizeof($removed_events) == 1)
			$text = __("Removed event");
		else
			$text = __("Removed events");
		$text .= ': ' . implode(', ', $removed_events);
		$html->add(tag('div', $text));
	}

	if(sizeof($unremoved_events) > 0) {
		if(sizeof($unremoved_events) == 1)
			$text = __("Could not remove event");
		else
			$text = __("Could not remove events");
		$text .= ': ' . implode(', ', $unremoved_events);
		$html->add(tag('div', $text));
	}

	if(sizeof($permission_denied) > 0) {
		if(sizeof($permission_denied) == 1)
			$text = __("You do not have permission to remove event");
		else
			$text = __("You do not have permission to remove events");
		$text .= ': ' . implode(', ', $permission_denied);
		$html->add(tag('div', $text));
	}
	
        return message_redirect($html, PHPC_SCRIPT);
}

?>

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

function event_delete()
{
	global $vars, $phpcdb, $phpc_script;

	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($vars["eid"])) {
		$message = __('No event selected.');
		$html->add(tag('div', $message));
		return $html;
	}

	if (is_array($vars["eid"])) {
		$eids = $vars["eid"];
	} else {
		$eids = array($vars["eid"]);
	}

	if (empty($vars["confirm"])) {
		$list = tag('ul');
		foreach ($eids as $eid) {
			$event = new PhpcEvent($phpcdb->get_event_by_eid($eid));
			$list->add(tag('li', "$eid: ".$event->get_subject()));
		}
		$html->add(tag('div', __('Confirm you want to delete:')));
		$html->add($list);
		$html->add(" [ ", create_action_link(__('Confirm'),
					"event_delete", array("eid" => $eids,
						"confirm" => "1")), " ] ");
		$html->add(" [ ", create_action_link(__('Deny'),
					"display_month"), " ] ");
		return $html;
	}

	$removed_events = array();
	$unremoved_events = array();
	$permission_denied = array();

	foreach($eids as $eid) {
		$event = new PhpcEvent($phpcdb->get_event_by_eid($eid));
		if(!$event->can_modify()) {
			$permission_denied[] = $eid;
		} else {
			if($phpcdb->delete_event($eid)) {
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
	
        return message_redirect($html, $phpc_script);
}

?>

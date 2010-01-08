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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function event_delete()
{
	global $vars, $phpcdb;

	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($vars["eid"])) {
		$html->add(tag('p', _('No event selected.')));
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
			$event = $phpcdb->get_event_by_eid($eid);
			$list->add(tag('li', "$eid: ".$event->get_subject()));
		}
		$html->add(tag('p', _('Confirm you want to delete:')));
		$html->add($list);
		$html->add(" [ ", create_action_link(_('Confirm'),
					"event_delete", array("eid" => $eids,
						"confirm" => "1")), " ] ");
		$html->add(" [ ", create_action_link(_('Deny'),
					"display_month"), " ] ");
		return $html;
	}

	foreach($eids as $eid) {
		$event = $phpcdb->get_event_by_eid($eid);
		if(!can_modify_event($event)) {
			$html->add(tag('p', _("You do not have permission to remove event: $eid")));
			continue;
		}

		if($phpcdb->delete_event($eid)) {
			$html->add(tag('p', _("Removed event: $eid")));
		} else {        
			$html->add(tag('p', _("Could not remove event: $eid")));
		}
	}

        return $html;
}

?>

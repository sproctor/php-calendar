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

if (!defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function calendar_delete()
{
    global $vars, $phpcdb, $phpc_script;

    $html = tag('div', attributes('class="phpc-container"'));

    if(empty($vars["cid"])) {
        $html->add(tag('p', __('No calendar selected.')));
        return $html;
    }

    $id = $vars["cid"];

    $calendar = $phpcdb->get_calendar($id);

    if(empty($calendar)) {
        soft_error(__("Calendar does not exist") . ": $id");
    }

    if(!$calendar->can_admin()) {
        soft_error(__("You do not have permission to remove calendar") . ": $id");
    }

    if($phpcdb->delete_calendar($id)) {
        $html->add(tag('p', __("Removed calendar") . ": $id"));
    } else {        
        $html->add(
            tag(
                'p', __("Could not remove calendar")
                . ": $id"
            )
        );
    }

        return message_redirect($html, "$phpc_script?action=admin");
}

?>

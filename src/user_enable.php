<?php
/*
 * Copyright 2013 Sean Proctor
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


function user_enable()
{
    global $vars, $phpcid, $phpcdb, $phpc_script;

    $html = tag('div', attributes('class="phpc-container"'));

    if (!is_admin()) {
        $html->add(tag('p', __('You must be an admin to enable users.')));
        return $html;
    }

    if (empty($vars["uid"])) {
        $html->add(tag('p', __('No user selected.')));
        return $html;
    }

    if (is_array($vars["uid"])) {
        $ids = $vars["uid"];
    } else {
        $ids = array($vars["uid"]);
    }

    foreach ($ids as $id) {
        if ($phpcdb->enable_user($id)) {
            $html->add(tag('p', __("Enabled user: $id")));
        } else {
            $html->add(tag('p', __("Could not enable user: $id")));
        }
    }

        return message_redirect($html, "$phpc_script?action=admin&phpcid=$phpcid");
}

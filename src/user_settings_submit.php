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


function user_settings_submit()
{
    global $phpcid, $vars, $phpcdb, $phpc_user_tz, $phpc_user_lang,
           $phpc_prefix, $phpc_user, $phpc_script;

    verify_token();

    // If we have a timezone, make sure it's valid
    if (!empty($vars["timezone"]) && !in_array($vars['timezone'], timezone_identifiers_list())) {
        soft_error(__("Invalid timezone."));
    }

    // Expire 20 years in the future, give or take.
    $expiration_time = time() + 20 * 365 * 24 * 60 * 60;
    // One hour in the past
    $past_time = time() - 3600;
    if (!empty($vars["timezone"])) {
        setcookie("{$phpc_prefix}tz", $vars['timezone'], $expiration_time);
    } else {
        setcookie("{$phpc_prefix}tz", '', $past_time);
    }
    if (!empty($vars["language"])) {
        setcookie("{$phpc_prefix}lang", $vars['language'], $expiration_time);
    } else {
        setcookie("{$phpc_prefix}lang", '', $past_time);
    }

    if (is_user()) {
        $uid = $phpc_user->get_uid();
        $phpcdb->set_user_default_cid($uid, $vars['default_cid']);
        $phpcdb->set_timezone($uid, $vars['timezone']);
        $phpcdb->set_language($uid, $vars['language']);
        $phpc_user_tz = $vars["timezone"];
        $phpc_user_lang = $vars["language"];
    }

        return message_redirect(
            __('Settings updated.'),
            "$phpc_script?action=user_settings&phpcid=$phpcid"
        );
}

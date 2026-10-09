<?php

/*
 * Copyright 2010 Sean Proctor
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

if (!defined('IN_PHPC')) {
    die("Hacking attempt");
}

/**
 * @return Html
 */
function password_submit()
{
    /** @var PhpcUser $phpc_user */
    global $vars, $phpcdb, $phpc_user;

    if (!is_user()) {
        return tag('div', __('You must be logged in.'));
    }

    verify_token();

    if (!$phpc_user->is_password_editable()) {
        soft_error(__('You do not have permission to change your password.'));
    }

    if (!isset($vars['old_password'])) {
        return tag('div', __('You must specify your old password.'));
    } else {
        $old_password = phpc_post_password('old_password');
    }

    if (!phpc_verify_password($old_password, $phpc_user->password)) {
        return tag('div', __('The password you entered did not match your old password.'));
    }

    $password1 = phpc_post_password('password1');
    if ($password1 === '') {
        return tag('div', __('You must specify a password'));
    }

    if ($password1 !== phpc_post_password('password2')) {
        return tag('div', __('Your passwords did not match'));
    }

    $passwd = phpc_hash_password($password1);

    $phpcdb->set_password($phpc_user->get_uid(), $passwd);

    return tag('div', __('Password updated.'));
}

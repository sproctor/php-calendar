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

if (!defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function user_settings()
{
    global $vars, $phpcdb, $phpc_user;

    $index = tag(
        'ul',
        tag(
            'li', tag(
                'a', attrs('href="#phpc-config"'),
                __('Settings')
            )
        )
    );
    $forms = array();

    $forms[] = config_form();

    if(is_user() && $phpc_user->is_password_editable()) {
        $forms[] = password_form();
        $index->add(
            tag(
                'li', tag(
                    'a', attrs('href="#phpc-password"'),
                    __('Password')
                )
            )
        );
    }

    return tag('div', attrs('class="phpc-tabs"'), $index, $forms);
}

function password_form()
{
    global $phpc_script, $phpc_token;

    $form = tag(
        'form', attributes(
            "action=\"$phpc_script\"",
            'method="post"'
        ),
        tag(
            'div', attrs('class="phpc-sub-title"'),
            __('Change Password')
        ),
        tag(
            'table', attrs('class="phpc-form"'),
            tag(
                'tbody',
                tag(
                    'tr',
                    tag('th', __('Old Password')),
                    tag('td', create_password('old_password'))
                ),
                tag(
                    'tr',
                    tag('th', __('New Password')),
                    tag('td', create_password('password1'))
                ),
                tag(
                    'tr',
                    tag('th', __('Confirm New Password')),
                    tag('td', create_password('password2'))
                )
            )
        ),
        create_hidden('phpc_token', $phpc_token),
        create_hidden('action', 'password_submit'),
        create_submit(__('Submit'))
    );

    return tag('div', attrs('id="phpc-password"'), $form);
}

function config_form()
{
    global $phpc_script, $phpc_user_tz, $phpc_user_lang, $phpc_token,
           $phpcdb, $phpc_user;

    $tz_input = create_multi_select(
        'timezone', get_timezone_list(),
        $phpc_user_tz
    );

    $languages = array("" => __("Default"));
    foreach(get_languages() as $lang) {
        $languages[$lang] = $lang;
    }
    $lang_input = create_select(
        'language', $languages,
        $phpc_user_lang
    );

    $calendars = array("" => __("None"));
    foreach($phpcdb->get_calendars() as $calendar) {
        $calendars[$calendar->get_cid()] = $calendar->get_title();
    }
    $default_input = create_select(
        'default_cid', $calendars,
        $phpc_user->get_default_cid()
    );

    $table = tag('table', attrs('class="phpc-form"'));

    if (is_user()) {
        $table->add(
            tag(
                'tr',
                tag('th', __('Default Calendar')),
                tag('td', $default_input)
            )
        );
    }

    $table->add(
        tag(
            'tr',
            tag('th', __('Timezone')),
            tag('td', $tz_input)
        )
    );
    $table->add(
        tag(
            'tr',
            tag('th', __('Language')),
            tag('td', $lang_input)
        )
    );

    $form = tag(
        'form', attributes(
            "action=\"$phpc_script\"",
            'method="post"'
        ),
        tag(
            'div', attrs('class="phpc-sub-title"'),
            __('Settings')
        ),
        $table,
        create_hidden('phpc_token', $phpc_token),
        create_hidden('action', 'user_settings_submit'),
        create_submit(__('Submit'))
    );

    return tag('div', attrs('id="phpc-config"'), $form);
}
?>

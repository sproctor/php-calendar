<?php 
/*
 * Copyright 2017 Sean Proctor
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

require_once __DIR__ . '/form.php';
require_once __DIR__ . '/html.php';
require_once __DIR__ . '/helpers.php';

function admin() 
{
    global $phpc_version;

    if(!is_admin()) {
            permission_error(__('You must be logged in as an admin.'));
    }

    $menu = tag(
        'ul',
        tag(
            'li', tag(
                'a', attrs('href="#phpc-admin-calendars"'),
                __('Calendars')
            )
        ),
        tag(
            'li', tag(
                'a', attrs('href="#phpc-admin-users"'),
                __('Users')
            )
        ),
        tag(
            'li', tag(
                'a', attrs('href="#phpc-admin-import"'),
                __('Import')
            )
        ),
        tag(
            'li', tag(
                'a', attrs('href="#phpc-admin-translate"'),
                __('Translate')
            )
        )
    );

    $version = tag(
        'div', attrs('class="phpc-bar ui-widget-content"'),
        __('Version') . ": $phpc_version"
    );

    return tag(
        '', tag(
            'div', attrs('class="phpc-tabs"'), $menu,
            calendar_list(), user_list(), import(),
            translation_link()
        ), $version
    );
}



function translation_link() 
{
    global $phpc_script;

    return tag(
        'div', attrs('id="phpc-admin-translate"'),
        tag('p', __('This script needs read access to your calendar directory in order to write the translation files. Alternatively, you could run translate.php from the command line or use msgfmt or any other gettext tool that can generate .mo files from .po files.')),
        tag(
            'a', attrs(
                'class="phpc-button"',
                "href=\"$phpc_script?action=translate\""
            ),
            __('Generate Translations')
        )
    );
}

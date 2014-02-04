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

function settings()
{
	global $vars, $phpcdb, $phpc_user;

	if(!empty($vars["phpc_submit"]))
		settings_submit();

	$index = tag('ul',
			tag('li', tag('a', attrs('href="#phpc-config"'),
					__('Settings'))));
	$forms = array();

	$forms[] = config_form();

	if(is_user() && $phpc_user->is_password_editable()) {
		$forms[] = password_form();
		$index->add(tag('li', tag('a', attrs('href="#phpc-password"'),
						__('Password'))));
	}

	return tag('div', attrs('class="phpc-tabs"'), $index, $forms);
}

function password_form()
{
	global $phpc_script, $phpc_token;

	$form = tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', __('Change Password')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('phpc_token', $phpc_token),
							create_hidden('action', 'password_submit'),
							create_submit(__('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', __('Old Password')),
						tag('td', create_password('old_password'))),
					tag('tr',
						tag('th', __('New Password')),
						tag('td', create_password('password1'))),
					tag('tr',
						tag('th', __('Confirm New Password')),
						tag('td', create_password('password2')))
				   )));

	return tag('div', attrs('id="phpc-password"'), $form);
}

function config_form()
{
	global $phpc_script, $phpc_user_tz, $phpc_user_lang, $phpc_token;

	$tz_input = create_multi_select('timezone', get_timezone_list(),
			$phpc_user_tz);

	$languages = array("" => __("Default"));
	foreach(get_languages() as $lang) {
		$languages[$lang] = $lang;
	}
	$lang_input = create_select('language', $languages,
			$phpc_user_lang);

	$form = tag('form', attributes("action=\"$phpc_script\"",
				'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', __('Settings')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('phpc_token', $phpc_token),
							create_hidden('action', 'settings'),
							create_hidden('phpc_submit', 'settings'),
							create_submit(__('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', __('Timezone')),
						tag('td', $tz_input)),
					tag('tr',
						tag('th', __('Language')),
						tag('td', $lang_input))
				   )));

	return tag('div', attrs('id="phpc-config"'), $form);
}

function settings_submit()
{
	global $phpcid, $vars, $phpcdb, $phpc_user_tz, $phpc_user_lang,
	       $phpc_prefix, $phpc_user;

	verify_token();

	// Expire 20 years in the future, give or take.
	$expiration_time = time() + 20 * 365 * 24 * 60 * 60;
	// One hour in the past
	$past_time = time() - 3600;
	if(!empty($vars["timezone"]))
		setcookie("{$phpc_prefix}tz", $vars['timezone'], $expiration_time);
	else
		setcookie("{$phpc_prefix}tz", '', $past_time);
	if(!empty($vars["language"]))
		setcookie("{$phpc_prefix}lang", $vars['language'], $expiration_time);
	else
		setcookie("{$phpc_prefix}lang", '', $past_time);

	if(is_user()) {
		$uid = $phpc_user->get_uid();
		$phpcdb->set_timezone($uid, $vars['timezone']);
		$phpcdb->set_language($uid, $vars['language']);
		$phpc_user_tz = $vars["timezone"];
		$phpc_user_lang = $vars["language"];
	}

        return message(__('Settings updated.'));
}

?>

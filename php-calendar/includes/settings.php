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

	$forms = array();

	if(is_user() && $phpc_user->is_password_editable())
		$forms[] = password_form();

	$forms[] = config_form();
	return $forms;
}

function password_form()
{
	global $phpc_script;

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', _('Change Password')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('action', 'password_submit'),
							create_submit(_('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', _('Old Password')),
						tag('td', create_password('old_password'))),
					tag('tr',
						tag('th', _('New Password')),
						tag('td', create_password('password1'))),
					tag('tr',
						tag('th', _('Confirm New Password')),
						tag('td', create_password('password2')))
				   )));
}

function config_form()
{
	global $phpc_script, $phpc_user_tz, $phpc_user_lang;

	$tz_input = create_multi_select('timezone', get_timezone_list(),
			$phpc_user_tz);

	$languages = array("" => _("Default"));
	foreach(get_languages() as $lang) {
		$languages[$lang] = $lang;
	}
	$lang_input = create_select('language', $languages,
			$phpc_user_lang);

	return tag('form', attributes("action=\"$phpc_script\"",
				'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', _('Settings')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('action', 'settings'),
							create_hidden('phpc_submit', 'settings'),
							create_submit(_('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', _('Timezone')),
						tag('td', $tz_input)),
					tag('tr',
						tag('th', _('Language')),
						tag('td', $lang_input))
				   )));
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

        return message(_('Settings updated.'));
}

?>

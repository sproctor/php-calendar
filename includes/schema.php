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

/*
   this file contains the db schema and functions to use it.
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

require_once("$phpc_includes_path/phpcsql.php");

function phpc_table_schemas() {
	$tables = array();
	$tables[] = phpc_calendars_table();
	$tables[] = phpc_calendar_fields_table();
	$tables[] = phpc_categories_table();
	$tables[] = phpc_config_table();
	$tables[] = phpc_events_table();
	$tables[] = phpc_groups_table();
	$tables[] = phpc_logins_table();
	$tables[] = phpc_occurrences_table();
	$tables[] = phpc_permissions_table();
	$tables[] = phpc_users_table();
	$tables[] = phpc_user_groups_table();
	return $tables;
}

function phpc_calendars_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'calendars');

	$table->addColumn('cid', "int(11) unsigned NOT NULL auto_increment");
	$table->addColumn('hours_24', "tinyint(1) NOT NULL DEFAULT '0'");
	$table->addColumn('date_format', "tinyint(1) NOT NULL DEFAULT '0'");
	$table->addColumn('week_start', "tinyint(1) NOT NULL DEFAULT '0'");
	$table->addColumn('subject_max', "smallint(5) unsigned NOT NULL DEFAULT '50'");
	$table->addColumn('events_max', "tinyint(4) unsigned NOT NULL DEFAULT '8'");
	$table->addColumn('title', "varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PHP-Calendar'");
	$table->addColumn('anon_permission', "tinyint(1) NOT NULL DEFAULT '1'");
	$table->addColumn('timezone', "varchar(255) COLLATE utf8_unicode_ci");
	$table->addColumn('language', "varchar(255) COLLATE utf8_unicode_ci");
	$table->addColumn('theme', "varchar(255) COLLATE utf8_unicode_ci");

	$table->addKey('PRIMARY', 0, '`cid`');

	return $table;
}

function phpc_calendar_fields_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'calendar_fields');

	$table->addColumn('cid', "int(11) unsigned");
	$table->addColumn('name', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");

	return $table;
}

function phpc_categories_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'categories');

	$table->addColumn('catid', "int(11) unsigned NOT NULL auto_increment");
	$table->addColumn('cid', "int(11) unsigned NOT NULL");
	$table->addColumn('gid', "int(11) unsigned");
	$table->addColumn('name', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");
	$table->addColumn('text_color', "varchar(255) COLLATE utf8_unicode_ci");
	$table->addColumn('bg_color', "varchar(255) COLLATE utf8_unicode_ci");
	$table->addColumn('public', "tinyint(1) unsigned NOT NULL DEFAULT '1'");

	$table->addKey('PRIMARY', 0, '`catid`');
	$table->addKey('cid', 1, '`cid`');

	return $table;
}

function phpc_config_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'config');
	
	$table->addColumn('name', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");
	$table->addColumn('value', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");

	$table->addKey('PRIMARY', 0, '`name`');

	return $table;
}

function phpc_events_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'events');

	$table->addColumn('eid', "int(11) unsigned NOT NULL auto_increment");
	$table->addColumn('cid', "int(11) unsigned NOT NULL");
	$table->addColumn('owner', "int(11) unsigned NOT NULL DEFAULT '0'");
	$table->addColumn('subject', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");
	$table->addColumn('description', "text COLLATE utf8_unicode_ci NOT NULL");
	$table->addColumn('readonly', "tinyint(1) NOT NULL DEFAULT '0'");
	$table->addColumn('catid', "int(11) unsigned");
	$table->addColumn('ctime', "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
	$table->addColumn('mtime', "timestamp");

	$table->addKey('PRIMARY', 0, '`eid`');

	return $table;
}

function phpc_groups_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'groups');

	$table->addColumn('gid', "int(11) unsigned NOT NULL auto_increment");
	$table->addColumn('cid', "int(11) unsigned");
	$table->addColumn('name', "varchar(255) COLLATE utf8_unicode_ci");

	$table->addKey('PRIMARY', 0, '`gid`');

	return $table;
}

function phpc_logins_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'logins');

	$table->addColumn('uid', "int(11) unsigned NOT NULL");
	$table->addColumn('series', "char(43) COLLATE utf8_unicode_ci NOT NULL");
	$table->addColumn('token', "char(43) COLLATE utf8_unicode_ci NOT NULL");
	$table->addColumn('atime', "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

	$table->addKey('PRIMARY', 0, '`uid`,`series`');

	return $table;
}

function phpc_occurrences_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'occurrences');

	$table->addColumn('oid', "int(11) unsigned NOT NULL auto_increment");
	$table->addColumn('eid', "int(11) unsigned NOT NULL");
	$table->addColumn('start_date', "date");
	$table->addColumn('end_date', "date");
	$table->addColumn('start_ts', "timestamp");
	$table->addColumn('end_ts', "timestamp");
	$table->addColumn('time_type', "tinyint(4) NOT NULL DEFAULT '0'");

	$table->addKey('PRIMARY', 0, '`oid`');
	$table->addKey('eid', 1, '`eid`');

	return $table;
}

function phpc_permissions_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'permissions');

	$table->addColumn('cid', 'int(11) unsigned NOT NULL');
	$table->addColumn('uid', 'int(11) unsigned NOT NULL');
	$table->addColumn('read', 'tinyint(1) NOT NULL');
	$table->addColumn('write', 'tinyint(1) NOT NULL');
	$table->addColumn('readonly', 'tinyint(1) NOT NULL');
	$table->addColumn('modify', 'tinyint(1) NOT NULL');
	$table->addColumn('admin', 'tinyint(1) NOT NULL');

	$table->addKey('cid', 0, '`cid`,`uid`');

	return $table;
}

function phpc_users_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'users');

	$table->addColumn('uid', 'int(11) unsigned NOT NULL auto_increment');
	$table->addColumn('username', 'varchar(255) COLLATE utf8_unicode_ci NOT NULL');
	$table->addColumn('password', 'char(32) COLLATE utf8_unicode_ci NOT NULL');
	$table->addColumn('admin', "tinyint(1) NOT NULL DEFAULT '0'");
	$table->addColumn('password_editable', "tinyint(1) NOT NULL DEFAULT '1'");
	$table->addColumn('default_cid', "int(11)");
	$table->addColumn('timezone', "varchar(255) COLLATE utf8_unicode_ci");
	$table->addColumn('language', "varchar(255) COLLATE utf8_unicode_ci");
	$table->addColumn('gid', "int(11)");
	$table->addColumn('disabled', "tinyint(1) NOT NULL DEFAULT '0'");

	return $table;
}

function phpc_user_groups_table() {
	$table = new PhpcSqlTable(SQL_PREFIX . 'user_groups');

	$table->addColumn('gid', "int(11) unsigned");
	$table->addColumn('uid', "int(11) unsigned");
	
	return $table;
}

function phpc_updatedb($dbh) {
	global $phpc_script, $phpcdb;

	$message_tags = tag('div', tag('div', __("Updating calendar")));

	$updated = false;
	foreach(phpc_table_schemas() as $table) {
		$tags = $table->update($dbh);
		$message_tags->add($tags);
		if(sizeof($tags) > 0)
			$updated = true;
	}
	$phpcdb->set_config("version", PHPC_DB_VERSION);

	if(!$updated)
		$message_tags->add(tag('div', __('Already up to date.')));

	message_redirect($message_tags, $phpc_script);
}

?>

<?php
/*
 * Copyright Sean Proctor
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

namespace PhpCalendar;

define('PHPC_DB_VERSION', 3);

/**
 * @param string $prefix
 * @return SqlTable[]
 */
function phpc_table_schemas(string $prefix): array
{
    return array
    ( phpc_calendars_table($prefix)
    , phpc_calendar_fields_table($prefix)
    , phpc_categories_table($prefix)
    , phpc_config_table($prefix)
    , phpc_events_table($prefix)
    , phpc_groups_table($prefix)
    , phpc_occurrences_table($prefix)
    , phpc_permissions_table($prefix)
    , phpc_users_table($prefix)
    , phpc_user_groups_table($prefix)
    , phpc_fields_table($prefix)
    , phpc_event_fields_table($prefix)
    );
}

function phpc_calendars_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'calendars');

    $table->addColumn('cid', "int(11) unsigned NOT NULL auto_increment");
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

function phpc_calendar_fields_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'calendar_fields');

    $table->addColumn('cid', "int(11) unsigned");
    $table->addColumn('name', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");

    return $table;
}

function phpc_categories_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'categories');

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

function phpc_config_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'config');
    
    $table->addColumn('name', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");
    $table->addColumn('value', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");

    $table->addKey('PRIMARY', 0, '`name`');

    return $table;
}

function phpc_events_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'events');

    $table->addColumn('eid', "int(11) unsigned NOT NULL auto_increment");
    $table->addColumn('cid', "int(11) unsigned NOT NULL");
    $table->addColumn('owner', "int(11) unsigned NOT NULL DEFAULT '0'");
    $table->addColumn('subject', "varchar(255) COLLATE utf8_unicode_ci NOT NULL");
    $table->addColumn('description', "text COLLATE utf8_unicode_ci NOT NULL");
    $table->addColumn('catid', "int(11) unsigned");
    $table->addColumn('ctime', "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $table->addColumn('mtime', "timestamp NULL");
    $table->addColumn('pubtime', "timestamp NULL");

    $table->addKey('PRIMARY', 0, '`eid`');

    return $table;
}

function phpc_groups_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'groups');

    $table->addColumn('gid', "int(11) unsigned NOT NULL auto_increment");
    $table->addColumn('cid', "int(11) unsigned");
    $table->addColumn('name', "varchar(255) COLLATE utf8_unicode_ci");

    $table->addKey('PRIMARY', 0, '`gid`');

    return $table;
}

function phpc_occurrences_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'occurrences');

    $table->addColumn('oid', "int(11) unsigned NOT NULL auto_increment");
    $table->addColumn('eid', "int(11) unsigned NOT NULL");
    $table->addColumn('start', "datetime");
    $table->addColumn('end', "datetime");
    $table->addColumn('time_type', "tinyint(4) NOT NULL DEFAULT '0'");

    $table->addKey('PRIMARY', 0, '`oid`');
    $table->addKey('eid', 1, '`eid`');

    return $table;
}

function phpc_permissions_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'permissions');

    $table->addColumn('cid', 'int(11) unsigned NOT NULL');
    $table->addColumn('uid', 'int(11) unsigned NOT NULL');
    $table->addColumn('read', 'tinyint(1) NOT NULL');
    $table->addColumn('write', 'tinyint(1) NOT NULL');
    $table->addColumn('modify', 'tinyint(1) NOT NULL');
    $table->addColumn('admin', 'tinyint(1) NOT NULL');

    $table->addKey('cid', 0, '`cid`,`uid`');

    return $table;
}

function phpc_users_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'users');

    $table->addColumn('uid', 'int(11) unsigned NOT NULL auto_increment');
    $table->addColumn('username', 'varchar(255) COLLATE utf8_unicode_ci NOT NULL');
    $table->addColumn('password', 'char(255) COLLATE utf8_unicode_ci NOT NULL');
    $table->addColumn('admin', "tinyint(1) NOT NULL DEFAULT '0'");
    $table->addColumn('password_editable', "tinyint(1) NOT NULL DEFAULT '1'");
    $table->addColumn('default_cid', "int(11)");
    $table->addColumn('timezone', "varchar(255) COLLATE utf8_unicode_ci");
    $table->addColumn('language', "varchar(255) COLLATE utf8_unicode_ci");
    $table->addColumn('gid', "int(11)");
    $table->addColumn('disabled', "tinyint(1) NOT NULL DEFAULT '0'");
    
    $table->addKey('PRIMARY', 0, '`uid`');
    $table->addKey('username', 1, '`username`');

    return $table;
}

function phpc_user_groups_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'user_groups');

    $table->addColumn('gid', "int(11) unsigned");
    $table->addColumn('uid', "int(11) unsigned");
    
    return $table;
}

function phpc_fields_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'fields');

    $table->addColumn('fid', 'int(11) unsigned NOT NULL');
    $table->addColumn('cid', 'int(11) unsigned DEFAULT NULL');
    $table->addColumn('name', 'varchar(255) COLLATE utf8_unicode_ci');
    $table->addColumn('required', 'tinyint(1) NOT NULL');
    $table->addColumn('format', 'varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL');

    return $table;
}

function phpc_event_fields_table(string $prefix): SqlTable
{
    $table = new SqlTable($prefix . 'event_fields');

    $table->addColumn('eid', 'int(11) unsigned NOT NULL');
    $table->addColumn('fid', 'int(11) unsigned NOT NULL');
    $table->addColumn('value', 'text COLLATE utf8_unicode_ci NOT NULL');

    return $table;
}

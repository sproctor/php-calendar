CREATE TABLE phpc_general (
  version tinyint(4) NOT NULL default '1',
  default_calendar int(11) NOT NULL default '0'
);

CREATE TABLE phpc_calendar (
  id int(11) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  owner int(11) NOT NULL default '0',
  hours_24 int(11) NOT NULL default '0',
  start_monday int(11) NOT NULL default '0',
  translate int(11) NOT NULL default '0',
  anon_permission int(11) NOT NULL default '0',
  subject_max int(11) NOT NULL default '32',
  title varchar(255) NOT NULL default '',
  URL varchar(255) default NULL,
  PRIMARY KEY (id),
  KEY name (name)
);

CREATE TABLE phpc_event (
  id int(11) NOT NULL default '0',
  userid int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  description text NOT NULL,
  calendar int(11) NOT NULL default '0',
  PRIMARY KEY (id)
);

CREATE TABLE phpc_events_groups (
  eventid int(10) NOT NULL default '0',
  groupid int(10) NOT NULL default '0',
  access tinyint(4) NOT NULL default '0',
  PRIMARY KEY (eventid,groupid)
);

CREATE TABLE phpc_group (
  id int(10) NOT NULL default '0',
  name varchar(64) NOT NULL default '',
  PRIMARY KEY (id)
);

CREATE TABLE phpc_occurrence (
  eventid int(10) unsigned NOT NULL default '0',
  start_date date default NULL,
  end_date date default NULL,
  day_of_week tinyint(1) unsigned default NULL,
  day_of_month tinyint(2) unsigned default NULL,
  month tinyint(2) unsigned default NULL,
  nth_occurrence tinyint(2) unsigned default NULL,
  nth_in_month tinyint(1) unsigned default NULL,
  time time NOT NULL,
  duration smallint(6) unsigned NOT NULL,
  PRIMARY KEY (event_id)
);

CREATE TABLE phpc_sequence (
  id int(11) NOT NULL default '0'
);

CREATE TABLE phpc_user (
  id int(11) NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  PRIMARY KEY (id)
);

CREATE TABLE phpc_users_groups (
  groupid smallint(6) NOT NULL default '0',
  userid smallint(6) NOT NULL default '0',
  access tinyint(4) NOT NULL default '0',
  PRIMARY KEY (groupid,userid)
);

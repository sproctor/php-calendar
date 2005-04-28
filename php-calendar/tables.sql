CREATE TABLE phpc_calendars (
  id int(2) NOT NULL default '0',
  hours_24 int(11) NOT NULL default '0',
  start_monday int(11) NOT NULL default '0',
  translate int(11) NOT NULL default '0',
  anon_permission int(11) NOT NULL default '0',
  subject_max int(11) NOT NULL default '32',
  contact_name varchar(255) default NULL,
  contact_email varchar(255) default NULL,
  title varchar(255) NOT NULL default '',
  URL varchar(200) default NULL,
  PRIMARY KEY  (id)
);

CREATE TABLE phpc_events (
  id int(11) NOT NULL default '0',
  uid int(11) NOT NULL default '0',
  type tinyint(3) unsigned NOT NULL default '0',
  time time default NULL,
  duration int(11) default NULL,
  title varchar(255) NOT NULL default '',
  description text NOT NULL,
  calendar_id int(2) NOT NULL default '0',
  PRIMARY KEY  (id)
);

CREATE TABLE phpc_events_groups (
  event_id smallint(6) NOT NULL default '0',
  gid smallint(6) NOT NULL default '0',
  access tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (event_id,gid)
);

CREATE TABLE phpc_groups (
  id smallint(6) NOT NULL default '0',
  name varchar(64) NOT NULL default '',
  PRIMARY KEY  (id)
);

CREATE TABLE phpc_occurrences (
  event_id int(10) unsigned NOT NULL default '0',
  start_date date default NULL,
  end_date date default NULL,
  day_of_week tinyint(1) unsigned default NULL,
  day_of_month tinyint(2) unsigned default NULL,
  month tinyint(2) unsigned default NULL,
  nth_occurrence tinyint(2) unsigned default NULL,
  nth_in_month tinyint(1) unsigned default NULL,
  time time default NULL,
  duration smallint(6) unsigned default NULL,
  KEY start_date (start_date,end_date,day_of_week,month,nth_occurrence,nth_in_month),
  KEY event_id (event_id)
);

CREATE TABLE phpc_sequence (
  id int(11) NOT NULL default '0'
);

CREATE TABLE phpc_users (
  uid int(11) NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  PRIMARY KEY  (uid)
);

CREATE TABLE phpc_users_groups (
  gid smallint(6) NOT NULL default '0',
  uid smallint(6) NOT NULL default '0',
  access tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (gid,uid)
);

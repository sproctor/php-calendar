# phpMyAdmin MySQL-Dump
# version 2.3.0
# http://phpwizard.net/phpMyAdmin/
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Generation Time: Jan 09, 2003 at 09:14 AM
# Server version: 3.23.51
# PHP Version: 4.2.3
# Database : `calendar`
# --------------------------------------------------------

#
# Table structure for table `phpc_admin`
#

CREATE TABLE phpc_admin (
  calno int(11) NOT NULL default '0',
  UID varchar(9) NOT NULL default '',
  password varchar(30) NOT NULL default '',
  PRIMARY KEY  (calno,UID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `phpc_calendars`
#

CREATE TABLE phpc_calendars (
  calno int(11) NOT NULL auto_increment,
  contact_name varchar(40) default NULL,
  contact_email varchar(30) default NULL,
  cal_name varchar(200) NOT NULL default '',
  URL varchar(200) default NULL,
  PRIMARY KEY  (calno)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `phpc_events`
#

CREATE TABLE phpc_events (
  id int(11) NOT NULL auto_increment,
  username varchar(255) default NULL,
  stamp datetime default NULL,
  duration datetime default NULL,
  eventtype int(4) default NULL,
  subject varchar(255) default NULL,
  description longblob,
  calno int(4) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;


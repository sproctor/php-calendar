CREATE TABLE phpc_events (
  id INT(11) DEFAULT '0' NOT NULL auto_increment,
  username VARCHAR(255),
  stamp DATETIME,
  duration DATETIME,
  eventtype INT(4),
  subject VARCHAR(255),
  description TEXT,
  PRIMARY KEY (id)
);


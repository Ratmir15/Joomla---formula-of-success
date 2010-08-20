CREATE TABLE IF NOT EXISTS `#__jp_profiles` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
);
INSERT IGNORE INTO `#__jp_profiles` (`id`,`description`) VALUES (1,'Default Backup Profile');

CREATE TABLE IF NOT EXISTS `#__jp_exclusion` (
	`id` bigint(20) NOT NULL auto_increment,
	`profile` int(10) unsigned NOT NULL,
	`class` varchar(255) NOT NULL,
	`value` longtext NOT NULL,
	PRIMARY KEY  (`id`)
); 

CREATE TABLE IF NOT EXISTS `#__jp_inclusion` (
	`id` bigint(20) NOT NULL auto_increment,
	`profile` int(10) unsigned NOT NULL,
	`class` varchar(255) NOT NULL,
	`value` longtext NOT NULL,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `#__jp_registry` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `profile` int(10) unsigned NOT NULL default '1',
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `#__jp_temp` (
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY  (`key`)
);

CREATE TABLE IF NOT EXISTS `#__jp_stats` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `description` varchar(255) NOT NULL,
  `comment` longtext,
  `backupstart` timestamp NOT NULL default '0000-00-00 00:00:00',
  `backupend` timestamp NOT NULL default '0000-00-00 00:00:00',
  `status` enum('run','fail','complete') NOT NULL default 'run',
  `origin` enum('backend','frontend') NOT NULL default 'backend',
  `type` enum('full','dbonly','extradbonly') NOT NULL default 'full',
  `profile_id` bigint(20) NOT NULL default '1',
  `archivename` longtext,
  `absolute_path` longtext,
  PRIMARY KEY  (`id`)
);

ALTER TABLE `#__jp_stats` MODIFY COLUMN `type` ENUM('full','dbonly','extradbonly') NOT NULL DEFAULT 'full';

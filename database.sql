CREATE TABLE IF NOT EXISTS `wcddl_blacklist` (
	`url` varchar(100) NOT NULL,
	`reason` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcddl_config` (
	`config_name` varchar(200) NOT NULL,
	`config_val` text,
	`config_group` varchar(30) NOT NULL DEFAULT 'misc',
	PRIMARY KEY (`config_name`),
	KEY `config_group` (`config_group`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcddl_downloads` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`sid` int(5) NOT NULL,
	`title` varchar(255) NOT NULL,
	`type` varchar(20) NOT NULL,
	`url` text NOT NULL,
	`time_added` datetime NOT NULL,
	`views` int(5) NOT NULL DEFAULT '1',
	`rating` int(2) NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`),
	FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcddl_queue` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`sid` int(5) NOT NULL,
	`title` varchar(255) NOT NULL,
	`type` varchar(20) NOT NULL,
	`url` text NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcddl_recents` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`query` varchar(255) NOT NULL,
	`searches` int(5) NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`),
	KEY `searches` (`searches`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcddl_sites` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(200) NOT NULL,
	`url` varchar(200) NOT NULL,
	`email` varchar(200) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcddl_whitelist` (
	`url` varchar(100) NOT NULL,
	PRIMARY KEY (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO wcddl_config (config_name, config_val) VALUES ('admin_links', 'a:7:{i:0;a:2:{i:0;s:9:"?go=queue";i:1;s:5:"Queue";}i:1;a:2:{i:0;s:13:"?go=downloads";i:1;s:9:"Downloads";}i:2;a:2:{i:0;s:13:"?go=whitelist";i:1;s:9:"Whitelist";}i:3;a:2:{i:0;s:13:"?go=blacklist";i:1;s:9:"Blacklist";}i:4;a:2:{i:0;s:11:"?go=modules";i:1;s:7:"Modules";}i:5;a:2:{i:0;s:16:"?go=downloadsAdd";i:1;s:12:"Add Download";}i:6;a:2:{i:0;s:9:"?go=sites";i:1;s:12:"Manage Sites";}}');

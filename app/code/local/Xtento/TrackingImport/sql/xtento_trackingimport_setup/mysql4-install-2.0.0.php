<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS `" . $this->getTable('xtento_trackingimport_profile') . "` (
  `profile_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `entity` varchar(255) NOT NULL,
  `processor` varchar(255) NOT NULL,
  `enabled` int(1) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `source_ids` varchar(255) NOT NULL,
  `last_execution` datetime DEFAULT NULL,
  `last_modification` datetime DEFAULT NULL,
  `conditions_serialized` text NOT NULL,
  `cronjob_enabled` int(1) NOT NULL DEFAULT '0',
  `cronjob_frequency` varchar(255) NOT NULL,
  `cronjob_custom_frequency` varchar(255) NOT NULL,
  `configuration` mediumtext NOT NULL,
  PRIMARY KEY (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `" . $this->getTable('xtento_trackingimport_source') . "` (
  `source_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `port` int(5) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `timeout` int(5) NOT NULL DEFAULT '15',
  `path` varchar(255) NOT NULL,
  `filename_pattern` varchar(255) NOT NULL DEFAULT '//',
  `archive_path` varchar(255) NOT NULL,
  `delete_imported_files` int(1) NOT NULL DEFAULT '0',
  `ftp_type` enum('','ftp','ftps') NOT NULL,
  `ftp_pasv` int(1) NOT NULL DEFAULT '1',
  `custom_class` varchar(255) NOT NULL,
  `custom_function` varchar(255) NOT NULL,
  `last_result` int(1) NOT NULL,
  `last_result_message` text NOT NULL,
  `last_modification` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `" . $this->getTable('xtento_trackingimport_log') . "` (
  `log_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `profile_id` int(9) NOT NULL,
  `files` text NOT NULL,
  `source_ids` text NOT NULL,
  `import_type` int(9) NOT NULL,
  `import_event` varchar(255) NOT NULL,
  `records_imported` int(9) NOT NULL,
  `result` int(1) NOT NULL,
  `result_message` text NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
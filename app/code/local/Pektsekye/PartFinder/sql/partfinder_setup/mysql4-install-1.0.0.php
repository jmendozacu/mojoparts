<?php

$this->startSetup();

$this->run("

DROP TABLE IF EXISTS `{$this->getTable('partfinder/selector')}`;
CREATE TABLE `{$this->getTable('partfinder/selector')}` (
  `id` int(10) unsigned NOT NULL, 
  `level_0` varchar(60) default NULL,    
  `level_1` varchar(60) default NULL, 
  `level_2` varchar(60) default NULL, 
  `level_3` varchar(60) default NULL, 
  `level_4` varchar(60) default NULL, 
  `level_5` varchar(60) default NULL, 
  `level_6` varchar(60) default NULL, 
  `level_7` varchar(60) default NULL, 
  `level_8` varchar(60) default NULL,  
  `level_9` varchar(60) default NULL,         
  PRIMARY KEY  (`id`),
  KEY `PARTFINDER_SELECTOR_LEVEL0` (`level_0`), 
  KEY `PARTFINDER_SELECTOR_LEVEL1` (`level_1`), 
  KEY `PARTFINDER_SELECTOR_LEVEL2` (`level_2`), 
  KEY `PARTFINDER_SELECTOR_LEVEL3` (`level_3`), 
  KEY `PARTFINDER_SELECTOR_LEVEL4` (`level_4`), 
  KEY `PARTFINDER_SELECTOR_LEVEL5` (`level_5`),
  KEY `PARTFINDER_SELECTOR_LEVEL6` (`level_6`), 
  KEY `PARTFINDER_SELECTOR_LEVEL7` (`level_7`), 
  KEY `PARTFINDER_SELECTOR_LEVEL8` (`level_8`), 
  KEY `PARTFINDER_SELECTOR_LEVEL9` (`level_9`)                   
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{$this->getTable('partfinder/selector_level')}`;
CREATE TABLE `{$this->getTable('partfinder/selector_level')}` (
  `id` int(10) unsigned NOT NULL auto_increment, 
  `column_name` varchar(255) default NULL,    
  `option_title` varchar(255) default NULL,
  `url_parameter` varchar(255) NOT NULL,  
  PRIMARY KEY  (`id`)  
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('partfinder/column')}`;
CREATE TABLE `{$this->getTable('partfinder/column')}` (
  `id` int(10) unsigned NOT NULL auto_increment,    
  `column_name` varchar(255) default NULL,
  `attribute_id` smallint(5) unsigned NOT NULL,  
  `option_ids_already_in_eav` text,
  `option_ids_added_to_eav` text,       
  PRIMARY KEY  (`id`),
	KEY `PARTFINDER_COLUMN_ATTRIBUTE_ID` (`attribute_id`),
	CONSTRAINT `FK_PARTFINDER_COLUMN_ATTRIBUTE_ID` FOREIGN KEY (`attribute_id`) REFERENCES `{$this->getTable('eav/attribute')}` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('partfinder/attribute')}`;
CREATE TABLE `{$this->getTable('partfinder/attribute')}` ( 
  `attribute_id` smallint(5) unsigned NOT NULL,   
  `is_visible` tinyint(1) unsigned NOT NULL default 0,
	UNIQUE `PARTFINDER_ATTRIBUTE_ATTRIBUTE_ID` (`attribute_id`),
	CONSTRAINT `FK_PARTFINDER_ATTRIBUTE_ATTRIBUTE_ID` FOREIGN KEY (`attribute_id`) REFERENCES `{$this->getTable('eav/attribute')}` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('partfinder/attribute_option')}`;
CREATE TABLE `{$this->getTable('partfinder/attribute_option')}` (
  `id` int(10) unsigned NOT NULL auto_increment, 
  `selector_row_id` int(10) unsigned NOT NULL,
  `attribute_id` smallint(5) unsigned NOT NULL, 
  `attribute_option_id` int(10) unsigned NOT NULL, 
  PRIMARY KEY  (`id`),    
  KEY `PARTFINDER_ATTRIBUTE_OPTION_SELECTOR_ROW_ID` (`selector_row_id`),
	KEY `PARTFINDER_ATTRIBUTE_OPTION_ATTRIBUTE_ID` (`attribute_id`),  
	KEY `PARTFINDER_ATTRIBUTE_OPTION_ATTRIBUTE_OPTION_ID` (`attribute_option_id`),
	UNIQUE `PARTFINDER_ATTRIBUTE_OPTION_SRI_AI_AOI` (`selector_row_id`,`attribute_id`,`attribute_option_id`),	
	CONSTRAINT `FK_PARTFINDER_ATTRIBUTE_OPTION_SELECTOR_ROW_ID` FOREIGN KEY (`selector_row_id`) REFERENCES `{$this->getTable('partfinder/selector')}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,	
	CONSTRAINT `FK_PARTFINDER_ATTRIBUTE_OPTION_ATTRIBUTE_OPTION_ID` FOREIGN KEY (`attribute_option_id`) REFERENCES `{$this->getTable('eav/attribute_option')}` (`option_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$this->endSetup();

<?php

$this->startSetup();

$this->run("

DROP TABLE IF EXISTS `{$this->getTable('partfinder/restriction')}`;
CREATE TABLE `{$this->getTable('partfinder/restriction')}` ( 
  `selector_row_id` int(10) unsigned NOT NULL, 
  `product_ids` text NOT NULL               
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE  `{$this->getTable('partfinder/selector_level')}` ADD  `level` TINYINT( 1 ) NOT NULL AFTER  `id`;

ALTER TABLE `{$this->getTable('partfinder/column')}`
DROP COLUMN `option_ids_already_in_eav`,
DROP COLUMN `option_ids_added_to_eav`,
ADD UNIQUE (`column_name`);


DROP TABLE IF EXISTS `{$this->getTable('partfinder/attribute_addedvalues')}`;
CREATE TABLE `{$this->getTable('partfinder/attribute_addedvalues')}` (
  `id` int(10) unsigned NOT NULL auto_increment, 
  `attribute_id` smallint(5) unsigned NOT NULL,
  `value_ids` text NOT NULL,      
  PRIMARY KEY (`id`),
	UNIQUE `PARTFINDER_ATTRIBUTE_ADDEDVALUES_ATTRIBUTE_ID` (`attribute_id`),  
	CONSTRAINT `FK_PARTFINDER_ATTRIBUTE_ADDEDVALUES_ATTRIBUTE_ID` FOREIGN KEY (`attribute_id`) REFERENCES `{$this->getTable('eav/attribute')}` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{$this->getTable('partfinder/attribute')}`;

DROP TABLE IF EXISTS `{$this->getTable('partfinder/attribute_visibility')}`;
CREATE TABLE `{$this->getTable('partfinder/attribute_visibility')}` (
  `id` int(10) unsigned NOT NULL auto_increment, 
  `attribute_id` smallint(5) unsigned NOT NULL,     
  PRIMARY KEY (`id`),
	UNIQUE `PARTFINDER_ATTRIBUTE_VISIBILITY_ATTRIBUTE_ID` (`attribute_id`),  
	CONSTRAINT `FK_PARTFINDER_ATTRIBUTE_VISIBILITY_ATTRIBUTE_ID` FOREIGN KEY (`attribute_id`) REFERENCES `{$this->getTable('eav/attribute')}` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                
");

$this->endSetup();

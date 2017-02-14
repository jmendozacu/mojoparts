<?php

$installer = $this;
$installer->startSetup();

$installer->run("
ALTER TABLE `{$installer->getTable('dataflow/profile')}` ADD `is_commerce_extensions` TINYINT( 1 ) NOT NULL DEFAULT '0'
");

$installer->endSetup();
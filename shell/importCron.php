<?php
echo "Starting program...\r\n";
ini_set('memory_limit', '384M');
ini_set('auto_detect_line_endings',true);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '../app/Mage.php';
umask(0);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

// Trigger the event
$import = new Xtento_TrackingImport_Model_Observer_Cronjob;
$import->import(true);
?>

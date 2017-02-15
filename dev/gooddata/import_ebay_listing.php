<?php
require_once dirname(__FILE__).'/class.ebay_listing_import.php';
if(strpos(dirname(__FILE__),'public_html')===false){
	$isDebug = false;
}else{
	$isDebug = true;
}
$si = new ebay_listing_import($isDebug);
$si->import_run();
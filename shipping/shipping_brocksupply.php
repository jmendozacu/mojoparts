<?php
require_once dirname(__FILE__).'/class.shipping_import.php';
if(strpos(dirname(__FILE__),'public_html')===false){
	$isDebug = false;
}else{
	$isDebug = true;
}
$si = new shipping_import($isDebug);
$si->brocksupply_run();
<?php
ini_set('memory_limit', '1536M');

require("app/Mage.php");  // load the main Mage file
Mage::app();   // not run() because you just want to load Magento, not run it.




$products = Mage::getModel('catalog/product')->getCollection()->getAllIds();

$arr = array();
foreach($products as $prod) {
	$product = Mage::getModel('catalog/product')->load($prod);
	$sku = $product->getSku();
	$arr[$prod] = $sku;

}
var_export($arr);

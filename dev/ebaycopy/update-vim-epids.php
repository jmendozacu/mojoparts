<?php
error_reporting(E_ALL);
$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
// TODO: use an included config file for db connection.  The hard-coded connection was removed for better security.
// mysqli_real_connect($con, '$server','$user','$passord','$database');
if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$test = "DEC00028";
$result_compat = mysqli_query($con,"
	SELECT a.sku, c.value
	FROM catalog_product_entity a
	inner join catalog_product_entity_text c on a.entity_id = c.entity_id and c.attribute_id=200 and c.value <> ''
	inner join mojo_vendor_item_master im on im.item_number=a.sku 
	where (im.ebay_epids is null or im.ebay_epids='')
	;
	");
//	and a.sku='$test'

while($compat_row = mysqli_fetch_array($result_compat)) {
	$sku = $compat_row['sku'];
	$epids = $compat_row['value'];
	
	$updateSQL = "UPDATE `mojo_vendor_item_master` set ebay_epids='{$epids}' where item_number='{$sku}'";
	mysqli_query($con, $updateSQL); 
}
	
mysqli_close($con);

?>
<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$input_result = mysqli_query($con, "SELECT sa.*, im.*, inv.item_cost, inv.shipping_cost, inv.handling_cost FROM mojo_sku_assignments_single_temp sa inner join mojo_vendor_item_master im on im.item_number=sa.vendor_item and im.vendor='PFG' inner join mojo_vendor_inventory inv on inv.vendor_item_number=sa.vendor_item and inv.warehouse='VA'");
while ($row = mysqli_fetch_array($input_result)) { 
	$sku = $row['sku'];
	mysqli_query($con, "INSERT INTO `mojo_ebaycopy_new_output` (`sku`, `category_ids`, `additional_notes`, `combined_pair_ind`, `compatibility`, `cost`, `shipping_cost`, `handling_cost`, `description`, `ebay_category`, `ebay_store_cat_a`, `ebay_title`, `ebay_upc`, `hollander`, `is_in_stock`, `manufacturer`, `mpn`,`name`, `oemnumber`, `partslink`, `placement_on_vehicle`, `price`, `qty`, `short_description`, `surface_finish`, `vendor`, `vendor_item`) 
	VALUES ('{$sku}', '{$row["magento_category"]}', '{$row["additional_notes"]}', '0', '{$row["compatibility"]}', '{$row["item_cost"]}', '{$row["shipping_cost"]}', '{$row["handling_cost"]}', '{$row["ebay_title"]}', '{$row["ebay_category"]}', '{$row["ebay_store_category"]}', '{$row["ebay_title"]}', '{$row["upc"]}', '{$row["interchange"]}', '0', 'Aftermarket Replacement','{$row["mpn"]}', '{$row["ebay_title"]}', '{$row["oem"]}', '{$row["partslink"]}', '{$row["placement_on_vehicle"]}', '99999.99', '0', '{$row["ebay_title"]}', '{$row["surface_finish"]}', 'PFG', '{$row["item_number"]}')"); 

	$image_result = mysqli_query($con, "SELECT value FROM catalog_product_entity a LEFT JOIN catalog_product_entity_media_gallery b ON a.entity_id = b.entity_id WHERE a.sku = '{$sku}'");
	$magentoImageCount = mysqli_num_rows($image_result);
	if ($magentoImageCount < 3) {
		$mainImageURL = $row['main_image_url'];
		mysqli_query($con, "INSERT INTO `mojo_ebaycopy_pics_output` (`sku`, `image`, `small_image`, `thumbnail`, `media_gallery`) 
		VALUES ('{$sku}','{$mainImageURL}','{$mainImageURL}','{$mainImageURL}','{$row["other_image_urls"]}')"); 
	}
} 

$input_result->close(); 
$image_result->close(); 

echo "Whew, done.".PHP_EOL;
?>

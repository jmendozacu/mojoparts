<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$p1Count = 0;
$p2Count = 0;

// PASS 1 - create new skus that don't exist yet in magento
echo "************** STARTING PASS 1 ***************".PHP_EOL;
$result = mysqli_query($con, "select * from mojo_vendor_item_master im left join catalog_product_entity cpe on cpe.sku=im.item_number where cpe.sku is null and im.item_number is not null;");
$numRows = mysqli_num_rows($result);
$rowNum = 0;
while ($row = mysqli_fetch_array($result)) { 
	$sku = $row['item_number'];
	$vendorItem = $row['vendor_item_number'];
	$side = $row['side'];
	$component1 = $row['component_1'];
	$component2 = $row['component_2'];
	$invRow1 = NULL;
	$invRow2 = NULL;
	$invFound = FALSE;
	$rowNum++;
	echo "1: row ".$rowNum." of ".$numRows." - ".$sku.": ";

	// make sure this is still in the inventory file we get daily from PFG
	if ($side == "L" || $side == "R" || $side == "B" || $side == "N") {
		$invResult = mysqli_query($con, "select * from mojo_vendor_inventory inv where inv.vendor_item_number='{$vendorItem}' and inv.warehouse='VA' limit 1;");
		if (mysqli_num_rows($invResult) == 1) {
			$invRow1 = mysqli_fetch_array($invResult);
			$invFound = TRUE;
			echo "found ";
		} else { echo "NOT "; }
	} else {
		if ($side == "S" && $component1 != NULL && $component2 != NULL) {
			$invResult = mysqli_query($con, "select * from mojo_vendor_inventory inv where inv.warehouse='VA' and (inv.vendor_item_number='{$component1}' or inv.vendor_item_number='{$component2}') limit 2;");
			if (mysqli_num_rows($invResult) == 2) {
				$invRow1 = mysqli_fetch_array($invResult);
				$invRow2 = mysqli_fetch_array($invResult);
				$invFound = TRUE;
				echo "found ";
			} else { echo "NOT "; }
		} else { echo "side==S, component1|2==NULL "; }
	}
	
	if ($invFound) {
		$cost = $invRow1['item_cost'];
		$shippingCost = $invRow1['shipping_cost'];
		$handlingCost = $invRow1['handling_cost'];
		if ($side == "S") {
			$vendorItem = $component1.",".$component2;
			$cost = $invRow1['item_cost'] + $invRow2['item_cost'];
			$shippingCost = max($invRow1['shipping_cost'], $invRow2['shipping_cost']);
			$handlingCost = $invRow1['handling_cost'] + $invRow2['handling_cost'];
		}
		$mainImageURL = $row['main_image_url'];
		$eBayTitle = $row["ebay_title"];
		$compats = $row["compatibility"];
		$readyForEBay = FALSE;
		if ($mainImageURL != NULL && $eBayTitle != null && $compats != null) { $readyForEBay = TRUE; }

		$magentoCategory = $row["magento_category"];
		$additionalNotes = $row["additional_notes"];
		$compatibility = $row["compatibility"];
		$ebayTitle = $row["ebay_title"];
		$ebayCategory = $row["ebay_category"];
		$ebayStoreCategory = $row["ebay_store_category"];
		$upc = $row["upc"];
		$interchange = $row["interchange"];
		$mpn = $row["mpn"];
		$oem = $row["oem"];
		$partslink = $row["partslink"];
		$placement = $row["placement_on_vehicle"];
		$surfaceFinish = $row["surface_finish"];
		$otherImgURLs = $row["other_image_urls"];

		mysqli_query($con, "INSERT INTO `mojo_ebaycopy_new_output` (`sku`, `category_ids`, `additional_notes`, `ebay_epids`, `cost`, `shipping_cost`, `handling_cost`, `description`, `ebay_category`, `ebay_store_cat_a`, `ebay_title`, `ebay_upc`, `hollander`, `is_in_stock`, `manufacturer`, `mpn`,`name`, `oemnumber`, `partslink`, `placement_on_vehicle`, `price`, `qty`, `short_description`, `surface_finish`, `vendor`, `vendor_item`, `has_compat`) 
		VALUES ('{$sku}', '{$magentoCategory}', '{$additionalNotes}', '{$compatibility}', '{$cost}', '{$shippingCost}', '{$handlingCost}', '{$ebayTitle}', '{$ebayCategory}', '{$ebayStoreCategory}', '{$ebayTitle}', '{$upc}', '{$interchange}', '0', 'Aftermarket Replacement','{$mpn}', '{$ebayTitle}', '{$oem}', '{$partslink}', '{$placement}', '99999.99', '0', '{$ebayTitle}', '{$surfaceFinish}', 'PFG', '{$vendorItem}', '{$readyForEBay}')"); 
		$p1Count++;
		if ($mainImageURL != NULL) {
			mysqli_query($con, "INSERT INTO `mojo_ebaycopy_pics_output` (`sku`, `image`, `small_image`, `thumbnail`, `media_gallery`) 
			VALUES ('{$sku}','{$mainImageURL}','{$mainImageURL}','{$mainImageURL}','{$otherImgURLs}')");
		}
	}
	echo PHP_EOL;
}


// PASS 2 - update existing magento skus with new info
echo "************** STARTING PASS 2 ***************".PHP_EOL;
$result = mysqli_query($con, "select * from mojo_vendor_item_master im inner join catalog_product_entity cpe on cpe.sku=im.item_number;");
$numRows = mysqli_num_rows($result);
$rowNum = 0;
while ($row = mysqli_fetch_array($result)) { 
	$sku = $row['item_number'];
	$mainImageURL = $row['main_image_url'];
	$eBayTitle = $row["ebay_title"];
	$compats = $row["compatibility"];
	$magentoCategory = $row["magento_category"];
	$additionalNotes = $row["additional_notes"];
	$ebayCategory = $row["ebay_category"];
	$ebayStoreCategory = $row["ebay_store_category"];
	$upc = $row["upc"];
	$interchange = $row["interchange"];
	$mpn = $row["mpn"];
	$oem = $row["oem"];
	$partslink = $row["partslink"];
	$surfaceFinish = $row["surface_finish"];
	$otherImgURLs = $row["other_image_urls"];
	$reviewCode = "updates-".date("Y.m.d");
	$readyForEBay = FALSE;
	if ($mainImageURL != NULL && $eBayTitle != null && $compats != null) { $readyForEBay = TRUE; }

	$rowNum++;
	echo "2: row ".$rowNum." of ".$numRows." - ".$sku.": ";


	// add images if needed
	$imageResult = mysqli_query($con, "SELECT value FROM catalog_product_entity a LEFT JOIN catalog_product_entity_media_gallery b ON a.entity_id = b.entity_id WHERE a.sku = '{$sku}'");
	$numImages = mysqli_num_rows($imageResult);
	if($numImages <= 1 && $otherImgURLs != NULL) {
		mysqli_query($con, "INSERT INTO `mojo_ebaycopy_pics_output` (`sku`, `image`, `small_image`, `thumbnail`, `media_gallery`) 
		VALUES ('{$sku}','{$mainImageURL}','{$mainImageURL}','{$mainImageURL}','{$otherImgURLs}')");
	}

	// if title, compats, or image has been added, only then update the product
	$prodResult = mysqli_query($con, "select t.value as 'title', c.value as 'compatibility' from catalog_product_entity cpe left join catalog_product_entity_varchar t on t.entity_id=cpe.entity_id left join catalog_product_entity_text c on c.entity_id=cpe.entity_id where t.attribute_id=153 and c.attribute_id=135 and cpe.sku='{$sku}' limit 1");
	if (mysqli_num_rows($prodResult) == 1) {
		$prodRow = mysqli_fetch_array($prodResult);
		$prodEbayTitle = $prodRow["title"];
		$prodCompats = $prodRow["compatibility"];
	}
	if ($eBayTitle != NULL && $compats != NULL) {
// for now for dbp listings, only update if info is completely missing
//		if ($prodEbayTitle != $eBayTitle || $prodCompats != $compats || $numImages <= 1) {
		if ($prodEbayTitle == NULL || $prodEbayTitle == "" || $prodCompats == NULL || $prodCompats == "" || $numImages <= 1) {

// removed the FULL update for now... only updating title, notes, compats
//			mysqli_query($con, "INSERT INTO `mojo_ebaycopy_update_output` (`sku`, `category_ids`, `ebay_title`, `additional_notes`, `compatibility`,  `ebay_category`, `ebay_store_cat_a`, `ebay_upc`, `hollander`, `mpn`, `oemnumber`, `partslink`, `surface_finish`,`qty`,`is_in_stock`,`is_ready_for_ebay`, `review_code`) 
//			VALUES ('{$sku}', '{$magentoCategory}', '{$eBayTitle}','{$additionalNotes}', '{$compats}', '{$ebayCategory}', '{$ebayStoreCategory}', '{$upc}', '{$interchange}', '{$mpn}', '{$oem}', '{$partslink}', '{$surfaceFinish}', '0', '0', '{$readyForEBay}', '{$reviewCode}')"); 
			mysqli_query($con, "INSERT INTO `mojo_ebaycopy_update_output` (`sku`, `ebay_title`, `additional_notes`, `ebay_epids`, `has_compat`, `review_code`) 
			VALUES ('{$sku}', '{$eBayTitle}', '{$additionalNotes}', '{$compats}', '{$readyForEBay}', '{$reviewCode}')"); 

			$p2Count++;
			echo "mismatch on ";
			if ($prodEbayTitle != $eBayTitle) { echo "title "; }
			if ($prodCompats != $compats) { echo "compats "; }
			if ($numImages <= 1) { echo "images "; }
			
		} else { echo "everything matches"; }
	} else { echo "skipping... missing compats or title"; }
	echo PHP_EOL;
}

echo "--------------------------------------------------".PHP_EOL;
echo "1: new items: ".$p1Count.PHP_EOL;
echo "2: updated items: ".$p2Count.PHP_EOL;

?>

<?php
error_reporting(E_ALL);

echo "INFO: Initializing the connections".PHP_EOL;
$magento_con = mysqli_init();
$mojo_con = mysqli_init();
if (!$magento_con || !$mojo_con) {
    echo "ERROR: Failed to initialize the mysql objects.";
    exit(1);
}
mysqli_real_connect($magento_con, 'mojomysql2.c6orzbehh7d1.us-east-1.rds.amazonaws.com','mojo','3^-4Grj,;pF7[3kN','mojomagento');
if (!$magento_con) {
    echo "ERROR: Cannot connect to magento database.";
    exit(1);
}
	
// clear the vendor_mismatch work table
$query = "DELETE from mojo.vendor_mismatches_work";
mysqli_query($magento_con, $query);

$csvName = '/var/www/html/var/import/partnumFix.csv';
$csv = fopen($csvName, "w");
fputcsv($csv, array('code','item_number','vendor','vendor_item_number','note','action1','action2','action3','action4'));

$loopQuery = "select a.base_sku from (
	select substring(cpe.sku,1,length(cpe.sku)-2) as 'base_sku'
	from catalog_product_entity cpe
	inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
	where cpe.sku like '%LR'
	union distinct
	select substring(cpe.sku,1,length(cpe.sku)-1) as 'base_sku'
	from catalog_product_entity cpe
	inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
	where (cpe.sku like '%L'
	or cpe.sku like '%R'
	or cpe.sku like '%B')
	and cpe.sku not like '%LR'
	) a
	order by a.base_sku;";
$loopResult = mysqli_query($magento_con, $loopQuery);
if (!$loopResult) {
	echo "ERROR: ".$loopQuery.PHP_EOL;
	exit(1);
}
echo "Number of rows in main loop: ".mysqli_num_rows($loopResult).PHP_EOL;

	
while ($loopRow = mysqli_fetch_array($loopResult)) { 
	$itemArray = array();
	$baseItemNumber = $loopRow['base_sku'];
	$continue = TRUE;
	
	$query = "select cpe.sku, v.value as 'vendor', vi.value as 'vendor_item_number'
		from catalog_product_entity cpe
		inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
		inner join catalog_product_entity_varchar vi on cpe.entity_id = vi.entity_id and vi.attribute_id = 164
		inner join catalog_product_entity_int v on cpe.entity_id = v.entity_id and v.attribute_id = 163
		where cpe.sku like '{$baseItemNumber}%';";
	$result = mysqli_query($magento_con, $query);
	while ($row = mysqli_fetch_array($result)) {
		$sku = $row['sku'];
		$vendor = ($row['vendor'] == 36) ? 'PFG' : 'Brock';
		$vendorItemNumber = $row['vendor_item_number'];
		preg_match('/([A-Z0-9\-]*[0-9])(LR|L|R|B|$)/', $sku, $matches); // this removes the L|R|LR|B from the end of the item number 
		$suffix = $matches[2];
		$itemArray[$suffix] = array($sku, $vendor, $vendorItemNumber);
	}

//*******************************************************************************************************
// PASS 1
//*******************************************************************************************************
	// make sure they all have the same vendor
	$baseVendor = NULL;
	foreach($itemArray as $side => $item) {
		if ($baseVendor == NULL) $baseVendor = $item[1]; 
		else if ($item[1] == $baseVendor) $continue = TRUE;
		else $continue = FALSE;
	}
	if ($continue == FALSE) {
		fputcsv($csv, array('ERROR', $baseItemNumber, $baseVendor, '', 'Mismatched vendor.', 'Manually investigate and fix by running fixVendorMismatch.php to generate fixVendorMistmatch.csv.'));

		$query = "SELECT cpe.sku, v.value as 'vendor', vi.value as 'vendor_item_number', c.is_in_stock, p.value as 'price', cost.value as 'item_cost', ship.value as 'shipping_cost', hand.value as 'handling_cost', mkp.value as 'markup_pct'
		from catalog_product_entity cpe
		inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
		inner join catalog_product_entity_varchar vi on cpe.entity_id = vi.entity_id and vi.attribute_id = 164
		inner join catalog_product_entity_int v on cpe.entity_id = v.entity_id and v.attribute_id = 163
		inner join cataloginventory_stock_item c ON cpe.entity_id = c.product_id
		inner join catalog_product_entity_decimal p ON cpe.entity_id = p.entity_id and p.attribute_id=75
		left join catalog_product_entity_decimal cost ON cpe.entity_id = cost.entity_id and cost.attribute_id=79
		left join catalog_product_entity_decimal ship ON cpe.entity_id = ship.entity_id and ship.attribute_id=161
		left join catalog_product_entity_decimal hand ON cpe.entity_id = hand.entity_id and hand.attribute_id=159
		left join catalog_product_entity_varchar mkp ON cpe.entity_id = mkp.entity_id and mkp.attribute_id=189
		where cpe.sku LIKE '{$baseItemNumber}%'";
		$result = mysqli_query($magento_con, $query);
		while ($row = mysqli_fetch_array($result)) {
			$wSku = $row['sku'];
			$wVendor = $row['vendor'];
			$wVendorItem = $row['vendor_item_number'];
			$wPrice = ($row['price'] == NULL) ? 0 : $row['price'];
			$wItemCost = ($row['item_cost'] == NULL) ? 0 : $row['item_cost'];
			$wShippingCost = ($row['shipping_cost'] == NULL) ? 0 : $row['shipping_cost'];
			$wHandlingCost = ($row['handling_cost'] == NULL) ? 0 : $row['handling_cost'];
			$wMarkupPct = ($row['markup_pct'] == NULL) ? 0 : $row['markup_pct'];
			$wQuery = "INSERT INTO mojo.vendor_mismatches_work (sku, vendor, vendor_item, is_in_stock, price, item_cost, shipping_cost, handling_cost, markup_pct) 
			VALUES ('{$wSku}', '{$wVendor}', '{$wVendorItem}', {$row['is_in_stock']}, {$wPrice}, {$wItemCost}, {$wShippingCost}, {$wHandlingCost}, {$wMarkupPct});";
			mysqli_query($magento_con, $wQuery);
		}
	}

	// make sure PFG items are present in the mojo_vendor_item_master
	if ($continue == TRUE) {
		if ($baseVendor == 'PFG') {
//			foreach($itemArray as $side => $item) {
				$query = "SELECT im.item_number FROM mojo_vendor_item_master im
				WHERE im.item_number like '{$baseItemNumber}%'";
				$result = mysqli_query($magento_con, $query);
				if (mysqli_num_rows($result) == 0) {
					$continue = FALSE;
					fputcsv($csv, array('ERROR', $baseItemNumber, $baseVendor, '', 'No sides of base item# were found in mojo_vendor_item_master.', ''));
//					echo "No sides of base item# ".$baseItemNumber." were found in mojo_vendor_item_master.".PHP_EOL;
				}
				else if (mysqli_num_rows($result) <> count($itemArray)) {
					$continue = FALSE;
//					echo "One or more sides of base item# ".$baseItemNumber." is not found in mojo_vendor_item_master.".PHP_EOL;
					fputcsv($csv, array('ERROR', $baseItemNumber, $baseVendor, '', 'One or more sides of base item# is not found in mojo_vendor_item_master.', ''));
				}
//			}
		}
	}
	
	
	
	
//*******************************************************************************************************
// PASS 2
//*******************************************************************************************************
	
//*******************************************************************************************************
// PASS 3
//*******************************************************************************************************
/*	if ($continue == TRUE) {
		// LH is in magento, RH isn't
		if ($skuL!=NULL && $skuR==NULL) {
			if ($vendorL == 'Brock') {
				fputcsv($csv, array("ERROR", $skuL, $vendorL, $vendorItemL, "Missing RH for Brock Item.","use magmi to deactivate magento sku & pair", "delete from mojo_vendor_item_master where item_number like '{$baseItemNumber}%';", "delete from mojo_vendor_inventory where item_number like '{$baseItemNumber}%';","manually remove all disabled skus from listing groups"));
			} 	else {
				$query = "select description_scrubbed 
					from mojo_vendor_item_master im
					where im.vendor='PFG' 
					and im.item_number='{$skuL}'
					and im.description_scrubbed is not null;";
				$result = mysqli_query($magento_con, $query);
				if ($result && mysqli_num_rows($result) == 1) {
					$row = mysqli_fetch_array($result);
					$descScrubL = $row['description_scrubbed'];
					// next lookup RH by description_scrubbed
				}
				else {
					fputcsv($csv, array('ERROR', $skuL, $vendorL, $vendorItemL, 'No vendor item master record found for PFG LH part.', 'DEACTIVATE magento sku.'));
				}
			}
		}

		// RH is in magento, LH isn't
		if ($skuR!=NULL && $skuL==NULL) {
			if ($vendorR == 'Brock') {
				fputcsv($csv, array("ERROR", $skuR, $vendorR, $vendorItemR, "Missing LH for Brock Item.","use magmi to deactivate magento sku & pair", "delete from mojo_vendor_item_master where item_number like '{$baseItemNumber}%';", "delete from mojo_vendor_inventory where item_number like '{$baseItemNumber}%';","manually remove all disabled skus from listing groups"));
			} 	else {
				$query = "select description_scrubbed 
					from mojo_vendor_item_master im
					where im.vendor='PFG' 
					and im.item_number='{$skuR}'
					and im.description_scrubbed is not null;";
				$result = mysqli_query($magento_con, $query);
				if ($result && mysqli_num_rows($result) == 1) {
					$row = mysqli_fetch_array($result);
					$descScrubR = $row['description_scrubbed'];
					// next lookup LH by description_scrubbed
				}
				else {
					fputcsv($csv, array('ERROR', $skuR, $vendorR, $vendorItemR, 'No vendor item master record found for PFG RH part.', 'DEACTIVATE magento sku.'));
				}
			}
		}

		// LR is in magento, LH or RH or B isn't
		if ($skuLR!=NULL && (($skuB==NULL && $skuL==NULL && $skuR==NULL) ||($skuB==NULL && ($skuL==NULL || $skuR==NULL)))) {
			if ($vendorLR == 'Brock') {
				fputcsv($csv, array("ERROR", $skuLR, $vendorLR, $vendorItemLR, "Missing L/R or B for Brock Pair.","use magmi to deactivate magento pair and associated side", "delete from mojo_vendor_item_master where item_number like '{$baseItemNumber}%';", "delete from mojo_vendor_inventory where item_number like '{$baseItemNumber}%';","manually remove all disabled skus from listing groups"));
			} 	else {
				$query = "select description_scrubbed 
					from mojo_vendor_item_master im
					where im.vendor='PFG' 
					and im.item_number='{$skuLR}'
					and im.description_scrubbed is not null;";
				$result = mysqli_query($magento_con, $query);
				if ($result && mysqli_num_rows($result) == 1) {
					$row = mysqli_fetch_array($result);
					// next - ??
				}
				else {
					fputcsv($csv, array('ERROR', $skuLR, $vendorLR, $vendorItemLR, 'No vendor item master record found for PFG LR part.', 'DEACTIVATE magento sku.'));
				}
			}
		}

		// B is in magento, LR isn't
		if ($skuB!=NULL && $skuLR==NULL) {
			if ($vendorB == 'Brock') {
				// I'm not concerned about this situation.  It's too hard to get a picture & compats to create the LR for a brock item.
			} else {
				$query = "select description_scrubbed 
					from mojo_vendor_item_master im
					where im.vendor='PFG' 
					and im.item_number='{$skuB}'
					and im.description_scrubbed is not null;";
				$result = mysqli_query($magento_con, $query);
				if ($result && mysqli_num_rows($result) == 1) {
					$row = mysqli_fetch_array($result);
					// next - ??
				}
				else {
					fputcsv($csv, array('ERROR', $skuB, $vendorB, $vendorItemB, 'No vendor item master record found for PFG B part.', 'DEACTIVATE magento sku.'));
				}
			}
		}

		// L&R is in magento, LR isn't
		if ($skuL!=NULL && $skuR!=NULL && $skuLR==NULL) {
			// @todo 
		}
	}
*/
}

// close the connections
echo "INFO: Closing the connections".PHP_EOL;
$magento_con->close();
$mojo_con->close();

echo "INFO: Script complete".PHP_EOL;
?>

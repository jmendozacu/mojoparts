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
	$baseItemNumber = $loopRow['base_sku'];
	$skuL = NULL;
	$descScrubL = NULL;
	$vendorL = NULL;
	$vendorItemL = NULL;
	$skuR = NULL;
	$descScrubR = NULL;
	$vendorR = NULL;
	$vendorItemR = NULL;
	$skuB = NULL;
	$descScrubB = NULL;
	$vendorB = NULL;
	$vendorItemB = NULL;
	$skuLR = NULL;
	$descScrubLR = NULL;
	$vendorLR = NULL;
	$vendorItemLR = NULL;
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
		$vendor_item_number = $row['vendor_item_number'];
		preg_match('/([A-Z0-9\-]*[0-9])(LR|L|R|B|$)/', $sku, $matches); // this removes the L|R|LR|B from the end of the item number 
		$suffix = $matches[2];
		switch ($suffix) {
			case "L": 
				$skuL = $sku;
				$vendorL = $vendor;
				$vendorItemL = $vendor_item_number;
				break;
			case "R": 
				$skuR = $sku;
				$vendorR = $vendor;
				$vendorItemR = $vendor_item_number;
				break;
			case "B": 
				$skuB = $sku;
				$vendorB = $vendor;
				$vendorItemB = $vendor_item_number;
				break;
			case "LR": 
				$skuLR = $sku;
				$vendorLR = $vendor;
				$vendorItemLR = $vendor_item_number;
				break;
		}
	}
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
		// @todo - actually, the loop query doesn't include LRs, so I need to look at this again.
	}
}

// close the connections
echo "INFO: Closing the connections".PHP_EOL;
$magento_con->close();
$mojo_con->close();

echo "INFO: Script complete".PHP_EOL;
?>

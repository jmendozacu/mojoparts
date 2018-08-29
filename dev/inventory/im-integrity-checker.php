<?php

// @TODO: UNCOMMENT THE BASIC SANITY CHECK QUERIES

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

$imIntegrityCSVName = '/var/www/html/var/import/im-integrity.csv';
$imIntegrityCSV = fopen($imIntegrityCSVName, "w");
fputcsv($imIntegrityCSV, array('code','item_number','vendor','vendor_item_number','note'));

//****************************************************************************************************
// basic sanity checks
//****************************************************************************************************

/*
// check for mispriced CJANO listings
echo "INFO: Checking for any mispriced CJANO listings...".PHP_EOL;
$query = "select elp.online_sku, elp.online_current_price, p.value as 'price', abs(p.value-elp.online_current_price)/p.value*100 as 'pct diff'
from m2epro_ebay_listing_product elp
inner join m2epro_listing_product lp on lp.id=elp.listing_product_id and lp.`status`=2
inner join catalog_product_entity cpe on elp.online_sku=cpe.sku
inner join catalog_product_entity_decimal p on p.entity_id=cpe.entity_id and p.attribute_id=204
inner join catalog_product_entity_varchar f on f.entity_id=cpe.entity_id and f.attribute_id=202 and f.value=178
where abs(p.value-elp.online_current_price)/p.value>0.005
and (lp.listing_id=40 or lp.listing_id=42);";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	while ($row = mysqli_fetch_array($result)) { 
		$array = array('ERROR', $row['online_sku'], '', '', 'CJANO pricing error: price in eBay='.$row['online_current_price'].', price in magento='.$row['price'].'.');
		fputcsv($imIntegrityCSV, $array);
	}
	echo "ERROR: Found pricing errors for CJANO. Please check im-integrity.csv.".PHP_EOL;
	exit(1);
}
echo "INFO: COMPLETE".PHP_EOL;


// check for mispriced mojo parts listings
echo "INFO: Checking for any mispriced Mojo Parts listings...".PHP_EOL;
$query = "select elp.online_sku, elp.online_current_price, p.value as 'price', abs(p.value-elp.online_current_price)/p.value*100 as 'pct diff'
from m2epro_ebay_listing_product elp
inner join m2epro_listing_product lp on lp.id=elp.listing_product_id and lp.`status`=2
inner join catalog_product_entity cpe on elp.online_sku=cpe.sku
inner join catalog_product_entity_decimal p on p.entity_id=cpe.entity_id and p.attribute_id=75
where abs(p.value-elp.online_current_price)/p.value>0.005
and (lp.listing_id<>40 and lp.listing_id<>42);";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	while ($row = mysqli_fetch_array($result)) { 
		$array = array('ERROR', $row['online_sku'], '', '', 'Mojo Parts pricing error: price in eBay='.$row['online_current_price'].', price in magento='.$row['price'].'.');
		fputcsv($imIntegrityCSV, $array);
	}
	echo "ERROR: Found pricing errors for Mojo Parts. Please check im-integrity.csv.".PHP_EOL;
	exit(1);
}
echo "INFO: COMPLETE".PHP_EOL;

// check for duplicate item assignments in magento
echo "INFO: Checking for vendor items assigned to more than one sku in magento...".PHP_EOL;
$query = "select cpe2.sku, b2.value as 'vendor_item' 
from catalog_product_entity_varchar b2
inner join catalog_product_entity cpe2 on cpe2.entity_id=b2.entity_id
where b2.value in
(select b.value
from catalog_product_entity_varchar b
inner join catalog_product_entity cpe on cpe.entity_id=b.entity_id
inner join catalog_product_entity_int sts on sts.entity_id=cpe.entity_id and sts.attribute_id=96 and sts.value=1
where b.attribute_id = 164
group by b.value
having count(*)>1)
order by b2.value;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	while ($row = mysqli_fetch_array($result)) { 
		$array = array('ERROR', '', '', $row['vendor_item'], 'Found more than one sku that had this vendor item assigned in magento.');
		fputcsv($imIntegrityCSV, $array);
	}
	echo "ERROR: Found duplicate vendor sku assignments in magento. Please check im-integrity.csv.".PHP_EOL;
	exit(1);
}
echo "INFO: COMPLETE".PHP_EOL;


// check for duplicate vendor item assignments in mojo_vendor_inventory
echo "INFO: Checking for vendor items assigned to more than one sku in mojo_vendor_inventory...".PHP_EOL;
$query = "select mvi.vendor_item_number, count(*)
FROM mojo_vendor_inventory mvi
where mvi.item_number is not null and mvi.item_number <> ''
group by mvi.vendor_item_number
having count(*)>3;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	while ($row = mysqli_fetch_array($result)) { 
		$array = array('ERROR', '', '', $row['vendor_item_number'], 'Found more than one sku that had this vendor item assigned in mojo_vendor_inventory.');
		fputcsv($imIntegrityCSV, $array);
	}
	echo "ERROR: Found duplicate vendor sku assignments in mojo_vendor_inventory. Please check im-integrity.csv.".PHP_EOL;
	exit(1);
}
echo "INFO: COMPLETE".PHP_EOL;

// check for duplicate vendor item assignments in mojo_vendor_item_master
echo "INFO: Checking for vendor items assigned to more than one sku in mojo_vendor_item_master...".PHP_EOL;
$query = "select im.item_number, count(*)
FROM mojo_vendor_item_master im
where im.item_number is not null and im.item_number <> ''
group by im.item_number
having count(*)>1;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	while ($row = mysqli_fetch_array($result)) { 
		$array = array('ERROR', $row['item_number'], '','', 'Found more than one vendor item that had this item assigned in mojo_vendor_item_master.');
		fputcsv($imIntegrityCSV, $array);
	}
	echo "ERROR: Found duplicate vendor sku assignments in mojo_vendor_item_master. Please check im-integrity.csv.".PHP_EOL;
	exit(1);
}
echo "INFO: COMPLETE".PHP_EOL;


// check for pairs in item master without both sides in vendor item
echo "INFO: Checking for pairs in mojo_vendor_item_master where both sides are not specified in component_*...".PHP_EOL;
$query = "select im.item_number, count(*)
FROM mojo_vendor_item_master im
where im.item_number like '%%LR'
and (im.component_1 ='' or im.component_1 is null or im.component_2 = ''or im.component_2 is null)
group by im.item_number
having count(*)>1;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	while ($row = mysqli_fetch_array($result)) { 
		$array = array('ERROR', $row['item_number'], '','', 'Found a pair that was missing one or more component side in mojo_vendor_item_master.');
		fputcsv($imIntegrityCSV, $array);
	}
	echo "ERROR: Found a pair that was missing one or more component side in mojo_vendor_item_master. Please check im-integrity.csv.".PHP_EOL;
	exit(1);
}
echo "INFO: COMPLETE".PHP_EOL;
*/

//****************************************************************************************************
// mainline
//****************************************************************************************************
$loopquery = "select cpe.sku, v.value as 'vendor', vi.value as 'vendor_item_number'
from catalog_product_entity cpe
inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
inner join catalog_product_entity_varchar vi on cpe.entity_id = vi.entity_id and vi.attribute_id = 164
inner join catalog_product_entity_int v on cpe.entity_id = v.entity_id and v.attribute_id = 163;";
$loopresult = mysqli_query($magento_con, $loopquery);
if (!$loopresult) {
	echo "ERROR: ".$loopquery.PHP_EOL;
	exit(1);
}
while ($row = mysqli_fetch_array($loopresult)) { 
	$item_number = $row['sku'];
	$vendor = ($row['vendor'] == 36) ? 'PFG' : 'Brock';
	$vendor_item_number = $row['vendor_item_number'];

	$matches = array();
	$base_item_number = $item_number; // initialize this... but next, we'll remove the side if there is one
	if (preg_match('/([A-Z0-9\-]*[0-9])(LR|L|R|B|$)/', $item_number, $matches)) { // this removes the L|R|LR|B from the end of the item number 
		$base_item_number = $matches[1];
		$suffix = $matches[2];
//		echo "INFO: base item number for ".$vendor."|".$vendor_item_number." - ".$item_number." is ".$base_item_number."... ";
	
		# SINGLES first
		#######################
		if ($suffix <> 'LR') {
//			echo "it's a single.".PHP_EOL;

		// validate internally first
			if ($vendor == 'PFG' && strpos($vendor_item_number, ',') !== false) {
				echo "ERROR: Since it's not a pair, there shouldn't be any comma in the PFG item#.".PHP_EOL; 
				$array = array('ERROR', $item_number, $vendor, $vendor_item_number, 'Missing comma in PFG item number for pair.');
				fputcsv($imIntegrityCSV, $array);
			} else if ($vendor == 'Brock') {
				if (preg_match('/([A-Za-z0-9\-]*LR)/', $vendor_item_number, $matches)) { 
					echo "ERROR: Since it's not a pair, the Brock item# shouldn't end in LR.".PHP_EOL; 
					$array = array('ERROR', $item_number, $vendor, $vendor_item_number, 'Since it is not a pair, the Brock item# shouldn NOT end in LR.');
					fputcsv($imIntegrityCSV, $array);
				}
			}
			
			# validate against other magento skus
			#####################
			else if ($suffix == "L" || $suffix == "R") {
				$opposite_item_number = ($suffix == "L") ? $base_item_number."R" : $base_item_number."L";
				$query = "select cpe.sku, v.value as 'vendor', vi.value as 'vendor_item_number'
				from catalog_product_entity cpe
				inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
				inner join catalog_product_entity_varchar vi on cpe.entity_id = vi.entity_id and vi.attribute_id = 164
				inner join catalog_product_entity_int v on cpe.entity_id = v.entity_id and v.attribute_id = 163
				where cpe.sku = '{$opposite_item_number}'";
				$result = mysqli_query($magento_con, $query);
				if ($result && mysqli_num_rows($result) == 1) {
						// @TODO: continue logic here
				} else {
						echo "ERROR: No matching opposite side for ".$item_number.".".PHP_EOL; 
						$array = array('ERROR', $item_number, $vendor, $vendor_item_number, 'There is no matching other side for item# '.$item_number.'.');
						fputcsv($imIntegrityCSV, $array);
				}

/*					else
						if the other side's vendor item# is off by more than 1 character
							error
				if it is L/R/B
					find the matching LR
						if it doesn't have a matching LR
							error
						if it is L/R
							do the vendor item#s match between the LR and the L/R?
						else if it is B
							does the vendor item# match between the LR and the B?
							
				
			# validate against vendor inventory
			#####################
			if there were no errors...
				find matching sku in inventory based on vendor & vendor item#
				if a match was found
					if mojo item# doesn't match
						error (mojo item# should be the same between magento & inventory, based on vendor item#)
				else (no match found in inv)
					find matching sku in inventory based on MOJO item#
					if a match was found
						error (the mojo item# is assigned to the wrong vendor item)
					
			# validate against item master
			#####################
			if there were no errors...
				find matching item in the item master based on vendor & vendor item#
				if a match was found
					if mojo item# doesn't match
						error (mojo item# should be the same between magento & item master, based on vendor item#)
					if the side assignment is not consistent with what is in magento
						error
					if component_1 or component_2 are not empty
						error (these should be empty for non-pairs)				
				else (no match found in item master)
					find matching sku in item master based on MOJO item#
					if a match was found
						error (the mojo item# is assigned to the wrong vendor item)
	*/
			}
		} else { // it's a pair
//			echo "it's a pair.".PHP_EOL;
/*	else (it's a pair)
	#############################################################################################
		break the vendor item# into it's components 1 & 2
		mark whether it's a pair of L&R or B&B
		
		# validate internally
		#####################
		if vendor = PFG && vendor item# DOESN'T has a comma
			error (since it's not a pair, there should be any comma in the vendor item#) 
		if vendor = Brock && vendor item# DOESN'T ends with LR
			error (vendor item shouldn't be a pair)
		(NOTE: if it ends with LR, it SHOULD be a pair.  if it an item pair on the vendor side (one item), then it should not end with LR)
		do the L&R components have more than 1 character difference?
			is the difference between the L&R components only more than 1 integer (if the part that is different is numeric)
				error (the L&R vendor item#s don't seem to go together)
	
		# validate against other magento skus
		#####################
		if there were no errors...
			find the matching L&R or B based on the base magento item#
			if it does not have a matching L & R, or B
				error
			if the matching L & Rs, or B items have mismatched vendor item#s
				error
				
			
		# validate against vendor inventory
		#####################
		- nothing to do here since pairs aren't in inventory
		- anything that's a problem should be handled by checking the components
				
		# validate against item master
		#####################
		if there were no errors...
			find matching item in the item master based on COMPONENT vendor & vendor item#
			if a match was found
				if mojo item# doesn't match
					error (mojo item# should be the same between magento & item master, based on vendor item#)
				if the side assignment is not consistent with what is in magento
					error
			else (no match found in item master)
				find matching sku in item master based on MOJO item#
				if a match was found
					error (the mojo item# is assigned to the wrong vendor item)
*/
		}
	} else { 
		echo "ERROR: Bad item_number format: ".$item_number.PHP_EOL;
		$array = array('ERROR', $item_number, '','', 'Bad item# format');
		fputcsv($imIntegrityCSV, $array);
	}
}

// close the connections
echo "INFO: Closing the connections".PHP_EOL;
$magento_con->close();
$mojo_con->close();

echo "INFO: Script complete".PHP_EOL;
?>

<?php
error_reporting(E_ALL);
require_once dirname(__FILE__).'/excel_reader2.php';

function grab_attachment($mBox, $search) {
	$messages = imap_search($mBox, 'UNSEEN SUBJECT "'.$search.'"');

	if($messages == false) echo "not found :(".PHP_EOL;
	else {
		foreach ($messages as $muid) {
			/* get information specific to this email */
			$overview = imap_fetch_overview($mBox,$muid,0);
			$message = imap_fetchbody($mBox,$muid,1);
			$structure = imap_fetchstructure($mBox,$muid);
			$attachments = array();

			if(isset($structure->parts) && count($structure->parts)) {
				for($i = 0; $i < count($structure->parts); $i++) {
					$attachments[$i] = array(
					  'is_attachment' => false,
					  'filename' => '',
					  'name' => '',
					  'attachment' => '');

					if($structure->parts[$i]->ifdparameters) {
						foreach($structure->parts[$i]->dparameters as $object) {
							if(strtolower($object->attribute) == 'filename') {
								$attachments[$i]['is_attachment'] = true;
								$attachments[$i]['filename'] = $object->value;
							}
						}
					}

					if($structure->parts[$i]->ifparameters) {
						foreach($structure->parts[$i]->parameters as $object) {
							if(strtolower($object->attribute) == 'name') {
								$attachments[$i]['is_attachment'] = true;
								$attachments[$i]['name'] = $object->value;
							}
						}
					}

					if($attachments[$i]['is_attachment']) {
						$attachments[$i]['attachment'] = imap_fetchbody($mBox, $muid, $i+1);
						if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
							$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
						}
						elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
							$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
						}
					}             
				} // for($i = 0; $i < count($structure->parts); $i++)
			} // if(isset($structure->parts) && count($structure->parts))


			if(count($attachments)!=0){
				foreach($attachments as $at){
					if($at['is_attachment']==1){
						file_put_contents('/var/www/html/var/import/'.$at['name'], $at['attachment']);
					}
				}
			}
			
			// mark it as read
			imap_setflag_full($mBox, $muid, '\\Seen', ST_UID);
		}
	}
}


//**********************************************************************************
//  START OF MAINLINE 
//**********************************************************************************
echo "...initialize the connections".PHP_EOL;
$magento_con = mysqli_init();
$mojo_con = mysqli_init();
if (!$magento_con || !$mojo_con) {
    echo "Failed to initialize the mysql objects.";
    exit(1);
}
// TODO: use an included config file for db connection.  The hard-coded connection was removed for better security.
// mysqli_real_connect($con, '$server','$user','$passord','$magento-database');
if (!$magento_con) {
    echo "Cannot connect to magento database.";
    exit(1);
}
// TODO: use an included config file for db connection.  The hard-coded connection was removed for better security.
// mysqli_real_connect($con, '$server','$user','$passord','$mojo-database');
if (!$mojo_con) {
    echo "Cannot connect to mojo database.";
    exit(1);
}

// Process Brock inventory emails
$mBox = imap_open('{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX','ryan.hand@mojoparts.com','L3tm31ngm41l');
if ($mBox === false) throw new Exception('Unable to connect to mailbox. '.imap_last_error());

grab_attachment($mBox, "Brock Supply - Stock Availability Notification");
grab_attachment($mBox, "Brock Supply - Price Change Notification");

// Read the Brock stock file and update the vendor inventory table
$files = glob("/var/www/html/var/import/stockreport_*.xls");
if (count($files) != 1) {
	echo "ERROR: today's brock inventory file not found".PHP_EOL;
} 
else {
	$data = new Spreadsheet_Excel_Reader($files[0]);
	if ($data->rowcount(0)==0) {
		echo "ERROR: No Rows in brock stock report file.".PHP_EOL;
	}
	else {
		$bsCSVName = '/var/www/html/var/import/brock-stock.csv';
		$bsCSV = fopen($bsCSVName, "w");
		fputcsv($bsCSV, array('sku','is_in_stock','qty'));
		for ($i=2;$i<=$data->rowcount(0);$i++) {
			$itemID = $data->value($i, 1, 0);
			$interchange = $data->value($i, 2, 0);
			$partslink = $data->value($i, 3, 0);
			$oem = $data->value($i, 4, 0);
			$stockLevel = $data->value($i, 5, 0);
			$eta = $data->value($i, 6, 0);
			$description = str_replace(array('\'', '"'),'',$data->value($i, 9, 0));
			$is_in_stock = 1;
			$qty = 25; 
			$itemStatus = "enabled";
			if ($stockLevel != "Yes") {
				$is_in_stock=0;
				$qty = 0;
				if ($stockLevel == "DISC") {
					$itemStatus = "disabled";
				}
			}
			$eta = $data->value($i, 6, 0);
			$query = "update mojo_vendor_inventory set interchange='{$interchange}', partslink='{$partslink}', oem='{$oem}', qty={$qty}, item_status='{$itemStatus}', est_restock_date=DATE_FORMAT(STR_TO_DATE('{$eta}', '%m/%d/%Y'), '%Y-%m-%d'), description='{$description}', update_date=curdate() where vendor_item_number='{$itemID}' and vendor='brock' and warehouse='';";
//echo "QUERY: ".$query.PHP_EOL;
			if (!mysqli_query($magento_con, $query)) {
				echo "ERROR: Brock stock update: ".$query.PHP_EOL;
			}
			else {
				$query = "select sku 
					from catalog_product_entity cpe
					inner join catalog_product_entity_varchar vs on vs.entity_id=cpe.entity_id and vs.attribute_id=164
					inner join catalog_product_entity_int sts on sts.entity_id = cpe.entity_id and sts.attribute_id=96 and sts.value=1
					inner join catalog_product_entity_int v on v.entity_id = cpe.entity_id and v.attribute_id = 163 and v.value=37
					where vs.value='{$itemID}';";
				$result = mysqli_query($magento_con, $query);
				if ($result) {
					if (mysqli_num_rows($result)) {
						while ($row = mysqli_fetch_array($result)) { 
							$sku = $row['sku'];
							$array = array($sku, $is_in_stock, $qty);
							fputcsv($bsCSV, $array);
						}

						// update the pair, if any
						$lastChar = substr($sku, -1);
						if ($lastChar == 'L' || $lastChar == 'R' || $lastChar == 'B') { 
							$baseSku = rtrim($sku,'RLB');
							$pairSku = $baseSku."LR";
							if ($is_in_stock == 0 || $lastChar == 'B') {
								$array = array($pairSku, $is_in_stock, $qty);
								fputcsv($bsCSV, $array);
							}
							else {
								$otherSku = NULL;
								if ($lastChar == 'L') $otherSku = $baseSku.'R';
								else $otherSku = $baseSku.'L';

								$query = "select sku 
									from catalog_product_entity cpe
									inner join catalog_product_entity_int sts on sts.entity_id = cpe.entity_id and sts.attribute_id=96 and sts.value=1
									where cpe.sku='{$otherSku}';";
								$result = mysqli_query($magento_con, $query);
								if ($result) {
									$array = array($pairSku, $is_in_stock, $qty);
									fputcsv($bsCSV, $array);
								} else {
									$array = array($pairSku, 0, 0);
									fputcsv($bsCSV, $array);
								}
							}
						}
					}
				}
			}
		}
		unset($data);
		unlink($files[0]);
	}
}

// Read the Brock price file and update the vendor inventory table
$files = glob("/var/www/html/var/import/pricing_*.xls");
if (count($files) != 1) {
	echo "INFO: today's brock price file not found".PHP_EOL;
} 
else {
	$data = new Spreadsheet_Excel_Reader($files[0]);
	if ($data->rowcount(0)==0) {
		echo "ERROR: No Rows in brock price file.".PHP_EOL;
	}
	else {
		$bpCSVName = '/var/www/html/var/import/brock-price.csv';
		$bpCSV = fopen($bpCSVName, "w");
		fputcsv($bpCSV, array('sku','cost','price'));
		for ($i=2;$i<=$data->rowcount(0);$i++) {
			$itemID = $data->value($i, 1, 0);
			$newCost = $data->value($i, 3, 0);
			$query = "update mojo_vendor_inventory set item_cost={$newCost}, update_date=curdate() where vendor_item_number='{$itemID}' and vendor='brock' and warehouse='';";
			if (!mysqli_query($magento_con, $query)) {
				echo "ERROR: Brock price update: ".$query.PHP_EOL;
			}
			else {
				$query = "select sku, p.value as 'old_price'
					from catalog_product_entity cpe
					inner join catalog_product_entity_decimal p on p.entity_id=cpe.entity_id and p.attribute_id=75
					inner join catalog_product_entity_varchar vs on vs.entity_id=cpe.entity_id and vs.attribute_id=164
					inner join catalog_product_entity_int sts on sts.entity_id = cpe.entity_id and sts.attribute_id=96 and sts.value=1
					inner join catalog_product_entity_int v on v.entity_id = cpe.entity_id and v.attribute_id = 163 and v.value=37
					where vs.value='{$itemID}';";
				$result = mysqli_query($magento_con, $query);
				if ($result) {
					if (mysqli_num_rows($result)) {
						while ($row = mysqli_fetch_array($result)) { 
							$sku = $row['sku'];
							$oldPrice = $row['old_price'];
							// calc the new price and see if it's different
							$shippingCost = 0;
							if ($newCost < 40) { // when the cost < $40, brock charges shipping
								$query = "select weight 
									from mojo_vendor_inventory mvi
									where mvi.item_number='{$sku}';";
								$result = mysqli_query($magento_con, $query);
								if ($result) {
									if (mysqli_num_rows($result) == 1) {
										$row = mysqli_fetch_array($result);
										$weight = $row['weight'] + 2;
										if ($weight > 5) $shippingCost = $weight * 2;
										else $shippingCost = $weight + 7;
										if (40-$newCost < $shippingCost) $shippingCost = 40-$newCost; // only add the amount needed to get to 40, if just short
									}
								}
							}

							$newTotalCost = $newCost + $shippingCost;

							$newPrice = round(($newTotalCost + 0.3) / (0.8785 - 0.15), 2); 
							$calcedMinPrice = round(($newTotalCost + 0.3 + 10) / 0.8785, 2); // min profit is $10 for brock
							if ($calcedMinPrice > $newPrice) $newPrice = $calcedMinPrice;

							if ($newPrice != $oldPrice) {	
								$array = array($sku, $newCost, $newPrice);
								fputcsv($bpCSV, $array);
							}

							$pairSku = NULL;
							$lastChar = substr($sku, -1);
							if ($lastChar == 'L' || $lastChar == 'R' || $lastChar == 'B') { 
								$baseSku = rtrim($sku,'RLB');
								$pairSku = $baseSku."LR";
								$pairShippingCost = 0;
								if($newCost*2 < 40) {
									$pairShippingCost = $shippingCost*2;
									if (40-$newCost*2 < $pairShippingCost) $pairShippingCost = 40-$newCost*2;
								}
								$newPairTotalCost = $newCost*2 + $pairShippingCost;
								$newPairPrice = round(($newPairTotalCost + 0.3) / (0.8785 - 0.15), 2); 
								$calcedPairMinPrice = round(($newPairTotalCost + 0.3 + 10) / 0.8785, 2); // min profit is $10 for brock
								if ($calcedPairMinPrice > $newPairPrice) $newPairPrice = $calcedPairMinPrice;
								$array = array($pairSku, $newCost*2, $newPairPrice);
								fputcsv($bpCSV, $array);
							}
						}
					}
				}
			}
		}
		unset($data);
		unlink($files[0]);
	}
}

echo "... calculate days without sale".PHP_EOL;
$query = "select elp.online_sku as 'sku', 
elp.start_date as 'start_date', 
elp.end_date, 
dws.value as 'days_without_sale',
datediff(now(), subtime(elp.start_date, '0 4:00:00')) as 'days_active'
from m2epro_ebay_listing_product elp
left join m2epro_ebay_item ei on ei.id=elp.ebay_item_id
left join m2epro_listing_product lp on lp.id=elp.listing_product_id
left join catalog_product_entity_varchar dws on dws.entity_id=lp.product_id and dws.attribute_id=206
where lp.status=2;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	echo "... updating ".mysqli_num_rows($result)." days-wo-sale.".PHP_EOL;
	$dwsCSVName = '/var/www/html/var/import/dws.csv';
	$dwsCSV = fopen($dwsCSVName, "w");
	fputcsv($dwsCSV, array('sku','days_without_sale'));
	$yesterday = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));
	$dws = 0;
	
	while ($row = mysqli_fetch_array($result)) { 
		$sku = $row['sku'];
		$startDate = date('Y-m-d',strtotime($row['start_date']));
		$dws = $row['days_without_sale'];
		if ($dws == null or $dws == '') {
			$dws = $row['days_active'];
		}
		else if ($startDate == $yesterday) { // this will clean up dws from the previous defunct listing
			$dws = 1;
		}
		else {
			$dws++;
		}
		$array = array($sku, $dws);
		fputcsv($dwsCSV, $array);
	}
}

echo "... calculate yesterday sales".PHP_EOL;
$query = "select distinct eoi.sku
from m2epro_order_item oi
inner join m2epro_order o on o.id=oi.order_id
inner join m2epro_ebay_order eo on eo.order_id=o.id
inner join m2epro_ebay_order_item eoi on eoi.order_item_id=oi.id
where date(eo.purchase_create_date)=subdate(current_date, 1);";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	echo "... updating ".mysqli_num_rows($result)." yesterday-sales.".PHP_EOL;
	$ysCSVName = '/var/www/html/var/import/yesterday-sales.csv';
	$ysCSV = fopen($ysCSVName, "w");
	fputcsv($ysCSV, array('sku','days_without_sale'));
	
	while ($row = mysqli_fetch_array($result)) { 
		$sku = $row['sku'];
		$array = array($sku, 1);
		fputcsv($ysCSV, $array);
	}
}

// close the connections
echo "...close the connections".PHP_EOL;
$magento_con->close();
$mojo_con->close();

echo "PHP script complete.".PHP_EOL;
?>

<?php
include 'config.php';
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
mysqli_real_connect($magento_con, $host,$username,$password,$magento_db);
if (!$magento_con) {
    echo "Cannot connect to magento database.";
    exit(1);
}
mysqli_real_connect($mojo_con, $host,$username,$password,$custom_db);
if (!$mojo_con) {
    echo "Cannot connect to mojo database.";
    exit(1);
}

echo "...clear the import & inventory files".PHP_EOL;
$query = "truncate mojo_pfg_inv_import;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

$query = "truncate mojo_pfg_inv";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...daily backup of vendor inventory".PHP_EOL;
$query = "truncate mojo_vendor_inventory_backup;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
$query = "INSERT INTO mojo_vendor_inventory_backup 
(`vendor`, `warehouse`, `vendor_item_number`, `item_number`, `item_status`, `qty`, `yesterday_qty`, `avg_demand`, `stock_days`, `in_stock_streak`, `weight`, `item_cost`, `shipping_cost`, `handling_cost`, `interchange`, `partslink`, `oem`, `other_number_1`, `other_label_1`, `other_number_2`, `other_label_2`, `est_restock_date`, `msrp`, `category`, `description`, `create_date`, `update_date`, `processed_flag`) 
SELECT `vendor`, `warehouse`, `vendor_item_number`, `item_number`, `item_status`, `qty`, `yesterday_qty`, `avg_demand`, `stock_days`, `in_stock_streak`, `weight`, `item_cost`, `shipping_cost`, `handling_cost`, `interchange`, `partslink`, `oem`, `other_number_1`, `other_label_1`, `other_number_2`, `other_label_2`, `est_restock_date`, `msrp`, `category`, `description`, `create_date`, `update_date`, `processed_flag` 
FROM `mojo_vendor_inventory`;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...import the data from the ftp file".PHP_EOL;
$filename = null;
$yesterday = date('Ymd', strtotime('-1 day', strtotime(date('Y-m-d'))));
$files = glob("/var/www/html/var/import/usautoparts_inv_noquotes.txt");

if (count($files) == 1) {
	$filename = $files[0];
} else {
	echo "ERROR: today's inventory file not found".PHP_EOL;
	exit (1);
}
$query = "LOAD DATA LOCAL INFILE '{$filename}' 
INTO TABLE mojo_pfg_inv_import
FIELDS TERMINATED BY '\t' 
OPTIONALLY ENCLOSED BY '\"' 
ESCAPED BY '\"' 
LINES TERMINATED BY '\n' 
IGNORE 1 LINES 
(`warehouse`, `sku`, `brand`, `part_name`, `partslink`, `oem_number`, `qty`, `cost`, `list_price`, `shipping_cost`, `handling_cost`, `description`);";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".mysqli_error($magento_con)."\r\n".$query.PHP_EOL;
	exit(1);
}

echo "...check to see if the entire file is corrupt".PHP_EOL;
$query = "select count(*)
from mojo_pfg_inv_import ii
where ii.qty>0
and ii.cost>0
and ii.shipping_cost>0
and ii.handling_cost>0;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".mysqli_error($magento_con)."\r\n".$query.PHP_EOL;
	exit(1);
}
$row = mysqli_fetch_array($result);
if ($row[0] == 0) {
	echo "ERROR: import file is corrupt.  All the cost fields are empty".PHP_EOL;
	exit(1);
}	

echo "...delete any bad rows".PHP_EOL;
$query = "delete from mojo_pfg_inv_import
where cost=0
	or shipping_cost=0
	or handling_cost=0
	or sku=''
	or sku is null;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".mysqli_error($magento_con)."\r\n".$query.PHP_EOL;
	exit(1);
}

echo "...consolidate the qtys for each whs into today's table".PHP_EOL;
$query = "insert into mojo_pfg_inv (sku, qty, cost, shipping_cost, handling_cost, import_date, va_qty, list_price)
select imp.sku, imp.qty, imp.cost, imp.shipping_cost, imp.handling_cost, curdate(), imp.qty, list_price
from mojo_pfg_inv_import imp
where imp.warehouse='VA';";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

$query = "update mojo_pfg_inv inv
inner join mojo_pfg_inv_import imp on
    inv.sku = imp.sku and imp.warehouse='IL'
set inv.qty = inv.qty + imp.qty,
inv.il_qty = imp.qty;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...insert any new vendor items into the vendor inventory for IL".PHP_EOL;
$query = "insert into mojo_vendor_inventory (
vendor, warehouse, vendor_item_number, item_status, qty, item_cost, shipping_cost, handling_cost, msrp, create_date, update_date)
select 'PFG', 'IL', mpi.sku, 1, mpi.il_qty,	mpi.cost, mpi.shipping_cost, mpi.handling_cost,	mpi.list_price,	curdate(), curdate()
from mojo_pfg_inv mpi
left join mojo_vendor_inventory mvi on mvi.vendor_item_number=mpi.sku and mvi.vendor='PFG' and mvi.warehouse='IL'
where mvi.vendor_item_number is null;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...do the same for VA whs".PHP_EOL;
$query = "insert into mojo_vendor_inventory (
vendor, warehouse, vendor_item_number, item_status, qty, item_cost, shipping_cost, handling_cost, msrp, create_date, update_date)
select 'PFG', 'VA', mpi.sku, 1, mpi.va_qty,	mpi.cost, mpi.shipping_cost, mpi.handling_cost,	mpi.list_price,	curdate(), curdate()
from mojo_pfg_inv mpi
left join mojo_vendor_inventory mvi on mvi.vendor_item_number=mpi.sku and mvi.vendor='PFG' and mvi.warehouse='VA'
where mvi.vendor_item_number is null;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...move previous stock to yesterday_stock".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.yesterday_qty = mvi.qty,
	mvi.qty = 0
	where mvi.vendor='PFG';";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...update any existing items on the vendor inventory file".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
inner join mojo_pfg_inv mpi 
	on mvi.vendor_item_number=mpi.sku 
set mvi.qty=mpi.il_qty, 
	mvi.item_cost=mpi.cost,
	mvi.shipping_cost=mpi.shipping_cost,
	mvi.handling_cost=mpi.handling_cost,
	mvi.msrp=mpi.list_price,
	mvi.update_date=curdate()
where mvi.vendor='PFG' 
	and mvi.warehouse='IL';";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

$query = "update mojo_vendor_inventory mvi
inner join mojo_pfg_inv mpi 
	on mvi.vendor_item_number=mpi.sku 
set mvi.qty=mpi.va_qty,
	mvi.item_cost=mpi.cost,
	mvi.shipping_cost=mpi.shipping_cost,
	mvi.handling_cost=mpi.handling_cost,
	mvi.msrp=mpi.list_price,
	mvi.update_date=curdate()
where mvi.vendor='PFG' 
	and mvi.warehouse='VA';";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...handle OOS".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.stock_days=0,
	mvi.in_stock_streak=0
	where mvi.vendor='PFG'
	and mvi.qty=0;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...handle stock decrease when the demand is NOT primed yet".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.avg_demand = truncate(greatest(1, least(10,mvi.yesterday_qty-mvi.qty)),2)
where mvi.vendor='PFG'
	and mvi.qty <= mvi.yesterday_qty and mvi.qty > 0 and mvi.avg_demand is null;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...handle stock decrease when the demand IS primed".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.avg_demand = truncate(greatest(1, 0.86*mvi.avg_demand + 0.14*(least(10,mvi.yesterday_qty-mvi.qty))),2)
where mvi.vendor='PFG'
	and mvi.qty <= mvi.yesterday_qty and mvi.qty > 0 and mvi.avg_demand is not null;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...determine the number of days of stock left when there was normal demand".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.stock_days = truncate(mvi.qty/mvi.avg_demand,2)
where mvi.vendor='PFG'
	and mvi.qty <= mvi.yesterday_qty and mvi.qty > 0;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...handle stock increase, when the demand HAS ALREADY been primed".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.stock_days = truncate(mvi.qty/mvi.avg_demand,2)
where mvi.vendor='PFG'
	and mvi.qty > mvi.yesterday_qty and mvi.avg_demand is not null;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...handle stock increase, when the demand has NOT been primed, in this case, since we don't have avg_demand, assume the worst case of 10".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.avg_demand = 0, 
	mvi.stock_days = truncate(mvi.qty/10,2)
where mvi.vendor='PFG'
	and mvi.qty > mvi.yesterday_qty and mvi.avg_demand is null;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...update the stock streak days (already set to 0 earlier for OOS)".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
set mvi.in_stock_streak = mvi.in_stock_streak + 1
where mvi.vendor='PFG'
	and mvi.qty > 0;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...refresh the magneto sku on these records, as they may have changed in magento".PHP_EOL;
$query = "update mojo_vendor_inventory mvi
inner join (
	SELECT  
		a.sku, 
		b.value as 'vendor_item_number'
	FROM catalog_product_entity a
	inner join catalog_product_entity_varchar b 
		on a.entity_id = b.entity_id 
		and b.attribute_id = 164
	inner join catalog_product_entity_int sts 
		on a.entity_id = sts.entity_id 
		and sts.attribute_id=96 
		and sts.value=1
) sub on sub.vendor_item_number=mvi.vendor_item_number
set mvi.item_number=sub.sku
;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...same for vendor item master".PHP_EOL;
$query = "update mojo_vendor_item_master mim
inner join (
	SELECT  
		a.sku, 
		b.value as 'vendor_item_number'
	FROM catalog_product_entity a
	inner join catalog_product_entity_varchar b 
		on a.entity_id = b.entity_id 
		and b.attribute_id = 164
	inner join catalog_product_entity_int d 
		on a.entity_id = d.entity_id 
		and d.value=36 
		and d.attribute_id = 163
	inner join catalog_product_entity_int sts 
		on a.entity_id = sts.entity_id 
		and sts.attribute_id=96 
		and sts.value=1
) sub on sub.vendor_item_number=mim.vendor_item_number
set mim.item_number=sub.sku
where mim.vendor='PFG';";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...remove the sku assignment from any vendor items where the magento product was disabled".PHP_EOL;
$query = "update mojo_vendor_inventory mviu
inner join (
	select mvi.vendor_item_number
	from mojo_vendor_inventory mvi
	left join catalog_product_entity cpe on cpe.sku=mvi.item_number
	left join catalog_product_entity_int cpei on cpei.entity_id=cpe.entity_id
	where cpei.attribute_id=96
	and cpei.value=2
) sub on sub.vendor_item_number=mviu.vendor_item_number
set mviu.item_number='';";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "...same for vendor item master".PHP_EOL;
$query = "update mojo_vendor_item_master mimu
inner join (
	select mim.vendor_item_number
	from mojo_vendor_inventory mim
	left join catalog_product_entity cpe on cpe.sku=mim.item_number
	left join catalog_product_entity_int cpei on cpei.entity_id=cpe.entity_id
	where cpei.attribute_id=96
	and cpei.value=2
) sub on sub.vendor_item_number=mimu.vendor_item_number
set mimu.item_number='';";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}


echo "...reset the price_revised_flag=0, so that only new price changes are marked in the price spreadsheets".PHP_EOL;
$query = "update catalog_product_entity_int cpei 
set cpei.value=0
where cpei.attribute_id = 186
and cpei.value=1;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

$query = "truncate mojo_pfg_inv_import;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

$query = "truncate mojo_pfg_inv;";
if (!mysqli_query($magento_con, $query)) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

echo "... perform PFG OOS for singles".PHP_EOL;
$query = "select mviv.item_number as 'sku', '0' as 'is_in_stock', '0' as 'qty'
from mojo_vendor_inventory mviv
left join mojo_vendor_inventory mvii on mvii.vendor_item_number=mviv.vendor_item_number and mvii.vendor='PFG' and mvii.warehouse='IL' and mvii.item_cost>0 and mvii.shipping_cost>0 and mvii.handling_cost>0
inner join catalog_product_entity cpe on cpe.sku=mviv.item_number
inner join cataloginventory_stock_item iis on cpe.entity_id = iis.product_id and iis.is_in_stock=1
inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
inner join catalog_product_entity_int loc on cpe.entity_id = loc.entity_id and loc.attribute_id=162 and loc.value<>33
left join mojo_pfg_patents pat on pat.pfg_item=mviv.vendor_item_number 
where mviv.vendor='PFG'
and mviv.warehouse='VA'
and mviv.item_cost>0
and mviv.shipping_cost>0
and mviv.handling_cost>0
and mviv.qty+mvii.qty <= 2
and pat.pfg_item is null;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
		$oosCSVName = '/var/www/html/var/import/pfg-oos-singles.csv';
		$oosCSV = fopen($oosCSVName, "w");
		fputcsv($oosCSV, array('sku','is_in_stock','qty'));
		while ($row = mysqli_fetch_array($result)) { 
			$array = array($row['sku'], $row['is_in_stock'], $row['qty']);
			fputcsv($oosCSV, $array);
		}
}

echo "... perform PFG OOS for pairs".PHP_EOL;
$query = "select im.item_number as 'sku', '0' as 'is_in_stock', '0' as 'qty'
from mojo_vendor_item_master im
inner join mojo_vendor_inventory inv1 on inv1.vendor_item_number=im.component_1 and inv1.warehouse='VA' and inv1.vendor='PFG' and inv1.item_cost>0 and inv1.shipping_cost>0 and inv1.handling_cost>0
inner join mojo_vendor_inventory inv2 on inv2.vendor_item_number=im.component_2 and inv2.warehouse='VA' and inv2.vendor='PFG' and inv2.item_cost>0 and inv2.shipping_cost>0 and inv2.handling_cost>0
inner join mojo_vendor_inventory inv3 on inv3.vendor_item_number=im.component_1 and inv3.warehouse='IL' and inv3.vendor='PFG' and inv3.item_cost>0 and inv3.shipping_cost>0 and inv3.handling_cost>0
inner join mojo_vendor_inventory inv4 on inv4.vendor_item_number=im.component_2 and inv4.warehouse='IL' and inv4.vendor='PFG' and inv4.item_cost>0 and inv4.shipping_cost>0 and inv4.handling_cost>0
inner join catalog_product_entity cpe on cpe.sku=im.item_number
inner join cataloginventory_stock_item iis on cpe.entity_id = iis.product_id and iis.is_in_stock=1
inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
left join mojo_pfg_patents pat1 on pat1.pfg_item=inv1.vendor_item_number
left join mojo_pfg_patents pat2 on pat2.pfg_item=inv2.vendor_item_number
where im.component_1 is not null
and least(inv1.qty+inv3.qty, inv2.qty+inv4.qty) <= 2
and pat1.pfg_item is null
and pat2.pfg_item is null;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
		$oosCSVName = '/var/www/html/var/import/pfg-oos-pairs.csv';
		$oosCSV = fopen($oosCSVName, "w");
		fputcsv($oosCSV, array('sku','is_in_stock','qty'));
		while ($row = mysqli_fetch_array($result)) { 
			$array = array($row['sku'], $row['is_in_stock'], $row['qty']);
			fputcsv($oosCSV, $array);
		}
}

echo "... perform PFG IS for singles".PHP_EOL;
$query = "select mviv.item_number as 'sku', '1' as 'is_in_stock', '25' as 'qty'
from mojo_vendor_inventory mviv
left join mojo_vendor_inventory mvii on mvii.vendor_item_number=mviv.vendor_item_number and mvii.vendor='PFG' and mvii.warehouse='IL' and mvii.item_cost>0 and mvii.shipping_cost>0 and mvii.handling_cost>0
inner join catalog_product_entity cpe on cpe.sku=mviv.item_number
inner join cataloginventory_stock_item iis on cpe.entity_id = iis.product_id and iis.is_in_stock=0
inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
left join mojo_pfg_patents pat on pat.pfg_item=mviv.vendor_item_number 
where mviv.vendor='PFG'
and mviv.warehouse='VA'
and mviv.item_cost>0
and mviv.shipping_cost>0
and mviv.handling_cost>0
and mviv.qty+mvii.qty > 2
and pat.pfg_item is null;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
		$oosCSVName = '/var/www/html/var/import/pfg-is-singles.csv';
		$oosCSV = fopen($oosCSVName, "w");
		fputcsv($oosCSV, array('sku','is_in_stock','qty'));
		while ($row = mysqli_fetch_array($result)) { 
			$array = array($row['sku'], $row['is_in_stock'], $row['qty']);
			fputcsv($oosCSV, $array);
		}
}

echo "... perform PFG IS for pairs".PHP_EOL;
$query = "select im.item_number as 'sku', '1' as 'is_in_stock', '25' as 'qty'
from mojo_vendor_item_master im
inner join mojo_vendor_inventory inv1 on inv1.vendor_item_number=im.component_1 and inv1.warehouse='VA' and inv1.vendor='PFG' and inv1.item_cost>0 and inv1.shipping_cost>0 and inv1.handling_cost>0
inner join mojo_vendor_inventory inv2 on inv2.vendor_item_number=im.component_2 and inv2.warehouse='VA' and inv2.vendor='PFG' and inv2.item_cost>0 and inv2.shipping_cost>0 and inv2.handling_cost>0
inner join mojo_vendor_inventory inv3 on inv3.vendor_item_number=im.component_1 and inv3.warehouse='IL' and inv3.vendor='PFG' and inv3.item_cost>0 and inv3.shipping_cost>0 and inv3.handling_cost>0
inner join mojo_vendor_inventory inv4 on inv4.vendor_item_number=im.component_2 and inv4.warehouse='IL' and inv4.vendor='PFG' and inv4.item_cost>0 and inv4.shipping_cost>0 and inv4.handling_cost>0
inner join catalog_product_entity cpe on cpe.sku=im.item_number
inner join cataloginventory_stock_item iis on cpe.entity_id = iis.product_id and iis.is_in_stock=0
inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
left join mojo_pfg_patents pat1 on pat1.pfg_item=inv1.vendor_item_number
left join mojo_pfg_patents pat2 on pat2.pfg_item=inv2.vendor_item_number
where im.component_1 is not null
and least(inv1.qty+inv3.qty, inv2.qty+inv4.qty) > 2
and pat1.pfg_item is null
and pat2.pfg_item is null;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
		$oosCSVName = '/var/www/html/var/import/pfg-is-pairs.csv';
		$oosCSV = fopen($oosCSVName, "w");
		fputcsv($oosCSV, array('sku','is_in_stock','qty'));
		while ($row = mysqli_fetch_array($result)) { 
			$array = array($row['sku'], $row['is_in_stock'], $row['qty']);
			fputcsv($oosCSV, $array);
		}
}

// Process Brock inventory emails
$mBox = imap_open('{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX', $email_user, $email_pwd);
if ($mBox === false) throw new Exception('Unable to connect to mailbox. '.imap_last_error());

grab_attachment($mBox, "Brock Supply Stock Availability Report");
grab_attachment($mBox, "Brock Supply Price Change Report");

// Read the Brock stock file and update the vendor inventory table
$files = glob("/var/www/html/var/import/Brock_Stock_Change.xlsx");
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
$files = glob("/var/www/html/var/import/Brock_Price_Change.xls");
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

echo "... calculate yestserday sales".PHP_EOL;
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

echo "... create brock inventory file for channel spyder".PHP_EOL;
$query = "	SELECT i.vendor_item_number AS 'brocksku',
	i.item_cost AS 'productcost',
	i.qty AS 'qty'
	FROM mojo_vendor_inventory i
	WHERE i.vendor='brock'
	AND i.item_status='enabled';";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}
if (mysqli_num_rows($result)) {
	echo "... updating ".mysqli_num_rows($result)." brock-price-qty.".PHP_EOL;
	$biCSVName = '/var/www/html/var/import/brock-price-qty.csv';
	$biCSV = fopen($biCSVName, "w");
	fputcsv($biCSV, array('brock sku','product cost','qty'));
	
	while ($row = mysqli_fetch_array($result)) { 
		$array = array($row['brocksku'], $row['productcost'], $row['qty']);
		fputcsv($biCSV, $array);
	}
}

// close the connections
echo "...close the connections".PHP_EOL;
$magento_con->close();
$mojo_con->close();

echo "PHP script complete.".PHP_EOL;
?>

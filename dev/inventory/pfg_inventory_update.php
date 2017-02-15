<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 
/*
// load pfg & brock stock files into database table
$result = mysqli_query($con, "truncate mojomagento.mojo_pfg_inv_import") or die(mysqli_error($con));
$result = mysqli_query($con, "truncate mojomagento.mojo_pfg_inv") or die(mysqli_error($con));

## import the data from the ftp file 
$result = mysqli_query($con, "LOAD DATA LOCAL INFILE '/var/www/html/var/import/usautoparts_20160417180120.txt' 
INTO TABLE `mojomagento`.`mojo_pfg_inv_import` 
FIELDS TERMINATED BY '	' 
OPTIONALLY ENCLOSED BY '\"' 
ESCAPED BY '\"' 
LINES TERMINATED BY '\n' 
IGNORE 1 LINES 
(`warehouse`, `sku`, `part_name`, `qty`, `cost`, `list_price`, `shipping_cost`, `handling_cost`, `description`)") or die(mysqli_error($con));

## consolidate the qtys for each whs into today's table
$result = mysqli_query($con, "insert into mojo_pfg_inv (sku, qty, cost, shipping_cost, handling_cost, import_date, va_qty)
select imp.sku, imp.qty, imp.cost, imp.shipping_cost, imp.handling_cost, curdate(), imp.qty
from mojo_pfg_inv_import imp
where imp.warehouse='VA'") or die(mysqli_error($con));

$result = mysqli_query($con, "update mojo_pfg_inv inv
inner join mojo_pfg_inv_import imp on
    inv.sku = imp.sku and imp.warehouse='IL'
set inv.qty = inv.qty + imp.qty,
inv.il_qty = imp.qty") or die(mysqli_error($con));

/* insert any new vendor items into the vendor item master for IL */
$result = mysqli_query($con, "insert into mojo_vendor_inventory_copy (
vendor,
warehouse,
vendor_item_number,
item_status,
qty,
item_cost,
shipping_cost,
handling_cost,
create_date,
update_date)
	select 
	'PFG', 
	'IL', 
	mpi.sku, 
	1, 
	mpi.il_qty,
	mpi.cost, 
	mpi.shipping_cost, 
	mpi.handling_cost,
	curdate(),
	curdate()
	from mojo_pfg_inv mpi
	left join mojo_vendor_inventory_copy mvi 
		on mvi.vendor_item_number=mpi.sku 
		and mvi.vendor='PFG' 
		and mvi.warehouse='IL'
	where mvi.vendor_item_number is null") or die(mysqli_error($con));

/* do the same for VA whs */
$result = mysqli_query($con, "insert into mojo_vendor_inventory_copy (
vendor,
warehouse,
vendor_item_number,
item_status,
qty,
item_cost,
shipping_cost,
handling_cost,
create_date,
update_date)

	select 
	'PFG', 
	'VA', 
	mpi.sku, 
	1, 
	mpi.va_qty,
	mpi.cost, 
	mpi.shipping_cost, 
	mpi.handling_cost,
	curdate(),
	curdate()
	from mojo_pfg_inv mpi
	left join mojo_vendor_inventory_copy mvi 
		on mvi.vendor_item_number=mpi.sku 
		and mvi.vendor='PFG' 
		and mvi.warehouse='VA'
	where mvi.vendor_item_number is null") or die(mysqli_error($con));

/* move previous stock to yesterday_stock */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.yesterday_qty = mvi.qty,
	mvi.qty = 0
	where mvi.vendor='PFG'") or die(mysqli_error($con));

/* update any existing items on the vendor item master */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
inner join mojo_pfg_inv mpi 
	on mvi.vendor_item_number=mpi.sku 
set mvi.qty=mpi.il_qty, 
	mvi.item_cost=mpi.cost,
	mvi.shipping_cost=mpi.shipping_cost,
	mvi.handling_cost=mpi.handling_cost,
	mvi.update_date=curdate()
where mvi.vendor='PFG' 
	and mvi.warehouse='IL'") or die(mysqli_error($con));

$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
inner join mojo_pfg_inv mpi 
	on mvi.vendor_item_number=mpi.sku 
set mvi.qty=mpi.va_qty,
	mvi.item_cost=mpi.cost,
	mvi.shipping_cost=mpi.shipping_cost,
	mvi.handling_cost=mpi.handling_cost,
	mvi.update_date=curdate()
where mvi.vendor='PFG' 
	and mvi.warehouse='VA'") or die(mysqli_error($con));


/* handle OOS */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.stock_days=0,
	mvi.in_stock_streak=0
	where mvi.vendor='PFG'
	and mvi.qty=0") or die(mysqli_error($con));
	
/* handle stock decrease when the demand is NOT primed yet */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.avg_demand = truncate(greatest(1, least(10,mvi.yesterday_qty-mvi.qty)),2)
where mvi.vendor='PFG'
	and mvi.qty <= mvi.yesterday_qty and mvi.qty > 0 and mvi.avg_demand = 1") or die(mysqli_error($con));

/* handle stock decrease when the demand IS primed */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.avg_demand = truncate(greatest(1, 0.86*mvi.avg_demand + 0.14*(least(10,mvi.yesterday_qty-mvi.qty))),2)
where mvi.vendor='PFG'
	and mvi.qty <= mvi.yesterday_qty and mvi.qty > 0 and mvi.avg_demand > 1") or die(mysqli_error($con));

/* determine the number of days of stock left when there was normal demand */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.stock_days = truncate(mvi.qty/mvi.avg_demand,2)
where mvi.vendor='PFG'
	and mvi.qty <= mvi.yesterday_qty and mvi.qty > 0") or die(mysqli_error($con));

/* handle stock increase, when the demand HAS ALREADY been primed */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.stock_days = truncate(mvi.qty/mvi.avg_demand,2)
where mvi.vendor='PFG'
	and mvi.qty > mvi.yesterday_qty and mvi.avg_demand>1") or die(mysqli_error($con));

/* handle stock increase, when the demand has NOT been primed */
/* in this case, since we don't have avg_demand, assume the worst case of 10 */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.stock_days = truncate(mvi.qty/10,2)
where mvi.vendor='PFG'
	and mvi.qty > mvi.yesterday_qty and mvi.avg_demand=1") or die(mysqli_error($con));

/* update the stock streak days */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.in_stock_streak = 0
where mvi.vendor='PFG'
	and mvi.qty = 0") or die(mysqli_error($con));

$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
set mvi.in_stock_streak = mvi.in_stock_streak + 1
where mvi.vendor='PFG'
	and mvi.qty > 0") or die(mysqli_error($con));

/* refresh the magneto sku on these records, as they may have changed in magento */
$result = mysqli_query($con, "update mojo_vendor_inventory_copy mvi
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
) sub on sub.vendor_item_number=mvi.vendor_item_number
set mvi.item_number=sub.sku
where mvi.vendor='PFG'") or die(mysqli_error($con));
*/
$result = mysqli_query($con, "select mvi.item_number as 'sku', mvi.qty as 'il_whs_qty'
from mojo_vendor_inventory_copy mvi
where mvi.item_number is not null
and mvi.item_number <> ''
and mvi.vendor = 'PFG'
and mvi.warehouse='IL'
INTO OUTFILE '/var/www/html/var/import/inventory/il_whs_import_test.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '\"'
LINES TERMINATED BY '\n'") or die(mysqli_error($con));

$result = mysqli_query($con, "select mvi.item_number as 'sku', mvi.qty as 'va_whs_qty'
from mojo_vendor_inventory_copy mvi
where mvi.item_number is not null
and mvi.item_number <> ''
and mvi.vendor = 'PFG'
and mvi.warehouse='VA'
INTO OUTFILE '/var/www/html/var/import/inventory/va_whs_import_test.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '\"'
LINES TERMINATED BY '\n'") or die(mysqli_error($con));


/* while($row = mysqli_fetch_array($result)) {
	// find the vendor inventory records
	$item = $row['item'];
	$vendor_item = $row['vendor_item'];
	$current_stock_status = $row['current_stock_status'];
	$va_whs_qty = $row['va_whs_qty'];
	$il_whs_qty = $row['il_whs_qty'];
	$current_price = $row['current_price'];

	echo $vendor_item.", ".$current_stock_status.", ".$va_whs_qty.", ".$il_whs_qty.", ".$current_price.PHP_EOL;
	
}
*/

// report any exceptions

// log changes to log table


$con->close();
?>

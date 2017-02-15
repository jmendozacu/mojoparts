<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

// load pfg & brock stock files into database table
$result = mysqli_query($con, "truncate mojomagento.mojo_vendor_inventory") or die(mysqli_error($con));

$result = mysqli_query($con, "LOAD DATA LOCAL INFILE '/var/www/html/var/import/usautoparts.txt' 
INTO TABLE mojomagento.mojo_vendor_inventory
FIELDS TERMINATED BY '\t' 
OPTIONALLY ENCLOSED BY '\"' 
ESCAPED BY '\"' 
LINES TERMINATED BY '\n' 
IGNORE 1 LINES 
(warehouse, item_number, category, qty, item_cost, msrp, shipping_cost, handling_cost, description)
set vendor = 'PFG',
item_status = 1,
create_date = curdate(),
update_date = curdate()") or die(mysqli_error($con));

// read through products and update stock levels & pricing accordingly
$result = mysqli_query($con, "SELECT  
cpe.sku as 'item', 
vi.value as 'vendor_item',
v.value as 'vendor',
ss.is_in_stock as 'current_stock_status',
wa.value as 'va_whs_qty',
wb.value as 'il_whs_qty',
p.value as 'current_price'
FROM catalog_product_entity cpe
inner join catalog_product_entity_varchar vi on cpe.entity_id = vi.entity_id and vi.attribute_id = 164
inner join catalog_product_entity_int v on cpe.entity_id = v.entity_id and v.attribute_id = 163
left join cataloginventory_stock_item ss on cpe.entity_id = ss.product_id
left outer join catalog_product_entity_decimal p on cpe.entity_id = p.entity_id and p.attribute_id=75
left outer join catalog_product_entity_varchar wa on cpe.entity_id = wa.entity_id and wa.attribute_id=171
left outer join catalog_product_entity_varchar wb on cpe.entity_id = wb.entity_id and wb.attribute_id=173
order by cpe.sku limit 10") or die(mysqli_error($con));

while($row = mysqli_fetch_array($result)) {
	// find the vendor inventory records
	$item = $row['item'];
	$vendor_item = $row['vendor_item'];
	$current_stock_status = $row['current_stock_status'];
	$va_whs_qty = $row['va_whs_qty'];
	$il_whs_qty = $row['il_whs_qty'];
	$current_price = $row['current_price'];

	echo $vendor_item.", ".$current_stock_status.", ".$va_whs_qty.", ".$il_whs_qty.", ".$current_price.PHP_EOL;
	
}


// report any exceptions

// log changes to log table


$con->close();
?>

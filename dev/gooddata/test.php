<?php
echo "1";
error_reporting(E_ALL);
echo "2";
libxml_use_internal_errors(true);
echo "3";
include("config.php"); 
echo "4";

// read through products and update stock levels & pricing accordingly
echo "5";

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
order by cpe.sku limit 10");
echo "6<br>";
echo 'ERROR: '.mysqli_error($con).'<br>';
echo 'STATE: '.mysqli_sqlstate($con).'<br>';
echo "a<br>";

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
echo "7";

$con->close();
echo "8";
?>

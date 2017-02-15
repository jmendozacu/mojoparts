<?php
error_reporting(E_ALL);

$skus = trim($_GET['skus']);
$textAr = explode("\n", $skus);
$textAr = array_filter($textAr, 'trim');

$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
mysqli_real_connect($con, 'mojomysql2.c6orzbehh7d1.us-east-1.rds.amazonaws.com','mojo','3^-4Grj,;pF7[3kN','mojomagento');

if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$csvfile = "/var/www/html/dev/gooddata/gooddata-compats-".date('Ymd').".csv";
if (! file_exists($csvfile)) die("$csvfile does not exist!");
if (! is_readable($csvfile)) die("$csvfile is unreadable!");
$csvfp = fopen($csvfile, 'a');

foreach($textAr as $sku) {
	$sku = trim($sku);
	$result = mysqli_query($con,"
		SELECT  a.sku, 
		b.value,
		ei.item_id
		FROM catalog_product_entity a
		inner join catalog_product_entity_text b on a.entity_id = b.entity_id and b.attribute_id=135
		inner join m2epro_ebay_item ei ON ei.product_id=a.entity_id
		inner join m2epro_ebay_listing_product elp ON elp.ebay_item_id=ei.id
		inner join m2epro_listing_product lp ON lp.id=elp.listing_product_id
		where a.sku='$sku'
		and lp.status=2
		and ei.account_id=3;
	");

	while($row = mysqli_fetch_array($result)) {
//		echo $row['sku'].", ".$row['item_id'].", ".$row['value']."<br/><br/>";

		$csvItemHeader = array("Revise",$row['item_id'],"","");
//		echo $csvItemHeader[0].",".$csvItemHeader[1].",".$csvItemHeader[2].",".$csvItemHeader[3]."<br/>";
		fputcsv($csvfp, $csvItemHeader);

		$compatAr = explode(PHP_EOL, trim($row['value']));
		foreach($compatAr as $compat) {
			$csvItemDetail = array("","","Compatibility",$compat);
//			echo $csvItemDetail[0].",".$csvItemDetail[1].",".$csvItemDetail[2].",".$csvItemDetail[3]."<br/>";
			fputcsv($csvfp, $csvItemDetail);
		}		
	}
}
mysqli_close($con);
fclose($csvfp);

echo 'Compatibility(ies) copied successfully.';

?>
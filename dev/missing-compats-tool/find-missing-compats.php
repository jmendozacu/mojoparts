<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$input_result = mysqli_query($con, "select ei.item_id from m2epro_ebay_item ei inner join m2epro_ebay_listing_product elp on elp.ebay_item_id=ei.id inner join m2epro_listing_product lp on lp.id=elp.listing_product_id inner join catalog_product_entity cpe on cpe.sku=elp.online_sku inner join catalog_product_entity_varchar rc on rc.entity_id=cpe.entity_id and rc.attribute_id=144 where lp.`status`=2"); 
// and rc.value like '20170817-%%'");
$numRows = mysqli_num_rows($input_result);
$rowNum = 0;
$pctDone = 0.0;

echo "Status,SKU,Item,Message".PHP_EOL;
while ($input_row = mysqli_fetch_array($input_result)) { 
	$rowNum++;
	if ($rowNum/$numRows > $pctDone+.01) {
		$pctDone = $rowNum/$numRows;
		$pctTxt = round($pctDone*100);
		echo "INFO,,,".$pctTxt."% complete".PHP_EOL;
	}
	$item_id = $input_row['item_id']; 

	// get the source listing info
	$apicall = "http://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=XML&appid=MojoPart-34e5-49b0-aab3-c8aa62626923&siteid=0&version=515";
	$apicall .= "&ItemID=".$item_id;
	$apicall .= "&includeSelector=Details,Compatibility";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apicall);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);

	if( ! $curl_result = curl_exec($ch))
    {
		trigger_error(curl_error($ch));
		echo "ERROR,,".$item_id.",skipped listing - error in CURL".PHP_EOL;		
    } else {
		$item = simplexml_load_string($curl_result)->Item;
		$sku = $item->SKU;
		curl_close($ch); 
		if (empty($item->ItemCompatibilityList->Compatibility)) {
			echo "WARNING,".$sku.",".$item_id.",missing compatibility table".PHP_EOL;
		} 
		if (empty($item->PictureURL[0])) {
			echo "WARNING,".$sku.",".$item_id.",missing picture".PHP_EOL;
		}
	}
} 

$input_result->close(); 
?>

<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

// load the listing information
$input_result = mysqli_query($con, "SELECT * FROM mojo_price_research_input");
while ($input_row = mysqli_fetch_array($input_result)) { 
	// clear the variable values
	$skuAlreadyExists = FALSE;
	$ebay_id = $input_row['ebay_id']; 
	$capa = FALSE;
	$isVendorSkuASet = FALSE;
	$processThisSku = TRUE;
	$skuExistsInOutput = FALSE;
	$skuNumber = ""; 
	$vendorSku1 = "";
	$vendorSku1RHLH = "";
	$vendorSku2 = "";

	// get the source listing info
	$apicall = "http://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=XML&appid=MojoPart-34e5-49b0-aab3-c8aa62626923&siteid=0&version=515";
	$apicall .= "&ItemID=".$ebay_id;
	$apicall .= "&includeSelector=Details,Description";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apicall);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);

	if( ! $curl_result = curl_exec($ch))
    {
		trigger_error(curl_error($ch));
		echo "*** ERROR [".$ebay_id."]: skipped listing - error in CURL".PHP_EOL;		
		$processThisSku = FALSE;
    } else {
		$item = simplexml_load_string($curl_result)->Item;
		curl_close($ch); 

		$listingHtml = $item->Description;

		// search for vendor sku in html
		$vendorSkuStartPattern = "<strong style=\"font-size:12px; color: black;\">Description / Notes:</strong><span style=\"margin-left:10px; color:#fff;\">";
		$vendorSkuEndPattern = "</span>";
		$vendorSkuStartPos = strpos($listingHtml, $vendorSkuStartPattern);
		$vendorSkuEndPos = strpos($listingHtml, $vendorSkuEndPattern, $vendorSkuStartPos);
		$vendorSku = substr($listingHtml, $vendorSkuStartPos+119, $vendorSkuEndPos-$vendorSkuStartPos-119);
		if (substr($vendorSku, -1) == "Q") {
			$capa = TRUE;
		}
		
		// Handle pairs
		if (substr($vendorSku, 0, 4) == "SET-") {
			$isVendorSkuASet = TRUE;
			$vendorSku1 = substr($vendorSku, 4);
			$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where sku = '{$vendorSku1}' limit 1");
			if (mysqli_num_rows($result) == 0 && substr($vendorSku1, -2) == "-2") { 
				$vendorSku1 = substr($vendorSku1, 0, -2);
				$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where sku = '{$vendorSku1}' limit 1");
			}
			if (mysqli_num_rows($result) == 1) { 
				$row = mysqli_fetch_array($result);
				$vendorSku1Description = $row['description'];
				$LHRHpos = false;
				$LHRHpos = strpos($vendorSku1Description, "RH=LH");
				if ($LHRHpos === false) {
					$LHRHpos = strpos($vendorSku1Description, "LH=RH");
				}
				if ($LHRHpos !== false) {
					$vendorSku2 = $vendorSku1;
				} else {			
					$RHpos = strpos($vendorSku1Description, "RH");
					if ($RHpos === false) {
						$LHpos = strpos($vendorSku1Description, "LH");
						if ($LHpos === false) {
							$processThisSku = FALSE;
							echo "... info [".$ebay_id."]: skipped [".$vendorSku."], no side (LH/RH) not found in the description for [".$vendorSku1."]".PHP_EOL;
						} else {
							$vendorSku2Description = str_replace("LH", "RH", $vendorSku1Description);
							$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where description = '{$vendorSku2Description}' limit 1");
							if (mysqli_num_rows($result) == 1) {
								$row = mysqli_fetch_array($result);
								$vendorSku2 = $row['sku'];
							} else {
								$processThisSku = FALSE;
								echo "... info [".$ebay_id."]: skipped [".$vendorSku."], could not find other side for [".$vendorSku1."]".PHP_EOL;
							}
						}
					} else {
						$vendorSku2Description = str_replace("RH", "LH", $vendorSku1Description);
						$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where description = '{$vendorSku2Description}' limit 1");
						if (mysqli_num_rows($result) == 1) {
							$row = mysqli_fetch_array($result);
							$vendorSku2 = $row['sku'];
						} else {
							$processThisSku = FALSE;
							echo "... info [".$ebay_id."]: skipped [".$vendorSku."], could not find other side part for [".$vendorSku1."]".PHP_EOL;
						}
					}
				}
			} else {
				$processThisSku = FALSE;
				echo "... info [".$ebay_id."]: skipped [".$vendorSku."] (".$vendorSku1.",".$vendorSku2.") not found in import file".PHP_EOL;
			}
		}
	}

	// check to see if this is restricted
	if ($processThisSku) {
		if ($isVendorSkuASet) {
			$result = mysqli_query($con, "select * from mojo_pfg_patents where pfg_item = '{$vendorSku1}'");
			if (mysqli_num_rows($result) == 1) { 
				$processThisSku = FALSE;
				echo "... info [".$ebay_id."]: [".$vendorSku."] is restricted".PHP_EOL;
			} else {
				$result = mysqli_query($con, "select * from mojo_pfg_patents where pfg_item = '{$vendorSku2}'");
				if (mysqli_num_rows($result) == 1) { 
					$processThisSku = FALSE;
					echo "... info [".$ebay_id."]: skipped [".$vendorSku."] is restricted".PHP_EOL;
				}
			}
		} else {
			$result = mysqli_query($con, "select * from mojo_pfg_patents where pfg_item = '{$vendorSku}'");
			if (mysqli_num_rows($result) == 1) { 
				$processThisSku = FALSE;
				echo "... info [".$ebay_id."]: skipped [".$vendorSku."] is restricted".PHP_EOL;
			}
		}
	}
	
	if ($processThisSku) {
	
		$listingPrice = $item->CurrentPrice;
	
		// determine if the sku is already in magento
		if ($isVendorSkuASet) {
			$vendorSkuCombo1 = $vendorSku1.",".$vendorSku2;
			$vendorSkuCombo2 = $vendorSku1.", ".$vendorSku2;
			$vendorSkuCombo3 = $vendorSku2.",".$vendorSku1;
			$vendorSkuCombo4 = $vendorSku2.", ".$vendorSku1;
			$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value in ('{$vendorSkuCombo1}','{$vendorSkuCombo2}','{$vendorSkuCombo3}','{$vendorSkuCombo4}') and cp.attribute_id=164 limit 1");
		} else {
			$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value='{$vendorSku}' and cp.attribute_id=164 limit 1");
		}
		
		// look for an exact match on vendor sku
		if (mysqli_num_rows($result) == 1) { 
			$row = mysqli_fetch_array($result);
			$skuNumber = $row['sku']; 
			$skuAlreadyExists = TRUE;
		} else {
			echo " [".$ebay_id."]: sku doesn't exist".PHP_EOL;
		}
	}

	// write the new item to the output file
	if($processThisSku) {
		if ($skuAlreadyExists) {
			mysqli_query($con, "INSERT INTO `mojo_price_research_output` (`sku`, `competitor_list_price`) VALUES ('{$skuNumber}', '{$listingPrice}')"); 
			echo "success [".$ebay_id."]: updated [".$skuNumber."] at $",$listingPrice.PHP_EOL;
		}
	}
} 

$result->close(); 
$input_result->close(); 

echo "Whew, done.".PHP_EOL;
?>

<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$markup = 1.15;
$markupFloor = 5;

// write the new item to the output file
 if(isset($_POST['btnapprove'])) {

	// get the correct category information based on the selection
	$result = mysqli_query($con, "SELECT * FROM mojo_category_lookup where id = '{$_POST['categorySelection']}' limit 1");
	$row = mysqli_fetch_array($result);
	$tempMagentoCat = $row['magento_category'];
	$tempEBayCat = $row['ebay_category'];
	$tempEBayStoreCat = $row['ebay_store_category'];
	$tempSkuPrefix = $row['prefix'];

	// build the new sku# unless already found
	$postSkuNumber = $_POST['skuNumber'];
	if ($postSkuNumber == "") {
		$result = mysqli_query($con, "SELECT * FROM mojo_sku_numbering WHERE prefix='{$tempSkuPrefix}' limit 1");
		$row = mysqli_fetch_array($result);
		$postSkuNumber = $tempSkuPrefix.str_pad($row['next_sequence'], 5, "0", STR_PAD_LEFT); 
		if ($_POST['isVendorSkuASet']) {
			$postSkuNumber = $postSkuNumber."LR";
		} else {
			if ($_POST['placementOnVehicle'] == "Right") {
				$postSkuNumber = $postSkuNumber."R";
			}
			if ($_POST['placementOnVehicle'] == "Left") {
				$postSkuNumber = $postSkuNumber."L";
			}
		}
		mysqli_query($con, "UPDATE mojo_sku_numbering SET next_sequence=next_sequence+1 WHERE prefix = '{$tempSkuPrefix}'"); 
	}

	// create the output records (to be used to import/update products into magento)
	$vendorSkuForInsert = $_POST['vendorSku'];
	if ($_POST['isVendorSkuASet']) {
		$vendorSkuForInsert = $_POST['vendorSku1'].",".$_POST['vendorSku2'];
	}
	$is_in_stock = 0;
	$qty = 0;
	if ($_POST['stockQty'] >= 5) {
		$is_in_stock = 1;
		$qty = 25;
	}
	mysqli_query($con, "INSERT INTO `mojo_ebaycopy_new_output` (`sku`, `category_ids`, `additional_notes`, `combined_pair_ind`, `compatibility`, `cost`, `description`, `ebay_category`, `ebay_store_cat_a`, `ebay_title`, `ebay_upc`, `hollander`, `is_in_stock`, `manufacturer`, `name`, `oemnumber`, `partslink`, `placement_on_vehicle`, `price`, `qty`, `short_description`, `surface_finish`, `vendor`, `vendor_item`) 
	VALUES ('{$postSkuNumber}', '{$tempMagentoCat},181', '{$_POST['additionalNotes']}', '{$_POST['isVendorSkuASet']}', '{$_POST['compatibility']}', '{$_POST['productCost']}', '{$_POST['ebayTitle']}', '{$tempEBayCat}', '{$tempEBayStoreCat}', '{$_POST['ebayTitle']}', '{$_POST['upc']}', '{$_POST['hollander']}', '{$is_in_stock}', '{$_POST['brand']}', '{$_POST['ebayTitle']}', '{$_POST['oemnumber']}', '{$_POST['partslink']}', '{$_POST['placementOnVehicle']}', '{$_POST['ourPrice']}', '{$qty}', '{$_POST['ebayTitle']}', '{$_POST['surfaceFinish']}', '{$_POST['vendor']}', '{$vendorSkuForInsert}')"); 

	// create the images file
	$chp = curl_init();
	curl_setopt($chp, CURLOPT_FAILONERROR, 1);
	curl_setopt($chp, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($chp, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($chp, CURLOPT_TIMEOUT, 15);

	if (isset($_POST['copyImagesInd'])) {
		$picArray = array();
		$imageSku = $_POST['vendorSku'];
		if ($_POST['isVendorSkuASet']) {
			$imageSku = $_POST['vendorSku1'];
		}
		for ($i = 9; $i > 0; $i--) {
			$picString = 'http://img.ptimg.com/is/image/Autos/'.strtolower($imageSku).'_'.$i.'?scl=2';
			curl_setopt($chp, CURLOPT_URL, $picString);
			$returnedp = curl_exec($chp);
			if ($returnedp) { 
				array_push($picArray, $picString);
				if ($i == 1) {
					if ($_POST['isVendorSkuASet']) {
						$postGalleryURL = $_POST['galleryURL'];
						mysqli_query($con, "INSERT INTO `mojo_ebaycopy_new_pics` (`sku`, `image`, `small_image`, `thumbnail`) VALUES ('{$postSkuNumber}', '{$postGalleryURL}', '{$postGalleryURL}', '{$postGalleryURL}')"); 
					} else {
						mysqli_query($con, "INSERT INTO `mojo_ebaycopy_new_pics` (`sku`, `image`, `small_image`, `thumbnail`) VALUES ('{$postSkuNumber}', '{$picString}', '{$picString}', '{$picString}')"); 
					}
				} else {
					mysqli_query($con, "INSERT INTO `mojo_ebaycopy_new_pics` (`sku`, `image`) VALUES ('{$postSkuNumber}', '{$picString}')"); 
				}
			}
		}
	} 
}

// remove the listing from the input file
if(isset($_POST['btnreject']) || isset($_POST['btnapprove'])){
	mysqli_query($con, "DELETE FROM mojo_ebaycopy_input_listings WHERE ebay_id = ('{$_POST['ebay_id']}')"); 
}

// get the next listing to copy
$input_result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_input_listings limit 1");

// load the listing information
while (mysqli_num_rows($input_result) == 1) { 
	$input_row = mysqli_fetch_array($input_result);
	$ebay_id = $input_row['ebay_id']; 

	// variable declarations
	$additionalNotes = "";
	$additionalDetailArray = array();
	$brand = "Aftermarket Replacement";
	$categoryArray = array();
	$categoryID = "";
	$compatibility = "";
	$hollander = "";
	$magentoImageURLArray = array();
	$multipleSkuMatches = FALSE;
	$oemnumber = "";
	$partslink = "";
	$placementOnVehicle = "";
	$material = ""; //not currently used
	$notes = ""; //not currently used
	$skuNumber = ""; 
	$upc = "Does Not Apply";

	// call the ebay api to get the listing info
	$apicall = "http://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=XML&appid=MojoPart-34e5-49b0-aab3-c8aa62626923&siteid=0&version=515";
	$apicall .= "&ItemID=".$ebay_id;
	$apicall .= "&includeSelector=Details,Compatibility,ItemSpecifics,Description";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apicall);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	$item = simplexml_load_string(curl_exec($ch))->Item;
	curl_close($ch); 

	// variable declarations
	$ebayCategory = $item->PrimaryCategoryID;
	$ebayCategoryName = $item->PrimaryCategoryName;
	$ebayTitle = $item->Title;
	$listingHtml = $item->Description;
	$vendor = "PFG"; // hard-coded for now

	// load the category details
	$result = mysqli_query($con, "SELECT * FROM mojo_category_lookup where ebay_category=".$ebayCategory);
	while($row = mysqli_fetch_array($result)) {
		array_push($categoryArray, array($row['id'], $row['ebay_category'], $row['category_name'], $row['ebay_store_category'], $row['magento_category'], $row['prefix']));
	}
	
	// search for vendor sku in html
	$vendorSkuStartPattern = "<strong style=\"font-size:12px; color: black;\">Description / Notes:</strong><span style=\"margin-left:10px; color:#fff;\">";
	$vendorSkuEndPattern = "</span>";
	$vendorSkuStartPos = strpos($listingHtml, $vendorSkuStartPattern);
	$vendorSkuEndPos = strpos($listingHtml, $vendorSkuEndPattern, $vendorSkuStartPos);
	$vendorSku = substr($listingHtml, $vendorSkuStartPos+119, $vendorSkuEndPos-$vendorSkuStartPos-119);
	$vendorSku1 = "";
	$vendorSku2 = "";
	$isVendorSkuASet = FALSE;
	$processThisSku = TRUE;
	
	// handle pairs, sets, etc...
	if (substr($vendorSku, 0, 4) == "SET-") {
		$isVendorSkuASet = TRUE;
		if (substr($vendorSku, -1) == "R") {
			$vendorSku1 = substr($vendorSku, 4);
			$vendorSku2 = substr($vendorSku, 4, -1)."L";
		} else {
			$vendorSku1 = substr($vendorSku, 4);
			$vendorSku2Int = substr($vendorSku, -2)+1;
			if ($vendorSku2Int < 10) {
				$vendorSku2 = substr($vendorSku, 4, -1).$vendorSku2Int;
			} else {
				$vendorSku2 = substr($vendorSku, 4, -2).$vendorSku2Int;
			}
		}
	}
	
	// check to see if this is restricted
	if ($isVendorSkuASet) {
		$result = mysqli_query($con, "select * from mojo_pfg_patents where pfg_item = '{$vendorSku1}'");
		if (mysqli_num_rows($result) == 1) { 
			$processThisSku = FALSE;
echo "skipped ".$vendorSku." - restricted<br/>";
		} else {
			$result = mysqli_query($con, "select * from mojo_pfg_patents where pfg_item = '{$vendorSku2}'");
			if (mysqli_num_rows($result) == 1) { 
				$processThisSku = FALSE;
echo "skipped ".$vendorSku." - restricted<br/>";
			}
		}
	} else {
		$result = mysqli_query($con, "select * from mojo_pfg_patents where pfg_item = '{$vendorSku}'");
		if (mysqli_num_rows($result) == 1) { 
			$processThisSku = FALSE;
echo "skipped ".$vendorSku." - restricted<br/>";
		}
	}
	
	if ($processThisSku) {
		
		// determine if we already have the sku in our system based on the vendor sku
		if ($isVendorSkuASet) {
			$vendorSkuCombo1 = $vendorSku1.",".$vendorSku2;
			$vendorSkuCombo2 = $vendorSku1.", ".$vendorSku2;
			$vendorSkuCombo3 = $vendorSku2.",".$vendorSku1;
			$vendorSkuCombo4 = $vendorSku2.", ".$vendorSku1;
			$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value in ('{$vendorSkuCombo1}','{$vendorSkuCombo2}','{$vendorSkuCombo3}','{$vendorSkuCombo4}') and cp.attribute_id=155 limit 1");
		} else {
			$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value='{$vendorSku}' and cp.attribute_id=155 limit 1");
		}
		if (mysqli_num_rows($result) == 1) { 
			$row = mysqli_fetch_array($result);
			$skuNumber = $row['sku']; 
		} else {

			// determine if we already have the sku in our system based on the product identifiers
			$pIDs = array();
			if ($partslink != "0" && $partslink != "") array_push($pIDs, $partslink);
			if ($oemnumber != "0" && $oemnumber != "") array_push($pIDs, $oemnumber);
			if ($hollander != "0" && $hollander != "") array_push($pIDs, $hollander);
			if (count($pIDs) > 0) {
				$queryString = "select cpe.sku, ea.attribute_code, cv.value from catalog_product_entity_varchar cv
					inner join catalog_product_entity cpe on cpe.entity_id=cv.entity_id
					inner join eav_attribute ea on ea.attribute_id=cv.attribute_id
					where cv.attribute_id in (122, 123, 124) and (";
				$firstPID = TRUE;
				foreach ($pIDs as $pID) {
					if (!$firstPID) {
						$queryString = $queryString." or ";
					}
					$queryString = $queryString."cv.value like '%%".$pID."%%'";
					$firstPID = FALSE;
				}
				$queryString = $queryString.")";
echo $queryString."<br/>";
				$result = mysqli_query($con, $queryString);
				$resultCount = mysqli_num_rows($result);
				switch ($resultCount) {
					case 0:
						break;
					case 1:
						$row = mysqli_fetch_array($result);
						$skuNumber = $row['sku'];
						break;
					default:
						$multipleSkuMatches = TRUE;
						$skuNumber = "(MULTIPLE SKUS)"; 
				}
			}
		}
		
		// get the magento data
		if ($skuNumber != "") {

			// check to make sure this isn't with brock
			$result = mysqli_query($con, "SELECT  d.value FROM catalog_product_entity a inner join catalog_product_entity_int d on a.entity_id = d.entity_id where sku = '{$skuNumber}' and d.value=48 and d.attribute_id = 154");
			if (mysqli_num_rows($result) >= 1) { 
				$processThisSku = FALSE;
echo "skipped ".$vendorSku." - vendor isn't PFG<br/>";
			} else {
			
				// check to see if this is already listed
				$result = mysqli_query($con, "SELECT * FROM catalog_product_entity a inner join m2epro_listing_product lp on lp.product_id=a.entity_id WHERE a.sku = '{$skuNumber}'");
				if (mysqli_num_rows($result) == 1) { 
					$processThisSku = FALSE;
echo "skipped ".$vendorSku." - already listed<br/>";
				}
			}

			// get images
			if ($processThisSku) {
				$result = mysqli_query($con, "SELECT value FROM catalog_product_entity a LEFT JOIN catalog_product_entity_media_gallery b ON a.entity_id = b.entity_id WHERE a.sku = '{$skuNumber}'");
				while($row = mysqli_fetch_array($result)) {
					if ($row['value'] != NULL) {
						array_push($magentoImageURLArray, "http://www.mojoparts.com/media/catalog/product".$row['value']);
					}
				}
			}
		}
		
		// get any info from PFG's daily stock file
		$productCost = "";
		$sAndHCost = "";
		$totalCost = "";
		$listPrice = "";
		$ourPrice = "";
		$stockQty = 0;

		if ($isVendorSkuASet) {
			$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where sku in ('{$vendorSku1}', '{$vendorSku2}') order by sku");
			if (mysqli_num_rows($result) == 4) { 
				$row1 = mysqli_fetch_array($result);
				$row2 = mysqli_fetch_array($result);
				$row3 = mysqli_fetch_array($result);
				$row4 = mysqli_fetch_array($result);
				$stockQty = min($row1['qty']+$row2['qty'], $row3['qty']+$row4['qty']);
				$productCost = $row1['cost'] + $row3['cost'];
				$shippingCost = max($row1['shipping_cost'], $row3['shipping_cost']);
				$handlingCost = $row1['handling_cost'] + $row3['handling_cost'];
				$sAndHCost = $shippingCost + $handlingCost;
				$totalCost = $productCost + $sAndHCost;
				$listPrice = $row1['list_price'] + $row3['list_price'];
				$ourPrice = round(max(($totalCost+0.3+$markupFloor)/0.898,($totalCost+0.3)*$markup/(1-0.102*$markup)),2);
			} else {
				$processThisSku = FALSE;
echo "skipped ".$vendorSku1.",".$vendorSku2." - wasn't found in import file<br/>";
			}
		} else {
			$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where sku='{$vendorSku}'");
			$firstIteration = TRUE;
			if (mysqli_num_rows($result) == 2) { 
				while ($row = mysqli_fetch_array($result)) { 
					$stockQty = $stockQty + $row['qty'];
					if ($firstIteration) {
						$productCost = $row['cost'];
						$shippingCost = $row['shipping_cost'];
						$handlingCost = $row['handling_cost'];
						$sAndHCost = $shippingCost+$handlingCost;
						$totalCost = $productCost+$sAndHCost;
						$listPrice = $row['list_price'];
						$ourPrice = round(max(($totalCost+0.3+$markupFloor)/0.898,($totalCost+0.3)*$markup/(1-0.102*$markup)),2);
						$firstIteration = FALSE;
					} 
				}
			} else {
				$processThisSku = FALSE;
echo "skipped ".$vendorSku." - wasn't found in import file<br/>";
			}
		}

		if ($processThisSku)
		{
			// search for details table in html
			$detailsTableStartPattern = "class=\"paTbl";
			$detailsLabelStartPattern = "<strong style=";
			$detailsLabelEndPattern = "</strong>";
			$detailsValueStartPattern = "<td style=";
			$detailsValueEndPattern = "</td></tr>";
			$detailsTableEndPattern = "</table>";
			$detailsTableStartPos = strpos($listingHtml, $detailsTableStartPattern);
			$detailsLabelStartPos = strpos($listingHtml, $detailsLabelStartPattern, $detailsTableStartPos)+34;
			$detailsLabelEndPos = strpos($listingHtml, $detailsLabelEndPattern, $detailsLabelStartPos);
			$detailsValueStartPos = strpos($listingHtml, $detailsValueStartPattern, $detailsLabelEndPos)+45;
			$detailsValueEndPos = strpos($listingHtml, $detailsValueEndPattern, $detailsValueStartPos);
			$detailsTableEndPos = strpos($listingHtml, $detailsTableEndPattern, $detailsLabelStartPos);

			while ($detailsLabelStartPos > 0 && $detailsLabelStartPos < $detailsTableEndPos) {
				$detailsLabel = substr($listingHtml, $detailsLabelStartPos, $detailsLabelEndPos-$detailsLabelStartPos);
				$detailsValue = substr($listingHtml, $detailsValueStartPos, $detailsValueEndPos-$detailsValueStartPos);

				if ($detailsLabel == "Material" ||
					$detailsLabel == "Anticipated ship out time" ||
					$detailsLabel == "Quantity sold" ||
					$detailsLabel == "Product fit" ||
					$detailsLabel == "Color/finish" ||
					$detailsLabel == "Condition") 
				{ 
				// do nothing, skip these
				} else {
					switch ($detailsLabel) {
						case "Replaces oe number":
							$oemnumber = $detailsValue;
							break;
						case "Replaces partslink number":
							$partslink = $detailsValue;
							break;
						case "Notes":
							$notes = $detailsValue;
							break;
						case "Material":
							$material = $detailsValue;
							break;
						case "":
							break;
						default:
							array_push($additionalDetailArray, array($detailsLabel, $detailsValue));
					}
				}
				
				$detailsLabelStartPos = strpos($listingHtml, $detailsLabelStartPattern, $detailsLabelEndPos)+34;
				$detailsLabelEndPos = strpos($listingHtml, $detailsLabelEndPattern, $detailsLabelStartPos);
				$detailsValueStartPos = strpos($listingHtml, $detailsValueStartPattern, $detailsLabelEndPos)+45;
				$detailsValueEndPos = strpos($listingHtml, $detailsValueEndPattern, $detailsValueStartPos);
			}


			// process the item specifics
			foreach ($item->ItemSpecifics->NameValueList as $specific) {
				if ($specific->Name == "Returns Accepted"
					|| $specific->Name == "Refund will be given as"
					|| $specific->Name == "Item must be returned within"
					|| $specific->Name == "Return shipping will be paid by"
					|| $specific->Name == "Restocking Fee"
					|| $specific->Name == "Return policy details"
					|| $specific->Name == "Warranty"
					|| $specific->Name == "Warranty Terms"
					|| $specific->Name == "Part Number") 
				{
					// do nothing, skip these
				} else {
					switch ($specific->Name) {
						case "Manufacturer Part Number":
							if ($oemnumber == "") { // use the oem from the detail table if it's available
								$oemnumber = $specific->Value;
							}
							break;
						case "Interchange Part Number":
							$hollander = $specific->Value;
							break;
						case "Surface Finish":
							$surfaceFinish = $specific->Value;
							break;
						case "Placement on Vehicle":
							$placementOnVehicle = $specific->Value;
							break;
						case "UPC":
							$upc = $specific->Value;
							break;
						case "Part Brand":
							$brand = $specific->Value;
							break;
						default:
							array_push($additionalDetailArray, array($specific->Name, $specific->Value));
					}
				}
			}
			
			// now that we have all the additional specifics and notes, store them in a string
			foreach ($additionalDetailArray as $ada) {
				$additionalNotes = $additionalNotes.implode(": ", $ada).PHP_EOL;
			}
			
			foreach($item->ItemCompatibilityList->Compatibility as $compat) {
				$compatibility .= "Year=".$compat->NameValueList[1]->Value;
				$compatibility .= "|Make=".$compat->NameValueList[2]->Value;
				$compatibility .= "|Model=".$compat->NameValueList[3]->Value;
				if (isset($compat->NameValueList[4]->Value)) {
					$compatibility .= '|Trim='.str_replace('"','',$compat->NameValueList[4]->Value);
				}
				if (isset($compat->NameValueList[5]->Value)) {
					$compatibility .= '|Engine='.str_replace('"','',$compat->NameValueList[5]->Value);
				}
				if (isset($compat->CompatibilityNotes) && $compat->CompatibilityNotes<>"") {
					$compatibility .= '|Notes='.str_replace('"','',$compat->CompatibilityNotes);
				}
				$compatibility .= PHP_EOL;
			}
			$listingPrice = $item->CurrentPrice;
		}
	}

	if ($processThisSku) { 
		// display the page
		include('ebaycopy.html'); 
		break;
	}
	else {
		// since this one wasn't found in our stock table, remove it and get the next listing to copy
		mysqli_query($con, "DELETE FROM mojo_ebaycopy_input_listings WHERE ebay_id = ('{$ebay_id}')"); 
		$input_result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_input_listings limit 1");
	} 
}
if (mysqli_num_rows($input_result) == 0) { 
	echo "<h1>Whew, done.</h1>";
}

$result->close(); 
$input_result->close(); 
$mysqli->close();
?>

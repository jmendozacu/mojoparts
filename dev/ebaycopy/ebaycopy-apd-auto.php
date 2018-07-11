<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$input_result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_input_listings where processed_flag=0");
$numRows = mysqli_num_rows($input_result);
$insertCount = 0;
$updateCount = 0;
$rowNum = 0;
while ($input_row = mysqli_fetch_array($input_result)) { 

	$rowNum++;
	echo "processing record ".$rowNum." of ".$numRows.PHP_EOL;
	$skuAlreadyExists = FALSE;
	$ebay_id = $input_row['ebay_id']; 
	$additionalNotes = "";
	$additionalDetailArray = array();
	$capa = FALSE;
	$compatibility = "";
	$ebayCategory = NULL;
	$ebayStoreCategory = "";
	$hollander = "";
	$isVendorSkuASet = FALSE;
	$magentoCategory = "";
	$manufacturerPartNumber = 0;
	$oemnumber = "";
	$partslink = "";
	$placementOnVehicle = NULL;
	$processThisSku = TRUE;
	$restricted = FALSE;
	$reviewCode = "ebaycopy-apd-".date("Y.m.d");
	$side = "";
	$surfaceFinish = ""; 
	$upc = "Does Not Apply";
	$vendor = "PFG";
	$vendorSku1 = "";
	$vendorSku2 = "";

	// get the source listing info
	$apicall = "http://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=XML&appid=MojoPart-34e5-49b0-aab3-c8aa62626923&siteid=0&version=515";
	$apicall .= "&ItemID=".$ebay_id;
	$apicall .= "&includeSelector=Details,Compatibility,ItemSpecifics,Description";
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
    } else {
		$item = simplexml_load_string($curl_result)->Item;
		curl_close($ch); 

		$ebayCategory = $item->PrimaryCategoryID;
		$ebayTitle = $item->Title;
		$listingHtml = $item->Description;

		// load the category details
		$result = mysqli_query($con, "SELECT * FROM mojo_category_lookup where ebay_category='{$ebayCategory}'");
		switch (mysqli_num_rows($result)) {
			case 1:
				$row = mysqli_fetch_array($result);
				$ebayStoreCategory = $row['ebay_store_category'];
				$magentoCategory = $row['magento_category'];
				break;
			default:
				$ebayStoreCategory = "";
				$magentoCategory = "365"; // Unknown eBay Category
		}
		
		// search for vendor sku in html
		$vendorSkuStartPattern = "<strong style=\"font-size:12px; color:#006600;\"></strong><span style=\"margin-left:10px; color:#fff;\">TBT";
		$vendorSkuEndPattern = "</span>";
		$vendorSkuStartPos = strpos($listingHtml, $vendorSkuStartPattern);
		$vendorSkuEndPos = strpos($listingHtml, $vendorSkuEndPattern, $vendorSkuStartPos);
		$vendorSku = substr($listingHtml, $vendorSkuStartPos+103, $vendorSkuEndPos-$vendorSkuStartPos-103);
		if (substr($vendorSku, -1) == "Q") { 
			$capa = TRUE; 
		}
//echo $vendorSku.PHP_EOL;
		if (substr($vendorSku,0,3) == "TBT") { 
			$vendorSku = substr($vendorSku,3);
		}
echo $vendorSku.PHP_EOL;
				
		// Handle pairs
		if (substr($vendorSku, 0, 4) != "SET-" && substr($vendorSku, 0, 4) != "KIT-") {
			$result = mysqli_query($con, "SELECT * FROM mojo_vendor_item_master where vendor_item_number='{$vendorSku}'");
			if (mysqli_num_rows($result) <> 1) {
				$processThisSku = FALSE;
			}
		} else {
			$isVendorSkuASet = TRUE;
			$vendorSku1 = substr($vendorSku, 4);
			$result = mysqli_query($con, "SELECT * FROM mojo_vendor_item_master where vendor='PFG' and vendor_item_number = '{$vendorSku1}' limit 1");
			if (mysqli_num_rows($result) == 0 && substr($vendorSku1, -2) == "-2") { 
				$vendorSku1 = substr($vendorSku1, 0, -2);
				$result = mysqli_query($con, "SELECT * FROM mojo_vendor_item_master where vendor='PFG' and vendor_item_number = '{$vendorSku1}' limit 1");
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
							$reviewCode = "invalid_item_number";
						} else {
							$vendorSku2Description = str_replace("LH", "RH", $vendorSku1Description);
							$result = mysqli_query($con, "SELECT * FROM mojo_vendor_item_master where description = '{$vendorSku2Description}' limit 1");
							if (mysqli_num_rows($result) == 1) {
								$row = mysqli_fetch_array($result);
								$vendorSku2 = $row['vendor_item_number'];
							} else {
								$reviewCode = "invalid_item_number";
							}
						}
					} else {
						$vendorSku2Description = str_replace("RH", "LH", $vendorSku1Description);
						$result = mysqli_query($con, "SELECT * FROM mojo_vendor_item_master where description = '{$vendorSku2Description}' limit 1");
						if (mysqli_num_rows($result) == 1) {
							$row = mysqli_fetch_array($result);
							$vendorSku2 = $row['vendor_item_number'];
						} else {
							$reviewCode = "invalid_item_number";
						}
					}
				}
			} else {
				$reviewCode = "invalid_item_number";
			}
		}

		// check to see if item is restricted
		$querySku = $vendorSku;
		if ($isVendorSkuASet) { $querySku = $vendorSku1; }
		$result = mysqli_query($con, "select * from mojo_pfg_patents where pfg_item = '{$querySku}'");
		if (mysqli_num_rows($result) == 1) { $processThisSku = FALSE; }		

		if ($processThisSku) {

			// search for details table in html
			$detailsTableStartPattern = "class=\"paTbl";
			$detailsLabelStartPattern = "<strong>";
			$detailsLabelEndPattern = "</strong>";
			$detailsValueStartPattern = "<td width = ";
			$detailsValueEndPattern = "</td></tr>";
			$detailsTableEndPattern = "</table>";
			$detailsTableStartPos = strpos($listingHtml, $detailsTableStartPattern);
			$detailsLabelStartPos = strpos($listingHtml, $detailsLabelStartPattern, $detailsTableStartPos)+8;
			$detailsLabelEndPos = strpos($listingHtml, $detailsLabelEndPattern, $detailsLabelStartPos);
			$detailsValueStartPos = strpos($listingHtml, $detailsValueStartPattern, $detailsLabelEndPos)+20;
			$detailsValueEndPos = strpos($listingHtml, $detailsValueEndPattern, $detailsValueStartPos);
			$detailsTableEndPos = strpos($listingHtml, $detailsTableEndPattern, $detailsLabelStartPos);

			while ($detailsLabelStartPos > 0 && $detailsLabelStartPos < $detailsTableEndPos) {
				$detailsLabel = substr($listingHtml, $detailsLabelStartPos, $detailsLabelEndPos-$detailsLabelStartPos);
				$detailsValue = substr($listingHtml, $detailsValueStartPos, $detailsValueEndPos-$detailsValueStartPos);
				if ($detailsLabel != "Anticipated ship out time" &&
					$detailsLabel != "Product fit" && 
					$detailsLabel != "Recommended use" && 
					$detailsLabel != "Condition" && 
					$detailsLabel != "Deals" && 
					$detailsLabel != "Quantity sold") { 
					switch ($detailsLabel) {
						case "Replaces oe number":
							if (strlen(trim($detailsValue)) >= 7) {
								$oemnumber = trim($detailsValue);
							}
							break;
						case "Replaces partslink number":
							if (strlen(trim($detailsValue)) >= 7) {
								$partslink = trim($detailsValue);
							}
							break;
						case "Location":
							switch ($detailsValue) {
								case "Left":
									$side="L";
									$placementOnVehicle = trim($detailsValue);
									break;
								case "Right":
									$side="R";
									$placementOnVehicle = trim($detailsValue);
									break;
								case "Left and Right":
								case "Right and Left":
									$placementOnVehicle = "Left,Right"; // the item specific doesn't specify "and" vs. "or"
									break;
								case "Left or Right":
								case "Right or Left":
									$placementOnVehicle = "Left,Right"; // the item specific doesn't specify "and" vs. "or"
									if (!$isVendorSkuASet) {
										$side="B";
									}
									break;
							}
							break;
						case "":
							break;
						default:
							array_push($additionalDetailArray, array($detailsLabel, $detailsValue));
					}
				}
					
					$detailsLabelStartPos = strpos($listingHtml, $detailsLabelStartPattern, $detailsLabelEndPos)+8;
					$detailsLabelEndPos = strpos($listingHtml, $detailsLabelEndPattern, $detailsLabelStartPos);
					$detailsValueStartPos = strpos($listingHtml, $detailsValueStartPattern, $detailsLabelEndPos)+20;
					$detailsValueEndPos = strpos($listingHtml, $detailsValueEndPattern, $detailsValueStartPos);
			}

			// process the Item Specifics
			foreach ($item->ItemSpecifics->NameValueList as $specific) {
				if ($specific->Name == "Returns Accepted"
					|| $specific->Name == "Refund will be given as"
					|| $specific->Name == "Item must be returned within"
					|| $specific->Name == "Return shipping will be paid by"
					|| $specific->Name == "Restocking Fee"
					|| $specific->Name == "Return policy details"
					|| $specific->Name == "Warranty"
					|| $specific->Name == "Warranty Terms"
					|| $specific->Name == "Part Number") {

				// do nothing, skip these

				} else {
					switch ($specific->Name) {
						case "Manufacturer Part Number":
							if (strlen(trim($specific->Value)) >= 7) {
								$manufacturerPartNumber = trim($specific->Value);
							}
							break;
						case "Interchange Part Number":
							if (strlen(trim($specific->Value)) >= 7) {
								$hollander = trim($specific->Value);
							}
							break;
						case "Part Link Number":
							if (strlen(trim($specific->Value)) >= 7) {
								$partslink = trim($specific->Value);
							}
							break;
						case "OEM Number":
							if (strlen(trim($specific->Value)) >= 7) {
								$oemnumber = trim($specific->Value);
							}
							break;
						case "Surface Finish":
							$surfaceFinish = trim($specific->Value);
							break;
						case "Placement on Vehicle":
							switch (trim($specific->Value)) {
								case "Left":
								case "Front,Left":
								case "Rear,Left":
								case "Left,Front":
								case "Left,Rear":
									$placementOnVehicle = trim($specific->Value);
									$side = "L";
									break;
								case "Right":
								case "Front,Right":
								case "Rear,Right":
								case "Right,Front":
								case "Right,Rear":
									$placementOnVehicle = trim($specific->Value);
									$side = "R";
									break;
								case "Left,Right":
								case "Right,Left":
								case "Front,Left,Right":
								case "Front,Right,Left":
								case "Rear,Left,Right":
								case "Rear,Right,Left":
								case "Left,Right,Front":
								case "Right,Left,Front":
								case "Left,Right,Rear":
								case "Right,Left,Rear":
									$placementOnVehicle = trim($specific->Value);
									if (!$isVendorSkuASet) {
										$side = "B";
									}
									break;
							}
							break;
						case "UPC":
							$upc = trim($specific->Value);
							break;
						case "Brand":
							$brand = trim($specific->Value);
							break;
						case "Part Brand":
							$brand = trim($specific->Value);
							break;
						case "Restrictions":
							if ($specific->Value == "FOR RETAIL PURCHASE ONLY, not for bulk, resale or wholesale buys") {
								$restricted = TRUE;
							}
							break;
						default:
							array_push($additionalDetailArray, array($specific->Name, $specific->Value));
					}
				}
			}
			
			// now that we have all the additional specifics and notes, store them in a string
			if (!empty($additionalDetailArray)) {
				foreach ($additionalDetailArray as $ada) {
					$additionalNotes = $additionalNotes.implode(": ", $ada).PHP_EOL;
				}
			}
			
			if (!empty($item->ItemCompatibilityList->Compatibility)) {

				$epidAr = array();
				foreach($item->ItemCompatibilityList->Compatibility as $compat) {
					$epid_query = NULL;
					$year = $compat->NameValueList[1]->Value;
					$make = $compat->NameValueList[2]->Value;
					$model = $compat->NameValueList[3]->Value;
					$epid_query = "select e.epid from m2epro_ebay_dictionary_motor_epid e where e.year='$year' and e.make='$make' and e.model='$model'";
					if (isset($compat->NameValueList[4]->Value)) {
						$epid_query .= " and e.trim='".str_replace("'","''",$compat->NameValueList[4]->Value)."'";
					}
					if (isset($compat->NameValueList[5]->Value)) {
						$epid_query .= " and e.engine='".$compat->NameValueList[5]->Value."'";
					}

					$epid_query .= ";";
//echo $epid_query.PHP_EOL;
					$result_epid = mysqli_query($con, $epid_query);
					if (!$result_epid) {
						printf("Error: %s\n", mysqli_error($con));
						exit();
					}
					while ($epid_row = mysqli_fetch_array($result_epid)) {
						$m2e_epid = '""ITEM""|""'.$epid_row['epid'].'""';
						if ($compat->CompatibilityNotes != null) $m2e_epid .= '|""'.$compat->CompatibilityNotes.'""';
						array_push($epidAr, $m2e_epid);
					}
				}
				if (empty($epidAr)) $compatibility = "";
				else $compatibility = implode(",",$epidAr);
			}

			$listingPrice = $item->CurrentPrice;

			/*************************************************************
			/* OUTPUT THE DATA THAT WAS SCRAPED
			**************************************************************/
			// build images fields
			$chp = curl_init();
			curl_setopt($chp, CURLOPT_FAILONERROR, 1);
			curl_setopt($chp, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($chp, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($chp, CURLOPT_TIMEOUT, 15);
			$imageSku = $vendorSku;
			if ($isVendorSkuASet) {
				$imageSku = $vendorSku1;
			}
			$mainImageURL = '';
			$mediaGalleryURLs = '';
			if ($isVendorSkuASet) {
				$mainImageURL = '+'.$item->PictureURL[0];
			}
			for ($i = 9; $i > 0; $i--) {
				$picString = 'http://img.ptimg.com/is/image/Autos/'.strtolower($imageSku).'_'.$i.'?scl=2';
				curl_setopt($chp, CURLOPT_URL, $picString);
				$returnedp = curl_exec($chp);
				if (!$returnedp) { 
					$picString = 'http://img.ptimg.com/is/image/Autos/'.strtolower($imageSku).'_'.$i.'?scl=3';
					curl_setopt($chp, CURLOPT_URL, $picString);
					$returnedp = curl_exec($chp);
				}
				if ($returnedp) { 
					if ($i == 1 && !$isVendorSkuASet) {
						$mainImageURL = '+'.$picString;
					} 
					$mediaGalleryURLs = $mediaGalleryURLs.'+'.$picString.';';
				}
			}
			$result = mysqli_query($con, "select im.vendor_item_number, im.component_1, im.component_2 from mojo_vendor_item_master im where im.vendor_item_number='{$vendorSku}' limit 1");

			if (mysqli_num_rows($result) == 1) { 
				$row = mysqli_fetch_array($result);
				$component1 = $row['component_1']; 
				$component2 = $row['component_2']; 
				if ($component1 != NULL && $component1 != '') { $vendorSku1 = $component1; }
				if ($component2 != NULL && $component2 != '') { $vendorSku2 = $component2; }
				$updateSQL = "UPDATE `mojo_vendor_item_master` set component_1='{$vendorSku1}', component_2='{$vendorSku2}',";
				if ($hollander != "") { $updateSQL = $updateSQL."interchange='{$hollander}',"; }
				if ($partslink != "") { $updateSQL = $updateSQL."partslink='{$partslink}', "; }
				if ($oemnumber != "") { $updateSQL = $updateSQL."oem='{$oemnumber}',"; } 
				if ($upc != "") { $updateSQL = $updateSQL."upc='{$upc}', "; }
				if ($manufacturerPartNumber != "") { $updateSQL = $updateSQL."mpn='{$manufacturerPartNumber}',"; } 
				if ($ebayTitle != "") { $updateSQL = $updateSQL."ebay_title='{$ebayTitle}', description='{$ebayTitle}', "; }
// TODO: add description_scrubbed
				if ($listingPrice != "") { $updateSQL = $updateSQL."ebay_price='{$listingPrice}', "; }
				if ($ebayCategory != "") { $updateSQL = $updateSQL."ebay_category='{$ebayCategory}', "; }
				if ($ebayStoreCategory != "") { $updateSQL = $updateSQL."ebay_store_category='{$ebayStoreCategory}', "; }
				if ($compatibility != "") { $updateSQL = $updateSQL."compatibility='{$compatibility}', "; }
				if ($additionalNotes != "") { $updateSQL = $updateSQL."additional_notes='{$additionalNotes}', "; }
				if ($magentoCategory != "") { $updateSQL = $updateSQL."magento_category='{$magentoCategory}', "; }
				if ($placementOnVehicle != "") { $updateSQL = $updateSQL."placement_on_vehicle='{$placementOnVehicle}', "; }
				if ($surfaceFinish != "") { $updateSQL = $updateSQL."surface_finish='{$surfaceFinish}', "; }
				if ($mainImageURL != "") { $updateSQL = $updateSQL."main_image_url='{$mainImageURL}', "; }
				if ($mediaGalleryURLs != "") { $updateSQL = $updateSQL."other_image_urls='{$mediaGalleryURLs}',"; }
				$updateSQL = $updateSQL."review_code='{$reviewCode}', ebay_id='{$ebay_id}', update_date=curdate() where vendor_item_number='{$vendorSku}'";
//				echo $updateSQL.PHP_EOL;
				mysqli_query($con, $updateSQL); 
				$updateCount++;
				echo "success [".$vendorSku."]: updated: ".$updateCount.PHP_EOL;
			} else {
				$insertSQL = "INSERT INTO `mojo_vendor_item_master` (`vendor`, `vendor_item_number`, `component_1`, `component_2`, `description`, `interchange`, `partslink`, `oem`, `upc`, `mpn`, `ebay_title`, `ebay_price`, `ebay_category`, `ebay_store_category`, `compatibility`, `additional_notes`, `magento_category`, `placement_on_vehicle`, `surface_finish`, `main_image_url`, `other_image_urls`, `review_code`,`ebay_id`, `create_date`, `update_date`) VALUES ('PFG','{$vendorSku}','{$vendorSku1}','{$vendorSku2}','{$ebayTitle}', '{$hollander}', '{$partslink}', '{$oemnumber}', '{$upc}', '{$manufacturerPartNumber}', '{$ebayTitle}', '{$listingPrice}', '{$ebayCategory}', '{$ebayStoreCategory}', '{$compatibility}', '{$additionalNotes}', '{$magentoCategory}', '{$placementOnVehicle}', '{$surfaceFinish}', '{$mainImageURL}', '{$mediaGalleryURLs}', '{$reviewCode}', '{$ebay_id}', curdate(), curdate())";
//				echo $insertSQL.PHP_EOL;
				mysqli_query($con, $insertSQL); 
				$insertCount++;
				echo "success [".$vendorSku."]: inserted: ".$insertCount.PHP_EOL;
			}
		} else {
			echo "skipped [".$vendorSku."]: not found or restricted.".PHP_EOL;
		}
	}
} 

$result->close(); 
$input_result->close(); 
echo "--------------------------------------------------".PHP_EOL;
echo "records inserted: ".$insertCount.PHP_EOL;
echo "records updated: ".$updateCount.PHP_EOL;
echo "Whew, done.".PHP_EOL;
?>

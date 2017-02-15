<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$markup = 1.15;
$markupFloor = 5;
// load the listing information
$input_result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_input_listings");
while ($input_row = mysqli_fetch_array($input_result)) { 
	// clear the variable values
	$skuAlreadyExists = FALSE;
	$ebay_id = $input_row['ebay_id']; 
	$additionalNotes = "";
	$additionalDetailArray = array();
	$brand = "Aftermarket Replacement";
	$capa = FALSE;
	$categoryArray = array();
	$compatibility = "";
	$ebayCategory = "";
	$ebayStoreCategory = "";
	$hollander = "";
	$isVendorSkuASet = FALSE;
	$magentoCategory = "";
	$magentoImageCount = 0;
	$manufacturerPartNumber = 0;
	$oemnumber = "";
	$partslink = "";
	$placementOnVehicle = "";
	$processThisSku = TRUE;
	$side = "";
	$skuExistsInOutput = FALSE;
	$skuPrefix = "";
	$skuNumber = ""; 
	$surfaceFinish = ""; 
	$upc = "Does Not Apply";
	$vendor = "PFG";
	$vendorSku1 = "";
	$vendorSku1RHLH = "";
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
		$processThisSku = FALSE;
    } else {
		$item = simplexml_load_string($curl_result)->Item;
		curl_close($ch); 

		$ebayCategory = $item->PrimaryCategoryID;
		$ebayCategoryName = $item->PrimaryCategoryName;
		$ebayTitle = $item->Title;
		$listingHtml = $item->Description;

		// load the category details
		$result = mysqli_query($con, "SELECT * FROM mojo_category_lookup where ebay_category='{$ebayCategory}'");
		switch (mysqli_num_rows($result)) {
			case 1:
				$row = mysqli_fetch_array($result);
				$ebayStoreCategory = $row['ebay_store_category'];
				$magentoCategory = $row['magento_category'];
				$skuPrefix = $row['prefix'];
				break;
			case 0:
				$processThisSku = FALSE;
				echo "*** ERROR [".$ebay_id."]: skipped listing - can't find source listing's eBay category [".$ebayCategory."] in lookup table.".PHP_EOL;
				break;
			default:
				$processThisSku = FALSE;
				echo "*** ERROR [".$ebay_id."]: skipped listing - source listing's eBay category [".$ebayCategory."] found multiple times in lookup table.".PHP_EOL;
		}
		
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
	
		// search for details table in html
		// (we need to do this first because we use the part#s to determine if sku already exists)
		$detailsTableStartPattern = "class=\"paTbl";
		$detailsLabelStartPattern = "<strong style=";
		$detailsLabelEndPattern = "</strong>";
		$detailsValueStartPattern = "<td style=";
		$detailsValueEndPattern = "</td></tr>";
		$detailsTableEndPattern = "</table>";
		$detailsTableStartPos = strpos($listingHtml, $detailsTableStartPattern);
		$detailsLabelStartPos = strpos($listingHtml, $detailsLabelStartPattern, $detailsTableStartPos)+34;
		$detailsLabelEndPos = strpos($listingHtml, $detailsLabelEndPattern, $detailsLabelStartPos);
		$detailsValueStartPos = strpos($listingHtml, $detailsValueStartPattern, $detailsLabelEndPos)+44;
		$detailsValueEndPos = strpos($listingHtml, $detailsValueEndPattern, $detailsValueStartPos);
		$detailsTableEndPos = strpos($listingHtml, $detailsTableEndPattern, $detailsLabelStartPos);

		while ($detailsLabelStartPos > 0 && $detailsLabelStartPos < $detailsTableEndPos) {
			$detailsLabel = substr($listingHtml, $detailsLabelStartPos, $detailsLabelEndPos-$detailsLabelStartPos);
			$detailsValue = substr($listingHtml, $detailsValueStartPos, $detailsValueEndPos-$detailsValueStartPos);

			if ($detailsLabel != "Anticipated ship out time" &&
				$detailsLabel != "Product fit" && 
				$detailsLabel != "Recommended use" && 
				$detailsLabel != "Condition" && 
				$detailsLabel != "Quantity sold") 
			{ 
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
			
			$detailsLabelStartPos = strpos($listingHtml, $detailsLabelStartPattern, $detailsLabelEndPos)+34;
			$detailsLabelEndPos = strpos($listingHtml, $detailsLabelEndPattern, $detailsLabelStartPos);
			$detailsValueStartPos = strpos($listingHtml, $detailsValueStartPattern, $detailsLabelEndPos)+44;
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
							$processThisSku = FALSE;
							echo "... info [".$ebay_id."]: [".$vendorSku."] is restricted".PHP_EOL;						
						}
						break;
					default:
						array_push($additionalDetailArray, array($specific->Name, $specific->Value));
						echo "... NEW ITEM SPECIFIC: [".$ebay_id."]:  [".$specific->Name."] = [".$specific->Value."]".PHP_EOL;
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
					if ($side == "") {
						$lhPos = strpos($compat->CompatibilityNotes, "Driver");
						$rhPos = strpos($compat->CompatibilityNotes, "Passenger");
						$lhOrRhPos = strpos($compat->CompatibilityNotes, "Driver or Passenger");
						$rhOrLhPos = strpos($compat->CompatibilityNotes, "Passenger or Driver");
						if ($rhPos !== FALSE && $lhPos === FALSE) {
							$side = "R";
						} else {
							if ($lhPos !== FALSE && $rhPos === FALSE) {
								$side = "L";
							} else {
								if ($rhOrLhPos !== FALSE || $lhOrRhPos !== FALSE) {
									$side = "B";
								}
							}
						}
					}
				}
				$compatibility .= PHP_EOL;
			}
		}
		$listingPrice = $item->CurrentPrice;
	
	
	
		// 1. determine if the sku has been added to the output file during this run
		$tempVendorSku = $vendorSku;
		if ($isVendorSkuASet) {
			$tempVendorSku = $vendorSku1.",".$vendorSku2;
		} 
		$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_new_output where vendor_item = '{$tempVendorSku}' limit 1");
		if (mysqli_num_rows($result) == 1) { 
			$row = mysqli_fetch_array($result);
			$skuExistsInOutput = TRUE; 
			$skuNumber = $row['sku']; 
		} else {
		
			// 2. determine if the sku is already in magento
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

			// look for a *hard* match on another product identifier
			} else {
				$pIDs = array();
				if ($partslink != "0" && $partslink != "") array_push($pIDs, $partslink);
				if ($oemnumber != "0" && $oemnumber != "") array_push($pIDs, $oemnumber);
				if ($hollander != "0" && $hollander != "") array_push($pIDs, $hollander);
				if ($manufacturerPartNumber != "0" && $manufacturerPartNumber != "") array_push($pIDs, $manufacturerPartNumber);
				if (count($pIDs) > 0) {
					$queryString = "select distinct cpe.sku from catalog_product_entity_varchar cv
						inner join catalog_product_entity cpe on cpe.entity_id=cv.entity_id
						inner join eav_attribute ea on ea.attribute_id=cv.attribute_id
						where cv.attribute_id in (138, 140, 142) and (";
					$firstPID = TRUE;
					foreach ($pIDs as $pID) {
						if (!$firstPID) {
							$queryString = $queryString." or ";
						}
						$queryString = $queryString."trim(cv.value) = '".trim($pID)."'";
						$firstPID = FALSE;
					}
					$queryString = $queryString.")";
					$result = mysqli_query($con, $queryString);
					$resultCount = mysqli_num_rows($result);
					switch ($resultCount) {
						case 0:
							// No hard match found.  Look for a *soft* match on another product identifier
							$queryString = "select distinct cpe.sku from catalog_product_entity_varchar cv
								inner join catalog_product_entity cpe on cpe.entity_id=cv.entity_id
								inner join eav_attribute ea on ea.attribute_id=cv.attribute_id
								where cv.attribute_id in (138, 140, 142) and (";
							$firstPID = TRUE;
							foreach ($pIDs as $pID) {
								$ipIDs = array();
								$ipIDs = explode(",", $pID);
								foreach ($ipIDs as $ipID) {
									if (!$firstPID) {
										$queryString = $queryString." or ";
									}
									$queryString = $queryString."trim(cv.value) like '%%".trim($ipID)."%%'";
									$firstPID = FALSE;
								}
							}
							$queryString = $queryString.")";
							$result = mysqli_query($con, $queryString);
							$resultCount = mysqli_num_rows($result);

							switch ($resultCount) {
								case 0:
									break;
								default:
									while ($row = mysqli_fetch_array($result)) { 
										$ipIDsku = $row['sku'];
										$ipIDskuSide="";
										$ipIDskuSide = substr($ipIDsku, -2);
										if ($ipIDskuSide != "LR") {
											$ipIDskuSide = substr($ipIDsku, -1);
											if ($ipIDskuSide != "L" && $ipIDskuSide != "R" && $ipIDskuSide != "B") {
												$ipIDskuSide = "";
											}
										}
										// since the $pid may be OEM# (which doesn't account for capa), check that too
										$matchCAPA = FALSE;
										$result = mysqli_query($con, "SELECT b.value FROM catalog_product_entity a inner join catalog_product_entity_varchar b on a.entity_id = b.entity_id and b.attribute_id = 164 where (substr(trim(b.value),-1)='Q' or substr(trim(b.value),-4)='CAPA') and sku = '{$row['sku']}' limit 1");
										if (((mysqli_num_rows($result) == 1) && $capa) ||
											((mysqli_num_rows($result) == 0) && !$capa)) {
											$matchCAPA = TRUE;
										}

										if (($ipIDskuSide != "") && 
											(($side == $ipIDskuSide) || ($isVendorSkuASet && $ipIDskuSide == "LR")) &&
											$matchCAPA) {
											
											// if we have already found a soft match, then a second one means to skip this and clear the variables
											if ($skuAlreadyExists) { 
												$processThisSku = FALSE;
												echo "*** MANUAL CHECK [".$ebay_id."]: possible bad magento data.  2nd *soft* match to existing sku (".$ipIDsku.") for partslink/OEM/hollander for [".$vendorSku."]".PHP_EOL;
												echo $queryString.PHP_EOL;
											} else { 
												// this is the first and only soft match so far, assume it's the match
												$processThisSku = FALSE;
												$skuAlreadyExists = TRUE;
												echo "*** MANUAL CHECK [".$ebay_id."]: possible mismatch in output files.  1st *soft* match to existing sku (".$ipIDsku.") for partslink/OEM/hollander for [".$vendorSku."]".PHP_EOL;
												echo $queryString.PHP_EOL;
											}
										}
									}
									break;
							}
							break;
						case 1:
							$row = mysqli_fetch_array($result);
							$skuNumber = $row['sku'];
							$skuAlreadyExists = TRUE;
							break;
						default:
							$processThisSku = FALSE; 
							echo "*** MANUAL CHECK [".$ebay_id."]: more than one hard match for partslink/OEM/hollander found for [".$vendorSku."]".PHP_EOL;
							echo $queryString.PHP_EOL;
					}
				}
			}
		}
		
		// get the magento data if the sku already exists
		if ($skuNumber != "") {

			// check which vendor this is with
			$result = mysqli_query($con, "SELECT  d.value FROM catalog_product_entity a inner join catalog_product_entity_int d on a.entity_id = d.entity_id where sku = '{$skuNumber}' and d.value=37 and d.attribute_id = 163");
			if (mysqli_num_rows($result) >= 1) { 
				$vendor = "Brock";
			} 
			
			// get images
			if ($processThisSku) {
				$result = mysqli_query($con, "SELECT value FROM catalog_product_entity a LEFT JOIN catalog_product_entity_media_gallery b ON a.entity_id = b.entity_id WHERE a.sku = '{$skuNumber}'");
				$magentoImageCount = mysqli_num_rows($result);
			}
		}
		
		// handle pricing
		if ($processThisSku) {
			// get any info from PFG's daily stock file
			$productCost = "";
			$sAndHCost = "";
			$totalCost = "";
			$listPrice = "";
			$ourPrice = "";
			$stockQty = 0;

			if ($isVendorSkuASet) {
				$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where sku in ('{$vendorSku1}', '{$vendorSku2}') order by sku");
				$resultCount = mysqli_num_rows($result);
				switch ($resultCount) {
					// this may be a set where RH=LH
					case 2:
						if ($vendorSku1 == $vendorSku2) {
							$row1 = mysqli_fetch_array($result);
							$row2 = mysqli_fetch_array($result);
							$productCost = $row1['cost'] * 2;
							$shippingCost = $row1['shipping_cost'];
							$handlingCost = $row1['handling_cost'] * 2;
							$sAndHCost = $shippingCost + $handlingCost;
							$totalCost = $productCost + $sAndHCost;
							$listPrice = $row1['list_price'] * 2;
							$ourPrice = round(max(($totalCost+0.3+$markupFloor)/0.898,($totalCost+0.3)*$markup/(1-0.102*$markup)),2);
							if ($productCost == 0 || $shippingCost == 0 || $handlingCost == 0) {
								$stockQty = 0;
							} else {
								$stockQty = $row1['qty']+$row2['qty'];
							}
						} else {
							$processThisSku = FALSE;
							echo "... info [".$ebay_id."]: skipped [".$vendorSku1."] and [".$vendorSku2."] weren't found enough times in the import file".PHP_EOL;
						}
						break;
					case 4:
						$row1 = mysqli_fetch_array($result);
						$row2 = mysqli_fetch_array($result);
						$row3 = mysqli_fetch_array($result);
						$row4 = mysqli_fetch_array($result);
						$productCost = $row1['cost'] + $row3['cost'];
						$shippingCost = max($row1['shipping_cost'], $row3['shipping_cost']);
						$handlingCost = $row1['handling_cost'] + $row3['handling_cost'];
						$sAndHCost = $shippingCost + $handlingCost;
						$totalCost = $productCost + $sAndHCost;
						$listPrice = $row1['list_price'] + $row3['list_price'];
						$ourPrice = round(max(($totalCost+0.3+$markupFloor)/0.898,($totalCost+0.3)*$markup/(1-0.102*$markup)),2);
						if ($productCost == 0 || $shippingCost == 0 || $handlingCost == 0) {
							$stockQty = 0;
						} else {
							$stockQty = $row1['qty']+$row2['qty'];
						}
						break;
					default:
						$processThisSku = FALSE;
						echo "... info [".$ebay_id."]: skipped [".$vendorSku1."] and [".$vendorSku2."] weren't found in both warehouses in the import file".PHP_EOL;
						break;
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
					if ($productCost == 0 || $shippingCost == 0 || $handlingCost == 0) {
						$stockQty = 0;
					}
				} else {
					$processThisSku = FALSE;
					echo "... info [".$ebay_id."]: skipped [".$vendorSku."] - wasn't found in import file".PHP_EOL;
				}
			}
		}
	}

	/*************************************************************
	/* WRITE THE OUTPUT FILES 
	**************************************************************/
	// write the new item to the output file
	if($processThisSku) {

		$skuSuffix = "";
		if ($isVendorSkuASet) {
			$skuSuffix = "LR";
		} else {
			$skuSuffix = $side;
		}
			
		// if we haven't found a matching sku already (to update), determine the sku# to use
		if ($skuNumber == "") {
			$matchedSkuNumber = "";
			if ($isVendorSkuASet) {
				$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value = '{$vendorSku1}' and cp.attribute_id=164");
				if (mysqli_num_rows($result) == 0) {
					$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value = '{$vendorSku2}' and cp.attribute_id=164");
					if (mysqli_num_rows($result) == 0) {
						$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_new_output where vendor_item = '{$vendorSku1}'");
						if (mysqli_num_rows($result) == 0) {
							$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_new_output where vendor_item = '{$vendorSku2}'");
							if (mysqli_num_rows($result) == 1) {
								$row = mysqli_fetch_array($result);
								$matchedSkuNumber = $row['sku'];
							}
						} else {
							$row = mysqli_fetch_array($result);
							$matchedSkuNumber = $row['sku'];
						}
					} else {
						$row = mysqli_fetch_array($result);
						$matchedSkuNumber = $row['sku'];
					}
				} else {
					$row = mysqli_fetch_array($result);
					$matchedSkuNumber = $row['sku'];
				}
			} else {
				// find the opposite side (or pair) and then lookup in magento / output
				$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_new_output where vendor_item = '{$vendorSku}' limit 1");
				$originalSideDescription = $row['description'];
				$RHpos = strpos($originalSideDescription, "RH");
				$otherSideDescription = "";
				if ($RHpos !== FALSE) {
					$otherSideDescription = str_replace("RH", "LH", $originalSideDescription);
				} else {
					$LHpos = strpos($originalSideDescription, "LH");
					if ($LHpos !== FALSE) {
						$otherSideDescription = str_replace("RH", "LH", $originalSideDescription);
					}
				}
				if ($otherSideDescription !== "")  {
					$result = mysqli_query($con, "SELECT * FROM mojo_pfg_inv_import where description = '{$otherSideDescription}' limit 1");
					if (mysqli_num_rows($result) == 1) {
						$row = mysqli_fetch_array($result);
						$otherSideVendorSku = $row['sku'];

						// The other side was found in the inventory file.  Now determine if it's in magento or the output file.
						$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value = '{$otherSideVendorSku}' and cp.attribute_id=164");
						if (mysqli_num_rows($result) == 0) {
							$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_new_output where vendor_item = '{$otherSideVendorSku}'");
							if (mysqli_num_rows($result) == 0) {
								// couldn't find other side, now try the pair
								$vendorSkuCombo1 = $vendorSku.",".$otherSideVendorSku;
								$vendorSkuCombo2 = $vendorSku.", ".$otherSideVendorSku;
								$vendorSkuCombo3 = $otherSideVendorSku.",".$vendorSku;
								$vendorSkuCombo4 = $otherSideVendorSku.", ".$vendorSku;
								$result = mysqli_query($con, "select * from catalog_product_entity_varchar cp inner join catalog_product_entity pe on cp.entity_id=pe.entity_id where cp.value in ('{$vendorSkuCombo1}','{$vendorSkuCombo2}','{$vendorSkuCombo3}','{$vendorSkuCombo4}') and cp.attribute_id=164 limit 1");
								if (mysqli_num_rows($result) == 0) {
									$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_new_output where vendor_item in ('{$vendorSkuCombo1}','{$vendorSkuCombo3}')");
									if (mysqli_num_rows($result) == 1) {
										$row = mysqli_fetch_array($result);
										$matchedSkuNumber = $row['sku'];
										echo "... info [".$ebay_id."]: for [".$vendorSku."], found [".$vendorSkuCombo1."/".$vendorSkuCombo3."] in output as [".$matchedSkuNumber."]".PHP_EOL;
									}
								} else {
									$row = mysqli_fetch_array($result);
									$matchedSkuNumber = $row['sku'];
									echo "... info [".$ebay_id."]: for [".$vendorSku."], found [".$vendorSkuCombo1."/".$vendorSkuCombo3."] in output as [".$matchedSkuNumber."]".PHP_EOL;
								}
							} else {
								$row = mysqli_fetch_array($result);
								$matchedSkuNumber = $row['sku'];
								echo "... info [".$ebay_id."]: for [".$vendorSku."], found [".$otherSideVendorSku."] in output as [".$matchedSkuNumber."]".PHP_EOL;
							}
						} else {
							$row = mysqli_fetch_array($result);
							$matchedSkuNumber = $row['sku'];
							echo "... info [".$ebay_id."]: for [".$vendorSku."], found [".$otherSideVendorSku."] in magento as [".$matchedSkuNumber."]".PHP_EOL;
						}
					}
				}
			}
			// we've tried all the possible queries to find if we already have the other side or the pair, now analyze the results
			if ($matchedSkuNumber !== "") {
				if ($isVendorSkuASet) {
					if (substr($matchedSkuNumber, -1) == "R" || substr($matchedSkuNumber, -1) == "L" || substr($matchedSkuNumber, -1) == "B") {
						$skuNumber = substr($matchedSkuNumber, 0, -1).$skuSuffix;
						echo "... info [".$ebay_id."]: created sku# [".$skuNumber."] since we found [".$matchedSkuNumber."] already in magento (or the output file).".PHP_EOL;
					} else {
						$processThisSku = FALSE;
						echo "*** ERROR [".$ebay_id."]: found matched sku [".$matchedSkuNumber."] for [".$vendorSku." (".$vendorSku1.", ".$vendorSku2.")] but didn't end in R, L, or B".PHP_EOL;
					}
				} else {
					if (($skuSuffix = "R" && substr($matchedSkuNumber, -1) == "L") || 
						($skuSuffix = "L" && substr($matchedSkuNumber, -1) == "R")) {
						$skuNumber = substr($matchedSkuNumber, 0, -1).$skuSuffix;
						echo "... info [".$ebay_id."]: created sku# [".$skuNumber."] since we found [".$matchedSkuNumber."] already in magento (or the output file).".PHP_EOL;
					} else {
						if (substr($matchedSkuNumber, -2) == "LR") {
							$skuNumber = substr($matchedSkuNumber, 0, -2).$skuSuffix;
							echo "... info [".$ebay_id."]: created sku# [".$skuNumber."] since we found [".$matchedSkuNumber."] already in magento (or the output file).".PHP_EOL;							
						} else {
							$processThisSku = FALSE;
							echo "*** ERROR [".$ebay_id."]: found matched sku [".$matchedSkuNumber."] for [".$vendorSku."] but didn't end in LR".PHP_EOL;
						}
					}
				}
			}
			// otherwise, this is a totally new sku, create a new sku number
			if ($skuNumber == "" && $processThisSku) {
				$result = mysqli_query($con, "SELECT * FROM mojo_sku_numbering WHERE prefix='{$skuPrefix}' limit 1");
				$row = mysqli_fetch_array($result);
				$skuNumber = $skuPrefix.str_pad($row['next_sequence'], 5, "0", STR_PAD_LEFT).$skuSuffix; 
				mysqli_query($con, "UPDATE mojo_sku_numbering SET next_sequence=next_sequence+1 WHERE prefix = '{$skuPrefix}'"); 
			}
		}

		// create the output records (to be used to import/update products into magento)
		if ($processThisSku) {
			$vendorSkuForInsert = $vendorSku;
			if ($isVendorSkuASet) {
				$vendorSkuForInsert = $vendorSku1.",".$vendorSku2;
			}
			$is_in_stock = 0;
			$qty = 0;
			if ($stockQty >= 5) {
				$is_in_stock = 1;
				$qty = 25;
			}
			// if the sku already exists in the output, then assume this is just more compatibilities for the same sku
			if ($skuExistsInOutput) {
				if ($skuAlreadyExists) {
					$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_update_output WHERE sku='{$skuNumber}' limit 1");
					$row = mysqli_fetch_array($result);
					$compatibility = $row['compatibility'].$compatibility;
					mysqli_query($con, "update mojo_ebaycopy_update_output set compatibility = '{$compatibility}' where sku = '{$skuNumber}'");
					echo "... info [".$ebay_id."]: [".$vendorSkuForInsert."] found again - added to existing compatibility for update output record for [".$skuNumber."]".PHP_EOL;
				} else {
					$result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_new_output WHERE sku='{$skuNumber}' limit 1");
					$row = mysqli_fetch_array($result);
					$compatibility = $row['compatibility'].$compatibility;
					mysqli_query($con, "update mojo_ebaycopy_new_output set compatibility = '{$compatibility}' where sku = '{$skuNumber}'");
					echo "... info [".$ebay_id."]: [".$vendorSkuForInsert."] found again - added to existing compatibility for new output record for [".$skuNumber."]".PHP_EOL;
				}
			} else {
				if ($skuAlreadyExists) {
					$magentoCategory = $magentoCategory.",271";
					mysqli_query($con, "INSERT INTO `mojo_ebaycopy_update_output` (`sku`, `category_ids`, `additional_notes`, `compatibility`,  `ebay_category`, `ebay_store_cat_a`, `ebay_upc`, `hollander`, `mpn`, `oemnumber`, `partslink`, `surface_finish`,`qty`,`is_in_stock`) 
					VALUES ('{$skuNumber}', '{$magentoCategory}', '{$additionalNotes}', '{$compatibility}', '{$ebayCategory}', '{$ebayStoreCategory}', '{$upc}', '{$hollander}', '{$manufacturerPartNumber}', '{$oemnumber}', '{$partslink}', '{$surfaceFinish}', '{$qty}', '{$is_in_stock}')"); 
					echo "success [".$ebay_id."]: updated [".$skuNumber."] using info from vendor sku [".$vendorSkuForInsert."]".PHP_EOL;

					// if this is cheaper with PFG and currently in stock, send a warning message to evaluate
					if ($vendor == "Brock" && $is_in_stock == 1) {
						$result = mysqli_query($con, "SELECT b.value as brockSku, p.value as brockPrice FROM catalog_product_entity a inner join catalog_product_entity_varchar b on a.entity_id = b.entity_id and b.attribute_id = 164 left outer join catalog_product_entity_decimal p on a.entity_id = p.entity_id and p.attribute_id=75 where a.sku='{$skuNumber}' limit 1");
						$row = mysqli_fetch_array($result);
						$brockPrice = $row['brockPrice'];
						$brockSku = $row['brockSku'];

						if ($ourPrice < $brockPrice) {
							echo "*** MANUAL CHECK [".$ebay_id."]: [".$skuNumber."] is cheaper from PFG [".$vendorSku."] at $".$ourPrice." than from Brock [".$brockSku."] at $".$brockPrice.PHP_EOL;
						}
					}
					
				} else {
					// get the stock status first
					$result = mysqli_query($con, "SELECT is_in_stock FROM catalog_product_entity a inner join catalog_product_entity_int fba on a.entity_id = fba.entity_id and fba.value=32 and fba.attribute_id = 162 left join cataloginventory_stock_item c on a.entity_id = c.product_id WHERE sku='{$skuNumber}' limit 1");
					$row = mysqli_fetch_array($result);
					$is_in_stock = $row['is_in_stock'];
					if ($is_in_stock == 1) {
						$qty = 25;
					} else {
						$qty = 0;
					}
					
					// new create the new product
					$magentoCategory = $magentoCategory.",270";
					mysqli_query($con, "INSERT INTO `mojo_ebaycopy_new_output` (`sku`, `category_ids`, `additional_notes`, `combined_pair_ind`, `compatibility`, `cost`, `description`, `ebay_category`, `ebay_store_cat_a`, `ebay_title`, `ebay_upc`, `hollander`, `is_in_stock`, `manufacturer`, `mpn`,`name`, `oemnumber`, `partslink`, `placement_on_vehicle`, `price`, `qty`, `short_description`, `surface_finish`, `vendor`, `vendor_item`) 
					VALUES ('{$skuNumber}', '{$magentoCategory}', '{$additionalNotes}', '{$isVendorSkuASet}', '{$compatibility}', '{$productCost}', '{$ebayTitle}', '{$ebayCategory}', '{$ebayStoreCategory}', '{$ebayTitle}', '{$upc}', '{$hollander}', '{$is_in_stock}', '{$brand}','{$manufacturerPartNumber}', '{$ebayTitle}', '{$oemnumber}', '{$partslink}', '{$placementOnVehicle}', '{$ourPrice}', '{$qty}', '{$ebayTitle}', '{$surfaceFinish}', '{$vendor}', '{$vendorSkuForInsert}')"); 
					echo "success [".$ebay_id."]: added [".$skuNumber."] for vendor sku [".$vendorSkuForInsert."]".PHP_EOL;
				}
			}

			// create the images file
			if ($magentoImageCount <= 1 && !$skuExistsInOutput) {
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
				for ($i = 9; $i > 0; $i--) {
					$picString = 'http://img.ptimg.com/is/image/Autos/'.strtolower($imageSku).'_'.$i.'?scl=2';
					curl_setopt($chp, CURLOPT_URL, $picString);
					$returnedp = curl_exec($chp);
					if ($returnedp) { 
						if ($i == 1) {
							if ($isVendorSkuASet) {
								$mainImageURL = '+'.$item->PictureURL[0];
							} else {
								$mainImageURL = '+'.$picString;
							}
						} 
						$mediaGalleryURLs = $mediaGalleryURLs.'+'.$picString.';';
					}
				}
				mysqli_query($con, "INSERT INTO `mojo_ebaycopy_pics_output` (`sku`, `image`, `small_image`, `thumbnail`, `media_gallery`) 
				VALUES ('{$skuNumber}','{$mainImageURL}','{$mainImageURL}','{$mainImageURL}','{$mediaGalleryURLs}')"); 
			} 
		}
	}
} 

$result->close(); 
$input_result->close(); 

echo "Whew, done.".PHP_EOL;
?>

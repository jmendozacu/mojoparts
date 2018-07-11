<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$input_result = mysqli_query($con, "SELECT * FROM mojo_ebaycopy_input_listings where processed_flag=0");
$numRows = mysqli_num_rows($input_result);
$insertCount = 0;
$rowNum = 0;

while ($input_row = mysqli_fetch_array($input_result)) { 
	$rowNum++;
	echo "processing record ".$rowNum." of ".$numRows.PHP_EOL;
	$skuAlreadyExists = FALSE;
	$ebay_id = $input_row['ebay_id']; 
	$additionalNotes = "";
	$additionalDetailArray = array();
	$ebay_epids = "";
	$hollander = "";
	$manufacturerPartNumber = 0;
	$oemnumber = "";
	$partslink = "";
	$placementOnVehicle = NULL;
	$processThisSku = TRUE;
	$side = "";
	$surfaceFinish = ""; 
	$upc = "Does Not Apply";
	$vendorSku = "";

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
				|| $specific->Name == "Specialty Reference"
				|| $specific->Name == "Part Number") {

			// do nothing, skip these

			} else {
				switch ($specific->Name) {
					case "Manufacturer Part Number":
						if (strlen(trim($specific->Value)) >= 7) {
							$manufacturerPartNumber = trim($specific->Value);
							$vendorSku = trim($specific->Value);
						}
						break;
					case "Interchange Part Number":
						if (strlen(trim($specific->Value)) >= 7) {
							$hollander = trim($specific->Value);
						}
						break;
					case "Partslink Number":
						if (strlen(trim($specific->Value)) >= 7) {
							$partslink = trim($specific->Value);
						}
						break;
					case "OEM Reference":
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
			if (empty($epidAr)) $ebay_epids = "";
			else $ebay_epids = implode(",",$epidAr);
		}

		/*************************************************************
		/* OUTPUT THE DATA THAT WAS SCRAPED
		**************************************************************/
		if ($hollander != "") { $updateSQL = $updateSQL."interchange='{$hollander}',"; }
		if ($partslink != "") { $updateSQL = $updateSQL."partslink='{$partslink}', "; }
		if ($oemnumber != "") { $updateSQL = $updateSQL."oem='{$oemnumber}',"; } 
		if ($upc != "") { $updateSQL = $updateSQL."upc='{$upc}', "; }
		if ($manufacturerPartNumber != "") { $updateSQL = $updateSQL."mpn='{$manufacturerPartNumber}',"; } 
		if ($ebay_epids != "") { $updateSQL = $updateSQL."ebay_epids='{$ebay_epids}', "; }
		if ($additionalNotes != "") { $updateSQL = $updateSQL."additional_notes='{$additionalNotes}', "; }
		if ($placementOnVehicle != "") { $updateSQL = $updateSQL."placement_on_vehicle='{$placementOnVehicle}', "; }
		if ($surfaceFinish != "") { $updateSQL = $updateSQL."surface_finish='{$surfaceFinish}', "; }

		$insertSQL = "INSERT INTO `mojo_brock_item_master` (`vendor_item_number`, `item_number`, `interchange`, `partslink`, `oem`, `upc`, `mpn`, `ebay_epids`, `additional_notes`, `placement_on_vehicle`, `surface_finish`, `ebay_id`) VALUES ('{$vendorSku}','', '{$hollander}', '{$partslink}', '{$oemnumber}', '{$upc}', '{$manufacturerPartNumber}', '{$ebay_epids}', '{$additionalNotes}','{$placementOnVehicle}', '{$surfaceFinish}', '{$ebay_id}')";
		echo $insertSQL.PHP_EOL;
		mysqli_query($con, $insertSQL); 
		$insertCount++;
		echo "success [".$vendorSku."]: inserted: ".$insertCount.PHP_EOL;
	}
} 

$result->close(); 
$input_result->close(); 
echo "--------------------------------------------------".PHP_EOL;
echo "records inserted: ".$insertCount.PHP_EOL;
echo "Whew, done.".PHP_EOL;
?>

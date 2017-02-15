<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$advance = TRUE;

$resultTo = $_GET['resultTo'];
$tryNoVendor = $_GET['tryNoVendor'];
$skus = trim($_GET['skus']);
$skus = str_replace("\r",",",$skus);
$skus = str_replace("\n","",$skus);
$next = trim($_GET['next']);
$text = trim($_GET['skus']);

if($next > 0) {
	$textAr = explode(",", $skus);
}
else {
	$textAr = explode("\n", $text);
	$next=0;
}
$textAr = array_filter($textAr, 'trim');
$sku=str_replace(" ","",$textAr[$next]);
$sku=str_replace("\r","",$sku);
if ($next > 0) {
	$processSku=str_replace(" ","",$textAr[$next-1]);
	$processSku=str_replace("\r","",$processSku);
}
	
$next= $next+1;
$customSearch = $_GET["customSearch"];

$todo = $_GET["todo"];

function display_xml_error($error) {
    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
         case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
    }
    $return .= trim($error->message) .
               "\n  Line: $error->line" .
               "\n  Column: $error->column";
    if ($error->file) {
        $return .= "\n  File: $error->file";
    }
    return "$return\n\n--------------------------------------------\n\n";
}

function processCopy($sku) {

$message = "";
$fromItemId = $_GET["fromItemId"];
$toItemId = $_GET["toItemId"];
$useTitle = $_GET["useTitle"];
$usePics = $_GET["usePics"];
$vendorSku = $_GET["vendorSku"];
$instock = $_GET["instock"];
$categories = $_GET["categories"];
$resultTo = $_GET["resultTo"];

$entry = $sku." updated at ".date('Y/m/d h:i:sa')."\n";
$logfile = "/var/www/html/dev/gooddata/compat-log.txt";
if (! file_exists($logfile)) die("$logfile does not exist!");
if (! is_readable($logfile)) die("$logfile is unreadable!");
file_put_contents($logfile, $entry, FILE_APPEND);

$csvfile = "/var/www/html/dev/gooddata/gooddata-upload-".date('Ymd').".csv";
$csvfp = fopen($csvfile, 'a');
if (! file_exists($csvfile)) die("$csvfile does not exist!");
if (! is_readable($csvfile)) die("$csvfile is unreadable!");

$picfile = "/var/www/html/dev/gooddata/gooddata-pics-".date('Ymd').".csv";
$picfp = fopen($picfile, 'a');
if (! file_exists($picfile)) die("$picfile does not exist!");
if (! is_readable($picfile)) die("$picfile is unreadable!");

// API request variables
$endpoint = 'http://open.api.ebay.com/shopping';  // URL to call
$appid = 'MojoPart-34e5-49b0-aab3-c8aa62626923';  // Replace with your own AppID

$apicall = "$endpoint?";
$apicall .= "callname=GetSingleItem";
$apicall .= "&responseencoding=XML";
$apicall .= "&appid=$appid";
$apicall .= "&siteid=0";
$apicall .= "&version=515";
$apicall .= "&ItemID=".$fromItemId;
$apicall .= "&includeSelector=Compatibility,ItemSpecifics";

// Load the call and capture the document returned by eBay API
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $apicall);
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);

  $returned = curl_exec($ch);
  curl_close($ch); 
  $resp = simplexml_load_string($returned);
  //echo $apicall;

// loop through errors
if (!$resp) {
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        echo display_xml_error($error);
	}
	libxml_clear_errors();
}


// Check to see if the request was successful, else print an error
$fromItem = $resp->Item;

	// variables for $csvString
	$ebay_title = "";
	$oemnumber = "";
	$hollander = "";
	$partslink = "";
	$placement_on_vehicle = "";
	$surface_finish = "";
	$compatibility = "";
	$is_in_stock = 0;
	$qty = 0;
	if ($instock == 1) {
		$is_in_stock = 1;
		$qty = 25;
	}
	
/*****************************************
* UPDATE COMPATIBILITIES
******************************************/
//echo "<br/><br/>Before...".$fromItem->ItemSpecifics->asXML()."<br/><br/>";
	foreach($fromItem->ItemCompatibilityList->Compatibility as $compat) {
		$compatibility .= "Year=".$compat->NameValueList[1]->Value;
		$compatibility .= "|Make=".$compat->NameValueList[2]->Value;
		$compatibility .= "|Model=".$compat->NameValueList[3]->Value;
		if (isset($compat->NameValueList[4]->Value)) {
			$compatibility .= '|Trim='.str_replace('"','',$compat->NameValueList[4]->Value);
		}
		if (isset($compat->NameValueList[5]->Value)) {
			$compatibility .= '|Engine='.str_replace('"','',$compat->NameValueList[5]->Value);
		}
		if (isset($compat->CompatibilityNotes)) {
			$compatibility .= '|Notes='.str_replace('"','',$compat->CompatibilityNotes);
		}
		$compatibility .= PHP_EOL;
	}


	for($i = $fromItem->ItemSpecifics->NameValueList->count()-1; $i>=0; $i-=1) {
//		echo "specific: ".$i."<br/>";
//		echo "specific: ".$fromItem->ItemSpecifics->NameValueList[$i]->Name." = ";
//		echo "specific: ".$fromItem->ItemSpecifics->NameValueList[$i]->Value[0].", ".$fromItem->ItemSpecifics->NameValueList[$i]->Value[1]."<br/>";
		$isName = $fromItem->ItemSpecifics->NameValueList[$i]->Name;
		if(($isName == "Returns Accepted") ||
		   ($isName == "Item must be returned within") ||
		   ($isName == "Return policy details") ||
		   ($isName == "Return shipping will be paid by") ||
		   ($isName == "Restocking Fee") ||
		   ($isName == "Refund will be given as"))
		{
			unset($fromItem->ItemSpecifics->NameValueList[$i]);
		}
		if ($resultTo == "file")
		{
			switch($isName)
			{
				case "Manufacturer Part Number":
					$oemnumber = $fromItem->ItemSpecifics->NameValueList[$i]->Value;
					break;
				case "Interchange Part Number":
					$hollander = $fromItem->ItemSpecifics->NameValueList[$i]->Value;
					$pattern = "/([A-Z]{2}[0-9]{7})/";
					if (preg_match($pattern, $hollander, $matches)) {
						$partslink = $matches[1];
					}
					else {
						$partslink = preg_last_error();	
					}
					break;
				case "Placement on Vehicle":
					// make sure only front/rear for bumpers, and left/right for fenders (when multiple placements present)
					foreach ($fromItem->ItemSpecifics->NameValueList[$i]->Value as $placement) {
						if ($placement_on_vehicle == "" || 
						(($placement_on_vehicle == "Front" || $placement_on_vehicle == "Rear") && ($placement == "Left" || $placement == "Right"))) {
							$placement_on_vehicle = $placement;
						}
					}
					break;
				case "Surface Finish":
					$surface_finish = $fromItem->ItemSpecifics->NameValueList[$i]->Value;
					break;
			}
		}
	}

//echo "<br/><br/>After...".$fromItem->ItemSpecifics->asXML();

require_once('./get-common/keys.php');
require_once('./get-common/eBaySession.php');
    $siteID = 0;
    $verb = 'ReviseItem';
    $detailLevel = 0;

	$picString1 = array($sku,"","Default","simple","base","","","","","","");
	$picString2 = array($sku,"","Default","simple","base","","","","","","");
	$picString3 = array($sku,"","Default","simple","base","","","","","","");
	$picString4 = array($sku,"","Default","simple","base","","","","","","");
	$picString5 = array($sku,"","Default","simple","base","","","","","","");
	$picString6 = array($sku,"","Default","simple","base","","","","","","");
	$picString7 = array($sku,"","Default","simple","base","","","","","","");
	$picString8 = array($sku,"","Default","simple","base","","","","","","");
	$picString9 = array($sku,"","Default","simple","base","","","","","","");
    
    ///Build the request Xml string
    $requestXmlBody = '<?xml version="1.0" encoding="utf-8" ?>';
    $requestXmlBody .= '<ReviseItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
    $requestXmlBody .= '<ErrorLanguage>en_US</ErrorLanguage>';
    $requestXmlBody .= '<Item>';
    $requestXmlBody .= '<ItemID>';
    $requestXmlBody .= $toItemId;
	$requestXmlBody .= '</ItemID>';
	if($useTitle==1) {
		$requestXmlBody .= '<Title>'.htmlentities($fromItem->Title).'</Title>';
		$ebay_title = $fromItem->Title;
	}
	if($useTitle==2) {
//		$ebay_title = 'SOFTMATCH '.$fromItem->Title;
		$ebay_title = $fromItem->Title;
	}
	if($usePics==1) {
		$requestXmlBody .= '<PictureDetails><PhotoDisplay>SuperSizePictureShow</PhotoDisplay><PictureSource>Vendor</PictureSource>';
		$picURL1 = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
		$requestXmlBody .= '<PictureURL>'.htmlentities($picURL1).'</PictureURL>';
		$requestXmlBody .= '</PictureDetails>';

		if (substr($sku,-2)=="LR") {
			$picString1[5] = $fromItem->PictureURL[0];
			$picString1[7] = $fromItem->PictureURL[0];
			$picString1[9] = $fromItem->PictureURL[0];
		}
		else {
			$picString1[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
			$picString1[7] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
			$picString1[9] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
		}
		$picString2[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_2?scl=2';
		$picString3[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_3?scl=2';
		$picString4[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_4?scl=2';
		$picString5[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_5?scl=2';
		$picString6[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_6?scl=2';
		$picString7[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_7?scl=2';
		$picString8[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_8?scl=2';
		$picString9[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_9?scl=2';
		}
	$fromItem->ItemCompatibilityList->addChild('ReplaceAll', 'true');
	$requestXmlBody .= $fromItem->ItemCompatibilityList->asXML();
	$requestXmlBody .= $fromItem->ItemSpecifics->asXML();
    $requestXmlBody .= '</Item>';
    $requestXmlBody .= '<RequesterCredentials><eBayAuthToken>'.$userToken.'</eBayAuthToken></RequesterCredentials>';
    $requestXmlBody .= '<WarningLevel>High</WarningLevel>';
    $requestXmlBody .= '</ReviseItemRequest>';
//	echo $requestXmlBody."<br/><br/>";
	if ($resultTo != "file")
	{
		//Create a new eBay session with all details pulled in from included keys.php
		$session = new eBaySession($userToken, $devID, $appID, $certID, $serverUrl, $compatabilityLevel, $siteID, $verb);
		//send the request and get response
		//echo $requestXmlBody;
		$responseXml = $session->sendHttpRequest($requestXmlBody);
		if(stristr($responseXml, 'HTTP 404') || $responseXml == '')
			die('<P>Error sending request');
		
		//Xml string is parsed and creates a DOM Document object
		$responseDoc = new DomDocument();
		$responseDoc->loadXML($responseXml);
		
		//get any error nodes
		$errors = $responseDoc->getElementsByTagName('Errors');
		$message = '<P><B>Sucecss!</b> Item successfully revised.<br/><br/>';
		$message .= 'View the source listing: <a href="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item='.$fromItemId.'" target="_">'.$fromItemId.'</a><br/>';
		$message .= 'View the our fixed listing: <a href="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item='.$toItemId.'" target="_">'.$toItemId.'</a><br/><br/>';
	}
	else {
		$chp = curl_init();
		curl_setopt($chp, CURLOPT_FAILONERROR, 1);
		curl_setopt($chp, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($chp, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($chp, CURLOPT_TIMEOUT, 15);

		curl_setopt($chp, CURLOPT_URL, $picString9[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString9); }
		curl_setopt($chp, CURLOPT_URL, $picString8[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString8); }
		curl_setopt($chp, CURLOPT_URL, $picString7[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString7); }
		curl_setopt($chp, CURLOPT_URL, $picString6[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString6); }
		curl_setopt($chp, CURLOPT_URL, $picString5[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString5); }
		curl_setopt($chp, CURLOPT_URL, $picString4[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString4); }
		curl_setopt($chp, CURLOPT_URL, $picString3[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString3); }
		curl_setopt($chp, CURLOPT_URL, $picString2[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString2); }
		curl_setopt($chp, CURLOPT_URL, $picString1[5]);
		$returnedp = curl_exec($chp);
		if ($returnedp) { fputcsv($picfp, $picString1); }

		curl_close($chp); 

		fclose($picfp);

		if (mysqli_connect_errno()) {
		  echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}
		$category_results = mysqli_query($con2,"
		SELECT 
		a.sku,
		cp.category_id
		FROM catalog_product_entity a
		left JOIN catalog_category_product cp ON cp.product_id=a.entity_id
		WHERE
		a.sku='$sku';
		");
		$categories = '';
		while($row = mysqli_fetch_array($category_results)) {
			if ($categories=='') {
				$categories=$row['category_id'];
			} else {
				$categories=$categories.','.$row['category_id'];
			}
		}
		$categories = $categories.',182';
		mysqli_close($con);

		$csvString = array($sku,$ebay_title,$oemnumber,$hollander,$partslink,$placement_on_vehicle,$surface_finish,$compatibility,$categories,$is_in_stock,$qty);
		fputcsv($csvfp, $csvString);
		fclose($csvfp);
//		$message = 'Success! Wrote '.$sku.' to CSV file.<br/>';
	}

	foreach($errors as $error) {
        //display each error
        //Get error code, ShortMesaage and LongMessage
//        $classification = $error->getElementsByTagName('ErrorClassification');
        $severity = $error->getElementsByTagName('SeverityCode');
        $classification = $error->getElementsByTagName('ErrorClassification');
        $code = $error->getElementsByTagName('ErrorCode');
        $shortMsg = $error->getElementsByTagName('ShortMessage');
        $longMsg = $error->getElementsByTagName('LongMessage');
        //Display code and shortmessage
        echo '<P>', $severity->item(0)->nodeValue, ' : ',$classification->item(0)->nodeValue, ' : ',$code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
        //if there is a long message (ie ErrorLevel=1), display it
        if(count($longMsg) > 0)
            echo '<BR>', str_replace(">", "&gt;", str_replace("<", "&lt;", $longMsg->item(0)->nodeValue));

    }

/******************************************/
	return $message;
}

$message = "";

if ($todo == "process") {
	$message = processCopy($processSku);
}
echo $message;	

if($next > count($textAr)) {
	echo 'Done!  <a href="./copyCompatsStart">Start a new batch >></a><br/><br/>';
}
else {

$result = mysqli_query($con, "SELECT 
a.sku,
b.attribute_id,
b.value,
c.stock_status,
v.value as vendor,
ei.item_id,
elp.online_buyitnow_price as price
FROM catalog_product_entity a
left outer join catalog_product_entity_varchar b on a.entity_id = b.entity_id 
left join cataloginventory_stock_status c on a.entity_id = c.product_id
left outer join catalog_product_entity_int v on a.entity_id = v.entity_id and v.attribute_id=163
left JOIN m2epro_ebay_item ei ON ei.product_id=a.entity_id
left JOIN m2epro_ebay_listing_product elp ON elp.ebay_item_id=ei.id
left JOIN m2epro_listing_product lp ON lp.id=elp.listing_product_id
WHERE
a.sku='$sku'
and lp.status=2;") or die(mysqli_error($con));

if (mysqli_num_rows($result)==0) {
//echo 'NO RESULT FROM 1st QUERY... TRYING AGAIN<br/><br/>';
	$result = mysqli_query($con,"
	SELECT 
	a.sku,
	b.attribute_id,
	b.value,
	c.stock_status,
	v.value as vendor,
	lp.status
	FROM catalog_product_entity a
	left outer join catalog_product_entity_varchar b on a.entity_id = b.entity_id 
	left outer join cataloginventory_stock_status c on a.entity_id = c.product_id
	left outer join catalog_product_entity_int v on a.entity_id = v.entity_id and v.attribute_id=163
	left JOIN m2epro_listing_product lp ON lp.product_id=a.entity_id
	WHERE
	a.sku='$sku'
	and lp.status=0;
	") or die(mysqli_error($con));
}
//echo 'NUMBER OF RESULTS: '.mysqli_num_rows($result).'<br/><br/>';

$toItemId='';

while($row = mysqli_fetch_array($result)) {
//  echo $row['sku'] . " " . $row['value'];
//  echo "<br>";
  switch($row['attribute_id']) {
	case 71: 
		$title = $row['value'];
		$toItemId = $row['item_id'];
		$price = $row['price'];
		$instock = $row['stock_status'];
		break;
	case 85: 
		$imgUrl = $row['value'];
		break;
	case 100: 
		$altTitle = $row['value'];
		break;
	case 140: 
		$oem = $row['value'];
		break;
	case 142: 
		$partslink = $row['value'];
		break;
	case 164: 
		$vendorSku = strtok(str_replace(" ","",$row['value']),",");
		$vendorSku2 = strtok(",");
		if($row['vendor']==36) {
			$vendor = "PFG";
		}
		else {
			$vendor = "Brock";
		}
		break;
	}
}

/*******************************************************************************************************************/
// API request variables
if ($resultTo == "ebay") {
	$endpointTo = 'http://open.api.ebay.com/shopping';  // URL to call
	$appidTo = 'MojoPart-34e5-49b0-aab3-c8aa62626923';  // Replace with your own AppID

	$apicallTo = "$endpointTo?";
	$apicallTo .= "callname=GetSingleItem";
	$apicallTo .= "&responseencoding=XML";
	$apicallTo .= "&appid=$appidTo";
	$apicallTo .= "&siteid=0";
	$apicallTo .= "&version=515";
	$apicallTo .= "&ItemID=".$toItemId;
	$apicallTo .= "&includeSelector=Compatibility,ItemSpecifics";

	// Load the call and capture the document returned by eBay API
	  $chTo = curl_init();
	  curl_setopt($chTo, CURLOPT_URL, $apicallTo);
	  curl_setopt($chTo, CURLOPT_FAILONERROR, 1);
	  curl_setopt($chTo, CURLOPT_FOLLOWLOCATION, 1);
	  curl_setopt($chTo, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($chTo, CURLOPT_TIMEOUT, 15);

	  $returnedTo = curl_exec($chTo);
	  curl_close($chTo); 
	  $respTo = simplexml_load_string($returnedTo);
	    //echo $apicallTo;
}
/*******************************************************************************************************************/


// find the corresponding listing from the source
$endpoint = 'http://svcs.ebay.com/services/search/FindingService/v1';  // URL to call
$appid = 'MojoPart-34e5-49b0-aab3-c8aa62626923'; 

$apicall = "$endpoint?";
$apicall .= "OPERATION-NAME=findItemsIneBayStores&";
$apicall .= "SERVICE-VERSION=1.0.0&";
$apicall .= "SECURITY-APPNAME=$appid&";
$apicall .= "RESPONSE-DATA-FORMAT=XML&";
$apicall .= "REST-PAYLOAD&";
if($customSearch=="") {
	if (!$tryNoVendor) {
		if ($vendor == "PFG") {
			$apicall .= "storeName=Car-Parts-Wholesale&";
		}
		else {
			$apicall .= "storeName=AutoandArt&";
		}
	}
}
$apicall .= "outputSelector=StoreInfo&";
// uses syntax (keyword1,keyword2) to apply OR logic to multiple keywords
if($customSearch=="") {
	$apicall .= 'keywords=('.str_replace(" ","",$partslink).','.str_replace(",","",str_replace(" ","",$oem)).')';
	if($vendor != "PFG") {
		$apicall .= ','.str_replace(" ","",$vendorSku).')';
	}
}
else {
	$apicall .= 'keywords='.str_replace(" ","+",$customSearch);
}
	//echo "<a href='$apicall' target='_'>".$apicall."</a><br/>";

// Load the call and capture the document returned by eBay API
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $apicall);
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);

  $returned = curl_exec($ch);
    $resp = simplexml_load_string($returned);

	// Our sku
	if($toItemId == "" && $resultTo == "ebay") {
		echo "No active listing found for ".$sku.". <br/>";
		echo '<a href="./copyCompats2.php?tryNoVendor=0&skus='.$skus.'&next='.$next.'&resultTo='.$resultTo.'">Skip to the next one>><a><br/>'; 
	}
	else {
		echo "<table border=1>";
		echo "<tr>";
		if ($resultTo == "ebay") {
			echo '<td><img width="50" src="', $respTo->Item->GalleryURL, '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[0], '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[1], '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[2], '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[3], '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[4], '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[5], '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[6], '"></td>';
			echo '<td><img width="50" src="', $respTo->Item->PictureURL[7], '"></td>';
		}
		else {
			echo '<td><img width="50" src="http://www.mojoparts.com/media/catalog/product'.$imgUrl.'"></td>';		
		}
		echo "</tr>";
		echo "<tr><td colspan='9'>";
		echo "$",$price," | ", $sku, "<br/>";
		echo '<a href="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item=', $toItemId, '" target="_">',$toItemId,'</a>: ',$title,'<br/>';
//		echo "<br/>".$apicallTo;

		echo "</td></tr>";
		echo "<tr><td colspan='9'>";

		echo $vendor, " | ";
		echo $vendorSku, " | ";
		echo $vendorSku2, "<br/>";
		echo "Partslink: ", $partslink, " | ";
		echo "OEM: ", str_replace(',','', $oem);
		echo "</td></tr>";

		if($resultTo == "ebay") {
			echo "<tr><td colspan='9'>";
			$endpoint2 = 'http://open.api.ebay.com/shopping';  // URL to call
			$appid2 = 'MojoPart-34e5-49b0-aab3-c8aa62626923';  // Replace with your own AppID

			$apicall2 = "$endpoint2?";
			$apicall2 .= "callname=GetSingleItem";
			$apicall2 .= "&responseencoding=XML";
			$apicall2 .= "&appid=$appid";
			$apicall2 .= "&siteid=0";
			$apicall2 .= "&version=515";
			$apicall2 .= "&ItemID=".$toItemId;
			$apicall2 .= "&includeSelector=Compatibility,ItemSpecifics";

			// Load the call and capture the document returned by eBay API
			  $ch2 = curl_init();
			  curl_setopt($ch2, CURLOPT_URL, $apicall2);
			  curl_setopt($ch2, CURLOPT_FAILONERROR, 1);
			  curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 1);
			  curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
			  curl_setopt($ch2, CURLOPT_TIMEOUT, 15);

			  $returned2 = curl_exec($ch2);
			  curl_close($ch2); 
			  $resp2 = simplexml_load_string($returned2);
			  $compats = $resp2->Item->ItemCompatibilityList;
			//echo "<a href='$apicall2' target='_'>".$apicall2."</a><br/>";
			  echo '<div style="height:60px; border:1px solid; overflow:auto;">';
			  foreach ($compats->Compatibility as $compat) {
				echo $compat->NameValueList[1]->Value," ",$compat->NameValueList[2]->Value," ",$compat->NameValueList[3]->Value," ",$compat->NameValueList[4]->Value," ",$compat->NameValueList[5]->Value,"</br>";
			}
			echo "</div>";
			echo "</td></tr>";
		}
		echo "</table>";
		echo "<br/><hr/></br>";
		
	// found skus
//	echo "<a href='$apicall' target='_'>".$apicall."</a><br/>";
	echo '<a href="./copyCompats2.php?skus='.$skus.'&next='.$next.'&resultTo='.$resultTo.'">Skip to the next one>><a><br/>'; 
//	echo "Did not find any matching eBay listings for ".$vendor.".  Try again with a custom search phrase:<br/>";
	echo '<form action="copyCompats2.php" type="get">';
	$sampleCompat = $compats->Compatibility[0];
	$mmy = $sampleCompat->NameValueList[1]->Value." ".$sampleCompat->NameValueList[2]->Value." ".$sampleCompat->NameValueList[3]->Value;
	$cats = str_replace("&","",explode(":",$resp2->Item->PrimaryCategoryName));
	if (isset($compats)) {
	$stop=0;
		if(strpos($title, "-")!==FALSE && strpos($title, "-")<15) {
			$stop = strpos($title, "-");
			$mmy = substr($title,0,$stop);
		}
		else {
			$stop = strpos($altTitle, "-");
			if($stop < 20) {
				$mmy = substr($altTitle,0,$stop);
			}
		}
	}
	$cs = trim($mmy." ".$cats[count($cats)-1]);
	echo "<input type='text' name='customSearch' size='80' value='",$cs,"'/><br/>";
	echo '<input type="hidden" name="sku" value='.$sku.'>';
	echo '<input type="hidden" name="skus" value='.$skus.'>';
	$nextm1=$next-1;
	echo '<input type="hidden" name="next" value='.$nextm1.'>';
	echo '<input type="hidden" name="resultTo" value='.$resultTo.'>';
	echo '<input type="submit" value="Submit">';
	echo '</form>';

	echo "<table border=1>";
	$i=0;
	$matches = 0;
	
	/** display the results */
	foreach($resp->searchResult->item as $item) {
		$i=$i+1;
		if($i > 20) { 
			break; 
		}
		$endpoint2 = 'http://open.api.ebay.com/shopping';  // URL to call
		$appid2 = 'MojoPart-34e5-49b0-aab3-c8aa62626923';  // Replace with your own AppID
		$apicall2 = "$endpoint2?";
		$apicall2 .= "callname=GetSingleItem";
		$apicall2 .= "&responseencoding=XML";
		$apicall2 .= "&appid=$appid";
		$apicall2 .= "&siteid=0";
		$apicall2 .= "&version=515";
		$apicall2 .= "&ItemID=".$item->itemId;
		if($vendor == "PFG") {
			$apicall2 .= "&includeSelector=Description,ItemSpecifics";
		}
		else {
			$apicall2 .= "&includeSelector=ItemSpecifics";
		}
		//echo $apicall2.'<br/><br/>';
		// Load the call and capture the document returned by eBay API
		$ch2 = curl_init();
		curl_setopt($ch2, CURLOPT_URL, $apicall2);
		curl_setopt($ch2, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
		$returned2 = curl_exec($ch2);
		curl_close($ch2); 
		$resp2 = simplexml_load_string($returned2);
		$specifics = $resp2->Item->ItemSpecifics;

		$found = FALSE;
		$foundSoft = FALSE;
		$softMatch = "";
		$matchCmd = "";
		$matchCmdSoftMatch = "";
		if ($resultTo == "file") {
			$matchCmd = '<b>EXACT MATCH</b><br/><a href="./copyCompats2.php?todo=process&tryNoVendor=0&skus='.$skus.'&resultTo='.$resultTo.'&next='.$next.'&instock='.$instock.'&sku='.$sku.'&fromItemId='.$item->itemId.'&toItemId='.$toItemId.'&categories='.$categories.'&useTitle=1&usePics=1&vendorSku='.$vendorSku.'">COPY</a>';
			$matchCmdSoftMatch = '<a href="./copyCompats2.php?todo=process&tryNoVendor=0&skus='.$skus.'&resultTo='.$resultTo.'&next='.$next.'&instock='.$instock.'&sku='.$sku.'&fromItemId='.$item->itemId.'&toItemId='.$toItemId.'&categories='.$categories.'&useTitle=2&usePics=1&vendorSku='.$vendorSku.'">copy</a>';
		}
		else
		{
			$matchCmd = '<a href="./copyCompats2.php?todo=process&tryNoVendor=0&skus='.$skus.'&next='.$next.'&resultTo='.$resultTo.'&instock='.$instock.'&sku='.$sku.'&fromItemId='.$item->itemId.'&toItemId='.$toItemId.'&categories='.$categories.'&useTitle=0&usePics=0">compats</a><br/><a href="./copyCompats2.php?todo=process&skus='.$skus.'&next='.$next.'&instock='.$instock.'&resultTo='.$resultTo.'&sku='.$sku.'&fromItemId='.$item->itemId.'&categories='.$categories.'&toItemId='.$toItemId.'&useTitle=1&usePics=0">compats+title</a><br/>$matchCmdSoftMatch = <a href="./copyCompats2.php?todo=process&tryNoVendor=0&skus='.$skus.'&resultTo='.$resultTo.'&next='.$next.'&instock='.$instock.'&sku='.$sku.'&fromItemId='.$item->itemId.'&categories='.$categories.'&toItemId='.$toItemId.'&useTitle=1&usePics=1&vendorSku='.$vendorSku.'">COPY ALL</a>';
		}
		if($tryNoVendor) {
			$match += 1;
			$foundSoft = TRUE;
		}
		else {
			if($vendor == "PFG") {
				$desc = $resp2->Item->Description;
				$set = "";
				$double = "";
				if ($vendorSku2 != "") {
					$set = "SET-";
					$double = "-2";
				}
				$hiddenSku = '<span style="margin-left:10px; color:#fff;">'.$set.$vendorSku.'</span>';
				$hiddenSkuDouble = '<span style="margin-left:10px; color:#fff;">'.$set.$vendorSku.$double.'</span>';
				$hiddenSkuSoft = $vendorSku;
				$hiddenSku2 = '<span style="margin-left:10px; color:#fff;">'.$set.$vendorSku2.'</span>';
				$hiddenSku2Double = '<span style="margin-left:10px; color:#fff;">'.$set.$vendorSku2.$double.'</span>';
				$hiddenSku2Soft = $vendorSku2;
				$pos = strpos($desc, $hiddenSku);
				$posDouble = strpos($desc, $hiddenSkuDouble);
				$posSoft = strpos($desc, $hiddenSkuSoft);
				if ($vendorSku2!="" && $pos == 0 && $posDouble == 0 && $posSoft == 0) {
					$pos = strpos($desc, $hiddenSku2);
					$posDouble = strpos($desc, $hiddenSku2Double);
					$posSoft = strpos($desc, $hiddenSku2Soft);
				}
				if ($pos > 0 || $posDouble > 0) {
					$found = TRUE;
					$matches += 1;
				}
				else
				{
					if ($posSoft > 0) {
						$foundSoft = TRUE;
						$softMatch = substr($desc, $posSoft, 20);
						$matches += 1;
					}
				}
			}
			else {
				foreach ($resp2->Item->ItemSpecifics->NameValueList as $specific) {
					if ($specific->Value == $vendorSku) {
							$found = TRUE;
							$matches += 1;
							break;
					}
					else {
						if ((strpos($specific->Value, $vendorSku)!==false)) {
							if(substr($vendorSku,-1)=="L") {
								if(strpos($specific->Value, $vendorSku."R")===false) {
									$found = TRUE;
									$matches += 1;
									break;
								}
							}
							else {
								$found = TRUE;
								$matches += 1;
								break;
							}
						}
					}
				}
			}
		}
//		if ($found || $foundSoft) {
			echo '<tr><td width=150>';
			if (!$found) {
				echo $matchCmdSoftMatch;
				if ($softMatch != "") {
					echo ': '.$softMatch;
				}
			}
			else {
				echo $matchCmd;
			}
			echo '</td>';
			echo '<td><img width="50" src="', $item->galleryURL, '"></td>';
			echo '<td>$', $item->sellingStatus->currentPrice, '</td>';
			echo '<td><a href="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item=', $item->itemId, '" target="_">',$item->itemId,'</a>: ',$item->title,'<br/>', $item->storeInfo->storeName, '</td>';
			echo '<td>';
			foreach ($resp2->Item->ItemSpecifics->NameValueList as $specific) {
				if (($specific->Name == "Manufacturer Part Number") ||
				($specific->Name == "Partslink") ||
				($specific->Name == "Hollander") ||
				($specific->Name == "Interchange Part Number") ||
				($specific->Name == "Other Part Number"))
				echo $specific->Name." = ".$specific->Value."<br/>";
			}
			
			if ($matches == 1) {
				echo 'matches = '.$matches;
			}
			
			echo '</td>';
			//echo '<td>', $apicall2, '</td>';
			echo '</tr>';
//		}
	}
	echo "</table>";
	echo '<form action="copyCompats2.php" type="get">';
	echo '<input type="hidden" name="sku" value='.$sku.'>';
	echo '<input type="hidden" name="skus" value='.$skus.'>';
	$nextm1=$next-1;
	echo '<input type="hidden" name="next" value='.$nextm1.'>';
	echo '<input type="hidden" name="resultTo" value='.$resultTo.'>';
	echo '<input type="hidden" name="tryNoVendor" value=1>';
	echo '<input type="submit" value="Try Again with No Vendor">';
	echo '</form>';

	foreach($errors as $error) {
        $severity = $error->getElementsByTagName('SeverityCode');
        $classification = $error->getElementsByTagName('ErrorClassification');
        $code = $error->getElementsByTagName('ErrorCode');
        $shortMsg = $error->getElementsByTagName('ShortMessage');
        $longMsg = $error->getElementsByTagName('LongMessage');
        echo '<P>', $severity->item(0)->nodeValue, ' : ',$classification->item(0)->nodeValue, ' : ',$code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
        if(count($longMsg) > 0)
            echo '<BR>', str_replace(">", "&gt;", str_replace("<", "&lt;", $longMsg->item(0)->nodeValue));

    }
}
mysqli_close($con);
mysqli_close($con2);
}
?>
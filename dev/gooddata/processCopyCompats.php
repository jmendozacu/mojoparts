<?php
error_reporting(E_ALL);  // Turn on all errors, warnings and notices for easier debugging
libxml_use_internal_errors(true);

$fromItemId = $_GET["fromItemId"];
$toItemId = $_GET["toItemId"];
$sku = $_GET["sku"];
$skus = $_GET["skus"];
$next = $_GET["next"];
$useTitle = $_GET["useTitle"];
$usePics = $_GET["usePics"];
$vendorSku = $_GET["vendorSku"];
$resultTo = $_GET["resultTo"];
$instock = $_GET["instock"];

$entry = $sku." updated at ".date('Y/m/d h:i:sa')."\n";
$logfile = "/var/www/html/dev/gooddata/compat-log.txt";
if (! file_exists($logfile)) die("$logfile does not exist!");
if (! is_readable($logfile)) die("$logfile is unreadable!");
file_put_contents($logfile, $entry, FILE_APPEND);

$csvfile = "/var/www/html/dev/gooddata/gooddata-upload.csv";
if (! file_exists($csvfile)) die("$csvfile does not exist!");
if (! is_readable($csvfile)) die("$csvfile is unreadable!");
$csvfp = fopen($csvfile, 'a');

$picfile = "/var/www/html/dev/gooddata/gooddata-pics.csv";
if (! file_exists($picfile)) die("$picfile does not exist!");
if (! is_readable($picfile)) die("$picfile is unreadable!");
$picfp = fopen($picfile, 'a');

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
//  echo $apicall;

// loop through errors
if (!$resp) {
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        echo display_xml_error($error);
	}
	libxml_clear_errors();
}

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

// Check to see if the request was successful, else print an error
$fromItem = $resp->Item;

	// variables for $csvString
	$ebay_title = "";
	$oemnumber = "";
	$hollander = "";
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

	for($i=$fromItem->ItemSpecifics->NameValueList->count()-1; $i>=0; $i-=1) {
//		echo "specific: ".$i."<br/>";
//		echo "specific: ".$fromItem->ItemSpecifics->NameValueList[$i]->Name."<br/>";
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
					break;
				case "Placement on Vehicle":
					// make sure only front/rear for bumpers, and left/right for fenders (when multiple placements present)
					$placement_on_vehicle = $fromItem->ItemSpecifics->NameValueList[$i]->Value;
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
	if($usePics==1) {
		$requestXmlBody .= '<PictureDetails><PhotoDisplay>SuperSizePictureShow</PhotoDisplay><PictureSource>Vendor</PictureSource>';
		$picURL1 = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
		$requestXmlBody .= '<PictureURL>'.htmlentities($picURL1).'</PictureURL>';
		$requestXmlBody .= '</PictureDetails>';

		$picString1[5] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
		$picString1[7] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
		$picString1[9] = 'http://img.ptimg.com/is/image/Autos/'.strtolower($vendorSku).'_1?scl=2';
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
//	$requestXmlBody .= $fromItem->ItemSpecifics->asXML();
    $requestXmlBody .= '</Item>';
    $requestXmlBody .= '<RequesterCredentials><eBayAuthToken>'.$userToken.'</eBayAuthToken></RequesterCredentials>';
    $requestXmlBody .= '<WarningLevel>High</WarningLevel>';
    $requestXmlBody .= '</ReviseItemRequest>';
//echo $requestXmlBody."<br/><br/>";
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
		echo '<P><B>Sucecss!</b> Item successfully revised.<br/><br/>';
		$ebayLink='http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item=';
		echo 'View the source listing: <a href="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item='.$fromItemId.'" target="_">'.$fromItemId.'</a><br/>';
		echo 'View the our fixed listing: <a href="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item='.$toItemId.'" target="_">'.$toItemId.'</a><br/><br/>';
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
		$csvString = array($sku,$ebay_title,$oemnumber,$hollander,$placement_on_vehicle,$surface_finish,$compatibility,$is_in_stock,$qty);
		fputcsv($csvfp, $csvString);
		fclose($csvfp);
		echo '<P><B>Success!</b> Item written to file: '.$picString1[0].'<br/><br/>';
	}
    
	if($next==-1) {
		echo '<a href="./copyCompats.php">Do another one >><a>'; 
	}
	else {
		echo '<a href="./copyCompats2.php?skus='.$skus.'&resultTo='.$resultTo.'&next='.$next.'">Do the next one>><a>'; 
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

?>

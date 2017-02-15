<?php 

require_once('./get-common/keys.php') ?>
<?php require_once('./get-common/eBaySession.php') ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
<META http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<TITLE>testRevise</TITLE>
</HEAD>
<BODY>

<?php    
    $siteID = 0;
    $verb = 'ReviseItem';
    $detailLevel = 0;
    
    ///Build the request Xml string
    $requestXmlBody = '<?xml version="1.0" encoding="utf-8" ?>';
    $requestXmlBody .= '<ReviseItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
    $requestXmlBody .= '<ErrorLanguage>en_US</ErrorLanguage>';
    $requestXmlBody .= '<Item>';
    $requestXmlBody .= '<ItemID>291144778240</ItemID>';
    $requestXmlBody .= '<Title>';
    $requestXmlBody .= 'Muffler, Aluminized Steel, Natural Finish (x)';
    $requestXmlBody .= '</Title>';
	$requestXmlBody .= '
	<ItemCompatibilityList>
         <Type>NameValue</Type>
         <Compatibility>
            <NameValueList>
               <Name>Year</Name>
               <Value>2000</Value>
            </NameValueList>
            <NameValueList>
               <Name>Make</Name>
               <Value>Acura</Value>
            </NameValueList>
            <NameValueList>
               <Name>Model</Name>
               <Value>EL</Value>
            </NameValueList>
            <CompatibilityNotes>Fits for all trims and engines.</CompatibilityNotes>
			<Delete>true</Delete>
         </Compatibility>
	 </ItemCompatibilityList>';
    $requestXmlBody .= '</Item>';
    $requestXmlBody .= '<RequesterCredentials><eBayAuthToken>'.$userToken.'</eBayAuthToken></RequesterCredentials>';
    $requestXmlBody .= '<WarningLevel>High</WarningLevel>';
    $requestXmlBody .= '</ReviseItemRequest>';

	
    //Create a new eBay session with all details pulled in from included keys.php
    $session = new eBaySession($userToken, $devID, $appID, $certID, $serverUrl, $compatabilityLevel, $siteID, $verb);
    //send the request and get response
    $responseXml = $session->sendHttpRequest($requestXmlBody);
    if(stristr($responseXml, 'HTTP 404') || $responseXml == '')
        die('<P>Error sending request');
    
    //Xml string is parsed and creates a DOM Document object
    $responseDoc = new DomDocument();
    $responseDoc->loadXML($responseXml);
    
    
    //get any error nodes
    $errors = $responseDoc->getElementsByTagName('Errors');
    
    //if there are error nodes
    if($errors->length > 0)
    {
        echo '<P><B>eBay returned the following error(s):</B>';
        //display each error
        //Get error code, ShortMesaage and LongMessage
        $code = $errors->item(0)->getElementsByTagName('ErrorCode');
        $shortMsg = $errors->item(0)->getElementsByTagName('ShortMessage');
        $longMsg = $errors->item(0)->getElementsByTagName('LongMessage');
        //Display code and shortmessage
        echo '<P>', $code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
        //if there is a long message (ie ErrorLevel=1), display it
        if(count($longMsg) > 0)
            echo '<BR>', str_replace(">", "&gt;", str_replace("<", "&lt;", $longMsg->item(0)->nodeValue));

    }
    else //no errors
    {
        //get the node containing the time and display its contents
        $eBayTime = $responseDoc->getElementsByTagName('Timestamp');
        echo '<P><B>Item successfully revised at: ', $eBayTime->item(0)->nodeValue, ' GMT</B>';
    }
?>

</BODY>
</HTML>

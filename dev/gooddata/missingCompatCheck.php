<?php

if (!empty($_FILES["csv_file"]["tmp_name"])) {
    $file_path = $_FILES['csv_file']['tmp_name'];
//	echo $file_path;

	if($file = fopen($file_path, r)) {
//		echo "File opened.<br />";

		$firstline = fgets ($file, 4096 );
		//Gets the number of fields, in CSV-files the names of the fields are mostly given in the first line
		$num = strlen($firstline) - strlen(str_replace(",", "", $firstline));

		//save the different fields of the firstline in an array called fields
		$fields = array();
		$fields = explode( ",", $firstline, ($num+1) );
		$line = array();
		$i = 0;

		//CSV: one line is one record and the cells/fields are seperated by ";"
		//so $dsatz is an two dimensional array saving the records like this: $dsatz[number of record][number of cell]
		while ( $line[$i] = fgets ($file, 4096) ) {

			$dsatz[$i] = array();
			$dsatz[$i] = explode( ",", $line[$i], ($num+1) );

			$i++;
		}

		error_reporting(E_ALL);  // Turn on all errors, warnings and notices for easier debugging
		libxml_use_internal_errors(true);
		$ch = curl_init();
		$endpoint = 'http://open.api.ebay.com/shopping';  // URL to call
		$appid = 'MojoPart-34e5-49b0-aab3-c8aa62626923';  // Replace with your own AppID
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		echo "<table border=1>";
		echo "<tr>";
		echo "<td>" . $fields[0] . "</td><td>" . $fields[1] . "</td>";

	  foreach ($dsatz as $key => $number) {
			//new table row for every record

			// API request variables
			$apicall = "$endpoint?";
			$apicall .= "callname=GetSingleItem";
			$apicall .= "&responseencoding=XML";
			$apicall .= "&appid=$appid";
			$apicall .= "&siteid=0";
			$apicall .= "&version=515";
			$apicall .= "&includeSelector=Compatibility";
			$apicall .= "&ItemID=".$number[0];

			// Load the call and capture the document returned by eBay API
			  curl_setopt($ch, CURLOPT_URL, $apicall);

			  $returned = curl_exec($ch);
			  $resp = simplexml_load_string($returned);

			if(!isset($resp->Item->ItemCompatibilityList->Compatibility->NameValueList[1])) {
				echo "<tr><td>" . $number[0] . "</td><td>" . $number[1] . "</td></tr>";
			}
		}
		curl_close($ch); 
		echo "</table>";
	}
} 
?>

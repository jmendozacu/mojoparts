<?php
error_reporting(E_ALL);
$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
// TODO: use an included config file for db connection.  The hard-coded connection was removed for better security.
// mysqli_real_connect($con, '$server','$user','$passord','$database');
if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$result_compat = mysqli_query($con,"
	SELECT a.sku, c.value
	FROM catalog_product_entity a
	left join catalog_product_entity_text c on a.entity_id = c.entity_id and c.attribute_id=200
	where c.VALUE IS NOT null
	AND c.VALUE<>''
	;
	");
//	AND a.sku IN ('1221-0001L')


echo "CS-EPID,Notes,Year,Make,Model,Trim,Engine".PHP_EOL;
	
while($compat_row = mysqli_fetch_array($result_compat)) {
	$sku = $compat_row['sku'];
	$compatAr = array();
	$compatAr = explode('"ITEM"|', trim($compat_row['value']));
	foreach($compatAr as $compat) {
		$epidEl = array();
		$epidEl = explode("|", $compat);
		if ($epidEl[0] != "") {
			$epid = trim($epidEl[0],'",');
			$epid_query = "SELECT e.year, e.make, e.model, e.trim, e.engine from m2epro_ebay_dictionary_motor_epid e where e.epid='$epid';";
//			echo $epid_query.PHP_EOL;
			$result_epid = mysqli_query($con, $epid_query);
			if (mysqli_num_rows($result_epid) == 1) {
				echo "C".preg_replace("/[^a-zA-Z0-9]/","",$sku).",";
				if (isset($epidEl[1])) {
					echo '"'.preg_replace("/[\"\,]/","",trim($epidEl[1],',"')).'",';
				}
				else {
					echo '"",';
				}
				$row = mysqli_fetch_array($result_epid);
				echo $row['year'].",".$row['make'].",".$row['model'].",".$row['trim'].",".$row['engine'].PHP_EOL;
			}
			else {
//				echo "ERROR fetching compats for ".$sku.", ePID ".$epid.PHP_EOL;
			}
		}
	}
}
	
mysqli_close($con);

?>
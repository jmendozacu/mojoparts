<?php
error_reporting(E_ALL);
$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
mysqli_real_connect($con, 'mojomysql2.c6orzbehh7d1.us-east-1.rds.amazonaws.com','mojo','3^-4Grj,;pF7[3kN','mojomagento');
if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$test = "1221-0007L";
$result_compat = mysqli_query($con,"
	SELECT a.sku, b.value
	FROM catalog_product_entity a
	inner join catalog_product_entity_text b on a.entity_id = b.entity_id and b.attribute_id=135
	left join catalog_product_entity_text c on a.entity_id = c.entity_id and c.attribute_id=200
	where b.value is not null
	and (c.value is null or c.value = '')
	;
	");
//	and a.sku='$test'

echo "sku,ebay_epids".PHP_EOL;
	
while($compat_row = mysqli_fetch_array($result_compat)) {
	$sku = $compat_row['sku'];
	$compatAr = array();
	$compatAr = explode(PHP_EOL, trim($compat_row['value']));

	$epidAr = array();

	foreach($compatAr as $compat) {
		$componentAr = array();
		$componentAr = explode('|', trim($compat));
		$notes = null;
		$epid_query = null;

		if (count($componentAr) >= 3) {
			$year = str_replace("Year=","",$componentAr[0]);
			$make = str_replace("Make=","",$componentAr[1]);
			$model = str_replace("Model=","",$componentAr[2]);
			$epid_query = "select e.epid from m2epro_ebay_dictionary_motor_epid e where e.year='$year' and e.make='$make' and e.model='$model'";

			if (count($componentAr) >= 4) {
				for ($i=3; $i<=count($componentAr)-1; $i++) {
					if (strpos($componentAr[$i], "Trim=") !== false) $epid_query .= " and e.trim='".str_replace("Trim=","",$componentAr[$i])."'";
					if (strpos($componentAr[$i], "Engine=") !== false) $epid_query .= " and e.engine='".str_replace("Engine=","",$componentAr[$i])."'";
					if (strpos($componentAr[$i], "Notes=") !== false) $notes = str_replace("Notes=","",$componentAr[$i]);
				}
			}

			$epid_query .= ";";
			$result_epid = mysqli_query($con,$epid_query);

			while ($epid_row = mysqli_fetch_array($result_epid)) {
				$m2e_epid = '""ITEM""|""'.$epid_row['epid'].'""';
				if ($notes != null) $m2e_epid .= '|""'.$notes.'""';

				array_push($epidAr, $m2e_epid);
			}
		}
	}
	if (empty($epidAr)) echo $sku.",".PHP_EOL;
	else echo $sku.',"'.implode(",",$epidAr).'"'.PHP_EOL;
}
	
mysqli_close($con);

?>
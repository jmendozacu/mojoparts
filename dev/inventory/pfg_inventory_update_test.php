<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

$result = mysqli_query($con, "select mvi.item_number as 'sku', mvi.qty as 'il_whs_qty'
from mojo_vendor_inventory_copy mvi
where mvi.item_number is not null
and mvi.item_number <> ''
and mvi.vendor = 'PFG'
and mvi.warehouse='IL'
INTO OUTFILE '/var/www/html/var/import/il_whs_import_test.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '\"'
LINES TERMINATED BY '\n'") or die(mysqli_error($con));

$con->close();
?>

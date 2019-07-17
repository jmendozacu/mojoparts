<?php
error_reporting(E_ALL);
require_once dirname(__FILE__).'/excel_reader2.php';

$magento_con = mysqli_init();
// TODO: use an included config file for db connection.  The hard-coded connection was removed for better security.
// mysqli_real_connect($con, '$server','$user','$passord','$database');

//******************* PASTE TEMP CODE BELOW... *********************************************************/

echo "test";
	
//******************* PASTE TEMP CODE ABOVE... *********************************************************/

$magento_con->close();

echo "DONE.".PHP_EOL;
?>
	
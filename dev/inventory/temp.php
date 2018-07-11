<?php
error_reporting(E_ALL);
require_once dirname(__FILE__).'/excel_reader2.php';

$magento_con = mysqli_init();
mysqli_real_connect($magento_con, 'mojomysql2.c6orzbehh7d1.us-east-1.rds.amazonaws.com','mojo','3^-4Grj,;pF7[3kN','mojomagento');

//******************* PASTE TEMP CODE BELOW... *********************************************************/

echo "test";
	
//******************* PASTE TEMP CODE ABOVE... *********************************************************/

$magento_con->close();

echo "DONE.".PHP_EOL;
?>
	
<?php
//$con = mysqli_connect('199.168.174.22','mojo01sa','YikrbYrRE6XJNrKU','mojoparts_magento');
$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
mysqli_real_connect($con, 'mojomysql2.c6orzbehh7d1.us-east-1.rds.amazonaws.com','mojo','3^-4Grj,;pF7[3kN','mojomagento');
if (!$con) {
    die('mysqli_init failed');
}

?>
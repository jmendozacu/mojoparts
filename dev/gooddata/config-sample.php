<?php
// Copy this file to config.php and fill in the local data and remove this comment
// NOTE: make sure to update config-sample.php with any changes
$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
mysqli_real_connect($con, '### DB SERVER ###','### DB user ###','### DB PASSWORD ###','### DB NAME ###');
if (!$con) {
    die('mysqli_init failed');
}

$con2 = mysqli_init();
mysqli_real_connect($con, '### DB SERVER ###','### DB user ###','### DB PASSWORD ###','### DB NAME ###');
if (!$con2) {
    die('mysqli_init failed');
}

?>
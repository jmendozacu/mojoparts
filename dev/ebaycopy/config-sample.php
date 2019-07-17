<?php
$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
mysqli_real_connect($con, '### DB SERVER ###','### DB user ###','### DB PASSWORD ###','### DB NAME ###');
if (!$con) {
    die('mysqli_init failed');
}

?>
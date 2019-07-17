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

$query = "SELECT * FROM cs_epids limit 900000 offset 5400001;";

$result_compat = mysqli_query($con, $query);
echo "CS-EPID,Notes,Year,Make,Model,Trim,Engine".PHP_EOL;

while($row = mysqli_fetch_array($result_compat)) {
	echo $row['CS-EPID'].",".$row['Notes'].",".$row['Year'].",".$row['Make'].",".$row['Model'].",".$row['Trim'].",".$row['Engine'].PHP_EOL;
}

mysqli_close($con);

?>
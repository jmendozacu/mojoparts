<?php
error_reporting(E_ALL);
$con = mysqli_init();
if (!$con) {
    die('mysqli_init failed');
}
mysqli_real_connect($con, 'mojomysql2.c6orzbehh7d1.us-east-1.rds.amazonaws.com','mojo','3^-4Grj,;pF7[3kN','mojo');
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
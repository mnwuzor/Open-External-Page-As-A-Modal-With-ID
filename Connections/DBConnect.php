<?php
# FileName="WADYN_MYSQLI_CONN.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_DBConnect = "localhost";
$database_DBConnect = "openmodalwtid";
$username_DBConnect = "root";
$password_DBConnect = "";
@session_start();

$DBConnect = mysqli_init();
if (defined("MYSQLI_OPT_INT_AND_FLOAT_NATIVE")) $DBConnect->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, TRUE);
$DBConnect->real_connect($hostname_DBConnect, $username_DBConnect, $password_DBConnect, $database_DBConnect) or die("Connect Error: " . mysqli_connect_error());

?>
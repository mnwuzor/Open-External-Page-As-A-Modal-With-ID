<?php
//DB Conn details
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'openmodalwtid';

//Create connection and select DB
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if($conn->connect_error){
    die("Unable to connect database: " . $conn->connect_error);
}
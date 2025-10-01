<?php
// config.php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'restaurant_stock';


$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS);
if (!$conn) { die('Connection failed: ' . mysqli_connect_error()); }


// Ensure database exists, then select
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, $DB_NAME);


// Helpers
function q($sql) {
global $conn;
$res = mysqli_query($conn, $sql);
if (!$res) {
die('SQL Error: ' . mysqli_error($conn) . "\nIn: " . $sql);
}
return $res;
}


function esc($str){
global $conn;
return mysqli_real_escape_string($conn, $str);
}


function num($v){
// very light numeric sanitization
if ($v === '' || $v === null) return 0;
return (float)$v;
}


session_start();
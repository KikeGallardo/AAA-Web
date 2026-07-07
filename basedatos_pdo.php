<?php
$servername = "127.0.0.1:3306";
$username = "aaa_user";
$password = "Aaa@Arbitros2026!";
$dbname = "aaa_arbitros";

$conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>

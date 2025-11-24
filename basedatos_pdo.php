<?php
$servername = "db-fde-02.apollopanel.com:3306";
$username = "u136076_tCDay64NMd";
$password = "AzlYnjAiSFN!d=ZtajgQa=q.";
$dbname = "s136076_Aribatraje";

$conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>

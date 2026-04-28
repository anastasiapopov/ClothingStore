<?php
// config/db.php

$host = "localhost";
$dbname = "turyshop_db";
$user = "root";
$pass = ""; // XAMPP default: parola e goală

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $conn = new mysqli($host, $user, $pass, $dbname);
  $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
  die("Eroare conexiune DB: " . $e->getMessage());
}

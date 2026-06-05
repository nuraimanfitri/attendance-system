<?php
$host = "localhost";
$user = "your_db";
$pass = "your_db_pass";
$db   = "your_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die(json_encode([
    "ok" => false,
    "msg" => "DB connection failed: " . $conn->connect_error
  ]));
}

$conn->set_charset("utf8mb4");
?>
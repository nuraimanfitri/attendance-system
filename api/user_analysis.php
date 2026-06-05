<?php
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
  echo json_encode(["ok"=>false,"msg"=>"Unauthorized"]);
  exit;
}

$staff_id = $_GET["staff_id"] ?? "";

if (!$staff_id) {
  echo json_encode(["ok"=>false,"msg"=>"Missing staff_id"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT *
  FROM attendance
  WHERE staff_id=?
  ORDER BY clock_in ASC
  LIMIT 100
");

$stmt->bind_param("s", $staff_id);
$stmt->execute();

$res = $stmt->get_result();
$list = [];

while ($row = $res->fetch_assoc()) {
  $list[] = $row;
}

echo json_encode([
  "ok"=>true,
  "data"=>$list
]);
?>
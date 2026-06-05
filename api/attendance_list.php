<?php
header("Content-Type: application/json");
include "auth_check.php";
include "db.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
  echo json_encode(["ok"=>false,"msg"=>"Unauthorized"]);
  exit;
}

$date = $_GET["date"] ?? date("Y-m-d");

$stmt = $conn->prepare("
  SELECT *
  FROM attendance
  WHERE DATE(clock_in)=?
  ORDER BY clock_in ASC
");

$stmt->bind_param("s", $date);
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
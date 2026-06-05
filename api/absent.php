<?php
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
  echo json_encode(["ok"=>false,"msg"=>"Unauthorized"]);
  exit;
}

$date = $_GET["date"] ?? date("Y-m-d");

$stmt = $conn->prepare("
  SELECT u.staff_id, u.name, u.position
  FROM users u
  WHERE u.status='active'
    AND u.role='staff'
    AND u.staff_id NOT IN (
      SELECT DISTINCT staff_id
      FROM attendance
      WHERE DATE(clock_in)=?
    )
  ORDER BY u.id ASC
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
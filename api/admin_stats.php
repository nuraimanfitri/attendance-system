<?php
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
  echo json_encode(["ok"=>false,"msg"=>"Unauthorized"]);
  exit;
}

$today = date("Y-m-d");

$totalUsers = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()["c"];
$activeUsers = $conn->query("SELECT COUNT(*) c FROM users WHERE status='active' AND role='staff'")->fetch_assoc()["c"];

$stmt = $conn->prepare("
  SELECT COUNT(DISTINCT staff_id) c 
  FROM attendance 
  WHERE DATE(clock_in)=?
");
$stmt->bind_param("s", $today);
$stmt->execute();
$attendanceToday = $stmt->get_result()->fetch_assoc()["c"];

$absentToday = max(0, $activeUsers - $attendanceToday);

echo json_encode([
  "ok"=>true,
  "totalUsers"=>$totalUsers,
  "activeUsers"=>$activeUsers,
  "attendanceToday"=>$attendanceToday,
  "absentToday"=>$absentToday
]);
?>
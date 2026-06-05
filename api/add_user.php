<?php
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
  echo json_encode(["ok"=>false,"msg"=>"Unauthorized"]);
  exit;
}

$staff_id = $_POST["staff_id"] ?? "";
$name = $_POST["name"] ?? "";
$password = $_POST["password"] ?? "";
$position = $_POST["position"] ?? "";
$status = $_POST["status"] ?? "active";
$role = $_POST["role"] ?? "staff";
$allow_ot = $_POST["allow_ot"] ?? "NO";

if (!$staff_id || !$name || !$password) {
  echo json_encode(["ok"=>false,"msg"=>"Missing data"]);
  exit;
}

$stmt = $conn->prepare("
  INSERT INTO users (staff_id, name, password, position, status, role, allow_ot)
  VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("sssssss", $staff_id, $name, $password, $position, $status, $role, $allow_ot);

if (!$stmt->execute()) {
  echo json_encode(["ok"=>false,"msg"=>$stmt->error]);
  exit;
}

echo json_encode(["ok"=>true,"msg"=>"User added"]);
?>
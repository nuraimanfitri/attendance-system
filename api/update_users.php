<?php
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
  echo json_encode(["ok"=>false,"msg"=>"Unauthorized"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
  echo json_encode(["ok"=>false,"msg"=>"Invalid data"]);
  exit;
}

foreach ($data as $u) {
  $stmt = $conn->prepare("
    UPDATE users
    SET name=?, password=?, position=?, status=?, role=?, allow_ot=?
    WHERE staff_id=?
  ");

  $stmt->bind_param(
    "sssssss",
    $u["name"],
    $u["password"],
    $u["position"],
    $u["status"],
    $u["role"],
    $u["allow_ot"],
    $u["staff_id"]
  );

  if (!$stmt->execute()) {
    echo json_encode(["ok"=>false,"msg"=>$stmt->error]);
    exit;
  }
}

echo json_encode(["ok"=>true,"msg"=>"Updated"]);
?>
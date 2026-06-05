<?php
header("Content-Type: application/json");
include "auth_check.php";

if (!isset($_SESSION["user"])) {
  echo json_encode(["ok"=>false,"msg"=>"Session expired"]);
  exit;
}

$staff_id = $_SESSION["user"]["staff_id"];
$date = $_GET["date"] ?? "";

if ($date !== "") {
  $stmt = $conn->prepare("
    SELECT *
    FROM attendance
    WHERE staff_id=?
      AND DATE(clock_in)=?
    ORDER BY clock_in DESC
  ");
  $stmt->bind_param("ss", $staff_id, $date);
} else {
  $stmt = $conn->prepare("
    SELECT *
    FROM attendance
    WHERE staff_id=?
    ORDER BY clock_in DESC
  ");
  $stmt->bind_param("s", $staff_id);
}

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
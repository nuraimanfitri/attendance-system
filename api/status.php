<?php
header("Content-Type: application/json");

include "auth_check.php";

if (!isset($_SESSION["user"])) {
  echo json_encode(["ok"=>false,"msg"=>"Session expired"]);
  exit;
}

$staff_id = $_SESSION["user"]["staff_id"];

$stmt = $conn->prepare("
  SELECT *
  FROM attendance
  WHERE staff_id=?
  ORDER BY id DESC
  LIMIT 1
");

$stmt->bind_param("s", $staff_id);
$stmt->execute();
$res = $stmt->get_result();

$row = null;
$open = false;

if ($res->num_rows > 0) {
  $row = $res->fetch_assoc();
  $open = empty($row["clock_out"]);
}

echo json_encode([
  "ok" => true,
  "open" => $open,
  "user" => [
    "staff_id" => $_SESSION["user"]["staff_id"] ?? "",
    "name" => $_SESSION["user"]["name"] ?? "",
    "position" => $_SESSION["user"]["position"] ?? "",
    "role" => $_SESSION["user"]["role"] ?? "",
    "allow_ot" => $_SESSION["user"]["allow_ot"] ?? "NO"
  ],
  "data" => $row
]);
?>
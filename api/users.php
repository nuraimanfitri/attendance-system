<?php
header("Content-Type: application/json");
include "db.php";

$res = $conn->query("
  SELECT 
    staff_id,
    name,
    COALESCE(password, '') AS password,
    role,
    COALESCE(position, '') AS position,
    COALESCE(status, '') AS status,
    COALESCE(allow_ot, 'NO') AS allow_ot
  FROM users
  ORDER BY id ASC
");

if (!$res) {
  echo json_encode(["ok"=>false,"msg"=>$conn->error]);
  exit;
}

$list = [];

while ($row = $res->fetch_assoc()) {
  $list[] = $row;
}

echo json_encode([
  "ok"=>true,
  "data"=>$list,
  "time"=>time()
]);
?>
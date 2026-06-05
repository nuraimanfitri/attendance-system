<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

include "db.php";
include "send_push.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

function getSchedule($date) {
  $day = (int)date("N", strtotime($date));

  $schedule = [
    1 => ["start" => "15:00", "end" => "22:30"],
    2 => ["start" => "15:00", "end" => "22:30"],
    3 => ["start" => "15:00", "end" => "22:30"],
    4 => ["start" => "15:00", "end" => "22:30"],
    5 => ["start" => "15:00", "end" => "22:30"],
    6 => ["start" => "08:00", "end" => "14:00"]
  ];

  return $schedule[$day] ?? null;
}

$res = $conn->query("
  SELECT 
    a.id,
    a.staff_id,
    a.clock_in,
    COALESCE(u.allow_ot, 'NO') AS allow_ot
  FROM attendance a
  INNER JOIN users u 
    ON u.staff_id = a.staff_id
  WHERE a.clock_in IS NOT NULL
    AND a.clock_out IS NULL
");

if (!$res) {
  echo json_encode([
    "ok" => false,
    "msg" => "Select failed: " . $conn->error
  ]);
  exit;
}

$updated = 0;
$skipped = 0;
$checked = 0;
$nowTs = time();
$details = [];

while ($row = $res->fetch_assoc()) {
  $checked++;

  $id = (int)$row["id"];
  $staffId = $row["staff_id"];
  $clockIn = $row["clock_in"];
  $allowOt = strtoupper(trim($row["allow_ot"] ?? "NO"));

  if ($allowOt === "YES") {
    $skipped++;

    $details[] = [
      "id" => $id,
      "staff_id" => $staffId,
      "allow_ot" => $allowOt,
      "reason" => "Allow OT enabled / skipped auto clock out"
    ];

    continue;
  }

  $sched = getSchedule($clockIn);

  if (!$sched) {
    $skipped++;

    $details[] = [
      "id" => $id,
      "staff_id" => $staffId,
      "allow_ot" => $allowOt,
      "reason" => "No schedule / off day"
    ];

    continue;
  }

  $date = date("Y-m-d", strtotime($clockIn));
  $shiftEndStr = $date . " " . $sched["end"];
  $shiftEndTs = strtotime($shiftEndStr);

  if ($nowTs < $shiftEndTs) {
    $skipped++;

    $details[] = [
      "id" => $id,
      "staff_id" => $staffId,
      "allow_ot" => $allowOt,
      "reason" => "Not yet shift end",
      "shift_end" => $shiftEndStr,
      "now" => date("Y-m-d H:i:s", $nowTs)
    ];

    continue;
  }

  $stmt = $conn->prepare("
    UPDATE attendance
    SET clock_out = ?,
        status_out = 'AUTO CLOCK OUT'
    WHERE id = ?
      AND clock_out IS NULL
  ");

  if (!$stmt) {
    echo json_encode([
      "ok" => false,
      "msg" => "Update prepare failed: " . $conn->error
    ]);
    exit;
  }

  $stmt->bind_param("si", $shiftEndStr, $id);

  if (!$stmt->execute()) {
    echo json_encode([
      "ok" => false,
      "msg" => "Update failed: " . $stmt->error
    ]);
    exit;
  }

  if ($stmt->affected_rows > 0) {
    $updated++;

    $pushResponse = sendPushToStaff(
      $conn,
      $staffId,
      "Auto Clock Out",
      "You have been automatically clocked out."
    );

    $details[] = [
      "id" => $id,
      "staff_id" => $staffId,
      "allow_ot" => $allowOt,
      "clock_out" => $shiftEndStr,
      "status" => "AUTO CLOCK OUT",
      "push_response" => $pushResponse
    ];
  } else {
    $skipped++;

    $details[] = [
      "id" => $id,
      "staff_id" => $staffId,
      "allow_ot" => $allowOt,
      "reason" => "Already clocked out / no update"
    ];
  }
}

echo json_encode([
  "ok" => true,
  "checked" => $checked,
  "updated" => $updated,
  "skipped" => $skipped,
  "details" => $details
]);

?>
<?php
header("Content-Type: application/json");

include "auth_check.php";
include "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION["user"])) {
  echo json_encode([
    "ok" => false,
    "msg" => "Session expired"
  ]);
  exit;
}

$staff_id = $_SESSION["user"]["staff_id"];
$allow_ot = strtoupper($_SESSION["user"]["allow_ot"] ?? "NO");

$location = $_POST["location"] ?? "";
$notes = $_POST["notes"] ?? "";
$photo = $_POST["photo"] ?? "";

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

function getClockOutStatus($now, $allow_ot) {

  $sched = getSchedule($now);

  // OFF DAY
  if (!$sched) {

    if ($allow_ot === "YES") {
      return "OVERTIME";
    }

    return "OFF DAY";
  }

  $date = date("Y-m-d", strtotime($now));

  $shiftEnd = strtotime(
    $date . " " . $sched["end"]
  );

  $clockOut = strtotime($now);

  // CLOCK OUT BEFORE SHIFT END
  if ($clockOut < $shiftEnd) {
    return "LEFT BEFORE SHIFT END";
  }

  // OVERTIME
  if (
    $clockOut > $shiftEnd &&
    $allow_ot === "YES"
  ) {
    return "OVERTIME";
  }

  // ON TIME
  return "ON TIME";
}

$photoPath = "";

if ($photo) {

  $photo = preg_replace(
    '/^data:image\/\w+;base64,/',
    '',
    $photo
  );

  $photo = str_replace(' ', '+', $photo);

  $data = base64_decode($photo);

  if ($data === false) {
    echo json_encode([
      "ok" => false,
      "msg" => "Photo decode failed"
    ]);
    exit;
  }

  $fileName =
    $staff_id .
    "_OUT_" .
    date("Ymd_His") .
    ".jpg";

  $filePath = "../uploads/" . $fileName;

  if (
    file_put_contents($filePath, $data) === false
  ) {
    echo json_encode([
      "ok" => false,
      "msg" => "Failed to save photo"
    ]);
    exit;
  }

  $photoPath = "uploads/" . $fileName;
}

$now = date("Y-m-d H:i:s");

$status_out = getClockOutStatus(
  $now,
  $allow_ot
);

$stmt = $conn->prepare("
  UPDATE attendance
  SET
    clock_out = ?,
    location_out = ?,
    photo_out = ?,
    notes_out = ?,
    status_out = ?
  WHERE staff_id = ?
    AND clock_out IS NULL
  ORDER BY id DESC
  LIMIT 1
");

if (!$stmt) {
  echo json_encode([
    "ok" => false,
    "msg" => "Prepare failed: " . $conn->error
  ]);
  exit;
}

$stmt->bind_param(
  "ssssss",
  $now,
  $location,
  $photoPath,
  $notes,
  $status_out,
  $staff_id
);

if (!$stmt->execute()) {
  echo json_encode([
    "ok" => false,
    "msg" => "Update failed: " . $stmt->error
  ]);
  exit;
}

if ($stmt->affected_rows < 1) {
  echo json_encode([
    "ok" => false,
    "msg" => "No open clock in record"
  ]);
  exit;
}

echo json_encode([
  "ok" => true,
  "msg" => "Clock Out success",
  "status_out" => $status_out
]);
?>
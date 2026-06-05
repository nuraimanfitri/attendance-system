<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

include "auth_check.php";
include "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION["user"])) {
  echo json_encode(["ok"=>false,"msg"=>"Session expired"]);
  exit;
}

$user = $_SESSION["user"];

$staff_id = $user["staff_id"];
$name = $user["name"];
$position = $user["position"];
$allow_ot = strtoupper($user["allow_ot"] ?? "NO");

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

function getClockInStatus($now, $allow_ot) {
  $sched = getSchedule($now);

  if (!$sched) {
    if ($allow_ot === "YES") {
      return "OVERTIME";
    }

    return "BLOCK_OFF_DAY";
  }

  $date = date("Y-m-d", strtotime($now));
  $shiftStart = strtotime($date . " " . $sched["start"]);
  $clockIn = strtotime($now);

  $day = (int)date("N", strtotime($now));
  $grace = ($day === 6) ? 5 : 10;

  if ($clockIn < $shiftStart) return "EARLY";
  if ($clockIn <= ($shiftStart + ($grace * 60))) return "ON TIME";

  return "LATE";
}

$now = date("Y-m-d H:i:s");
$status_in = getClockInStatus($now, $allow_ot);

if ($status_in === "BLOCK_OFF_DAY") {
  echo json_encode([
    "ok" => false,
    "msg" => "Today is an off day. You are not allowed to clock in."
  ]);
  exit;
}

$check = $conn->prepare("
  SELECT id
  FROM attendance
  WHERE staff_id = ?
    AND clock_out IS NULL
  LIMIT 1
");

$check->bind_param("s", $staff_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
  echo json_encode(["ok"=>false,"msg"=>"You have not clocked out yet"]);
  exit;
}

$photoPath = "";

if ($photo) {
  $photo = preg_replace('/^data:image\/\w+;base64,/', '', $photo);
  $photo = str_replace(' ', '+', $photo);
  $data = base64_decode($photo);

  if ($data === false) {
    echo json_encode(["ok"=>false,"msg"=>"Photo decode failed"]);
    exit;
  }

  $fileName = $staff_id . "_IN_" . date("Ymd_His") . ".jpg";
  $uploadDir = __DIR__ . "/../uploads/";

  if (!is_dir($uploadDir)) {
    echo json_encode(["ok"=>false,"msg"=>"Uploads folder not found"]);
    exit;
  }

  $filePath = $uploadDir . $fileName;

  if (file_put_contents($filePath, $data) === false) {
    echo json_encode(["ok"=>false,"msg"=>"Failed to save photo. Check uploads permission"]);
    exit;
  }

  $photoPath = "uploads/" . $fileName;
}

$stmt = $conn->prepare("
  INSERT INTO attendance
  (staff_id, name, position, clock_in, location_in, photo_in, notes_in, status_in)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
  echo json_encode(["ok"=>false,"msg"=>"Prepare failed: " . $conn->error]);
  exit;
}

$stmt->bind_param(
  "ssssssss",
  $staff_id,
  $name,
  $position,
  $now,
  $location,
  $photoPath,
  $notes,
  $status_in
);

if (!$stmt->execute()) {
  echo json_encode(["ok"=>false,"msg"=>"Insert failed: " . $stmt->error]);
  exit;
}

echo json_encode([
  "ok"=>true,
  "msg"=>"Clock In success",
  "status_in"=>$status_in
]);
?>
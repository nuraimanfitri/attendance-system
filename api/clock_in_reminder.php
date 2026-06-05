<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$now = date("Y-m-d H:i:s");
$today = date("Y-m-d");

$sched = getSchedule($now);

if (!$sched) {
  echo "Off day. No reminder.";
  exit;
}

$shiftStartTs = strtotime($today . " " . $sched["start"]);
$reminderTs = $shiftStartTs - (10 * 60);
$nowTs = time();

if ($nowTs < $reminderTs || $nowTs > ($reminderTs + 300)) {
  echo "Not reminder time.";
  exit;
}

$res = $conn->query("
  SELECT staff_id, name
  FROM users
  WHERE role = 'staff'
    AND status = 'active'
");

if (!$res) {
  echo "Select users failed: " . $conn->error;
  exit;
}

$sent = 0;
$skipped = 0;
$details = [];

while ($u = $res->fetch_assoc()) {
  $staff_id = $u["staff_id"];
  $name = $u["name"];

  $check = $conn->prepare("
    SELECT id
    FROM attendance
    WHERE staff_id = ?
      AND DATE(clock_in) = ?
    LIMIT 1
  ");

  if (!$check) {
    echo "Prepare attendance check failed: " . $conn->error;
    exit;
  }

  $check->bind_param("ss", $staff_id, $today);
  $check->execute();

  $hasClockIn = $check->get_result()->num_rows > 0;

  if (!$hasClockIn) {
    $pushResponse = sendPushToStaff(
      $conn,
      $staff_id,
      "Clock In Reminder",
      "Your shift starts in 10 minutes. Please remember to clock in."
    );

    $sent++;

    $details[] = [
      "staff_id" => $staff_id,
      "name" => $name,
      "push_response" => $pushResponse
    ];
  } else {
    $skipped++;

    $details[] = [
      "staff_id" => $staff_id,
      "name" => $name,
      "reason" => "Already clocked in"
    ];
  }
}

echo json_encode([
  "ok" => true,
  "sent" => $sent,
  "skipped" => $skipped,
  "details" => $details
]);

?>
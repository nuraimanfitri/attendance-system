<?php

include "auth_check.php";
include "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
  echo json_encode([
    "ok" => false,
    "msg" => "Not logged in"
  ]);
  exit;
}

$staff_id = $_SESSION['user']['staff_id'] ?? "";
$data = json_decode(file_get_contents("php://input"), true);
$subId = $data["onesignal_subscription_id"] ?? "";

if (!$staff_id || !$subId) {
  echo json_encode([
    "ok" => false,
    "msg" => "Invalid subscription"
  ]);
  exit;
}

$check = $conn->prepare("
  SELECT onesignal_subscription_id
  FROM push_subscriptions
  WHERE staff_id = ?
  LIMIT 1
");

$check->bind_param("s", $staff_id);
$check->execute();

$res = $check->get_result();

$isNewOrChanged = true;

if ($row = $res->fetch_assoc()) {
  if ($row["onesignal_subscription_id"] === $subId) {
    $isNewOrChanged = false;
  }
}

$stmt = $conn->prepare("
  INSERT INTO push_subscriptions
  (staff_id, onesignal_subscription_id)
  VALUES (?, ?)
  ON DUPLICATE KEY UPDATE
    onesignal_subscription_id = VALUES(onesignal_subscription_id),
    updated_at = NOW()
");

if (!$stmt) {
  echo json_encode([
    "ok" => false,
    "msg" => "Prepare failed: " . $conn->error
  ]);
  exit;
}

$stmt->bind_param("ss", $staff_id, $subId);

if (!$stmt->execute()) {
  echo json_encode([
    "ok" => false,
    "msg" => $stmt->error
  ]);
  exit;
}

if ($isNewOrChanged) {
  include "send_push.php";

  sendPushToStaff(
    $conn,
    $staff_id,
    "Notification Subscribed",
    "You will now receive attendance notifications."
  );
}

echo json_encode([
  "ok" => true,
  "msg" => "Subscription saved",
  "notified" => $isNewOrChanged
]);

?>
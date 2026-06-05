<?php

include "config_push.php";

function sendPushToStaff($conn, $staff_id, $title, $message) {

  $stmt = $conn->prepare("
    SELECT onesignal_subscription_id
    FROM push_subscriptions
    WHERE staff_id = ?
    LIMIT 1
  ");

  if (!$stmt) {
    return json_encode([
      "ok" => false,
      "msg" => "Prepare failed: " . $conn->error
    ]);
  }

  $stmt->bind_param("s", $staff_id);
  $stmt->execute();

  $res = $stmt->get_result();

  if (!$row = $res->fetch_assoc()) {
    return json_encode([
      "ok" => false,
      "msg" => "No push subscription for staff_id: " . $staff_id
    ]);
  }

  $subscriptionId = $row["onesignal_subscription_id"];

  $data = [
    "app_id" => ONESIGNAL_APP_ID,
    "include_subscription_ids" => [$subscriptionId],
    "headings" => [
      "en" => $title
    ],
    "contents" => [
      "en" => $message
    ],
    "url" => "https://www.pusattuisyenmathspower.my/attendance/staff.php"
  ];

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");

  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json; charset=utf-8",
    "Authorization: Basic " . ONESIGNAL_REST_API_KEY
  ]);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

  $response = curl_exec($ch);
  $error = curl_error($ch);

  curl_close($ch);

  if ($error) {
    return json_encode([
      "ok" => false,
      "msg" => $error
    ]);
  }

  return $response;
}

?>
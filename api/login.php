<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);

session_set_cookie_params([
  'lifetime' => 60 * 60 * 24 * 30,
  'path' => '/',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);

session_start();

include "db.php";

$staff_id = $_POST["id"] ?? "";
$password = $_POST["password"] ?? "";
$remember = $_POST["remember"] ?? "";

if ($staff_id === "" || $password === "") {
  echo json_encode(["ok" => false, "msg" => "Please fill ID & password"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT staff_id, name, password, position, status, role, allow_ot
  FROM users
  WHERE staff_id = ?
  LIMIT 1
");

if (!$stmt) {
  echo json_encode(["ok" => false, "msg" => "Prepare failed: " . $conn->error]);
  exit;
}

$stmt->bind_param("s", $staff_id);

if (!$stmt->execute()) {
  echo json_encode(["ok" => false, "msg" => "Login query failed: " . $stmt->error]);
  exit;
}

$res = $stmt->get_result();

if ($res->num_rows === 0) {
  echo json_encode(["ok" => false, "msg" => "User not found"]);
  exit;
}

$user = $res->fetch_assoc();

if ($user["status"] !== "active") {
  echo json_encode(["ok" => false, "msg" => "User inactive"]);
  exit;
}

if ($password !== $user["password"]) {
  echo json_encode(["ok" => false, "msg" => "Wrong password"]);
  exit;
}

$_SESSION["user"] = [
  "staff_id" => $user["staff_id"],
  "name" => $user["name"],
  "position" => $user["position"],
  "role" => $user["role"],
  "allow_ot" => $user["allow_ot"]
];

if ($remember === "on") {
  $token = bin2hex(random_bytes(32));
  $expiry = date("Y-m-d H:i:s", time() + (60 * 60 * 24 * 30));

  $stmt = $conn->prepare("
    UPDATE users
    SET remember_token = ?, remember_expiry = ?
    WHERE staff_id = ?
  ");

  if (!$stmt) {
    echo json_encode(["ok" => false, "msg" => "Remember prepare failed: " . $conn->error]);
    exit;
  }

  $stmt->bind_param("sss", $token, $expiry, $staff_id);

  if (!$stmt->execute()) {
    echo json_encode(["ok" => false, "msg" => "Remember update failed: " . $stmt->error]);
    exit;
  }

  setcookie("remember_token", $token, [
    "expires" => time() + (60 * 60 * 24 * 30),
    "path" => "/",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
  ]);

} else {
  $stmt = $conn->prepare("
    UPDATE users
    SET remember_token = NULL, remember_expiry = NULL
    WHERE staff_id = ?
  ");

  if ($stmt) {
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
  }

  setcookie("remember_token", "", [
    "expires" => time() - 3600,
    "path" => "/",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
  ]);
}

echo json_encode([
  "ok" => true,
  "staff_id" => $user["staff_id"],
  "name" => $user["name"],
  "position" => $user["position"],
  "role" => $user["role"],
  "allow_ot" => $user["allow_ot"]
]);
?>
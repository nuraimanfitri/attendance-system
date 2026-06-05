<?php
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

if (!isset($_SESSION["user"]) && isset($_COOKIE["remember_token"])) {
  $token = $_COOKIE["remember_token"];

  $stmt = $conn->prepare("SELECT staff_id, name, position, role, allow_ot FROM users WHERE remember_token=? AND remember_expiry > NOW() LIMIT 1");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows > 0) {
    $_SESSION["user"] = $res->fetch_assoc();
  }
}

if (!isset($_SESSION["user"])) {
  echo json_encode([
    "ok" => false,
    "msg" => "No session"
  ]);
  exit;
}

echo json_encode([
  "ok" => true,
  "user" => $_SESSION["user"]
]);
?>
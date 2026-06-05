<?php
include "auth_check.php";
include "db.php";

if (isset($_SESSION["user"]["staff_id"])) {

  $staff_id = $_SESSION["user"]["staff_id"];

  $stmt = $conn->prepare("
    UPDATE users
    SET remember_token = NULL,
        remember_expiry = NULL
    WHERE staff_id = ?
  ");

  if ($stmt) {
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
  }

}

setcookie(
  "remember_token",
  "",
  time() - 3600,
  "/"
);

$_SESSION = [];

session_destroy();

header("Content-Type: application/json");

echo json_encode([
  "ok" => true
]);
?>
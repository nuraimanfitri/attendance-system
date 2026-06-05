<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);

session_set_cookie_params([
  'lifetime' => 60 * 60 * 24 * 30,
  'path' => '/',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

include_once "db.php";

if (!isset($_SESSION["user"])) {

  if (!empty($_COOKIE["remember_token"])) {

    $token = $_COOKIE["remember_token"];

    $stmt = $conn->prepare("
      SELECT staff_id, name, position, role, allow_ot
      FROM users
      WHERE remember_token = ?
      AND (
        remember_expiry IS NULL
        OR remember_expiry > NOW()
      )
      LIMIT 1
    ");

    if ($stmt) {

      $stmt->bind_param("s", $token);
      $stmt->execute();

      $res = $stmt->get_result();

      if ($res->num_rows > 0) {

        $user = $res->fetch_assoc();

        $_SESSION["user"] = [
          "staff_id" => $user["staff_id"],
          "name" => $user["name"],
          "position" => $user["position"],
          "role" => $user["role"],
          "allow_ot" => $user["allow_ot"]
        ];

      } else {

        setcookie(
          "remember_token",
          "",
          time() - 3600,
          "/"
        );

      }

    }

  }

}
?>
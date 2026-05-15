<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

// Authorization
if (($modify_notification ?? "false") !== "true") {
    header("Location: home.php");
    exit;
}

// POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: notification.php");
    exit;
}

csrf_verify();

// Use session user_id only (no user_id from GET/POST)
if (!isset($user_id) || !is_numeric($user_id)) {
    header("Location: login.php");
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE notification SET status = 'read' WHERE user_id = ?");
if (!$stmt) {
    header("Location: notification.php?err=server");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: notification.php" . ($ok ? "" : "?err=update_failed"));
exit;
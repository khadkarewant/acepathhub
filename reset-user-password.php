<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

// Authorization: only admin/teacher allowed (keep your rule)
if (!isset($type) || ($type !== "admin" && $type !== "teacher")) {
    header("Location: home.php");
    exit;
}

// POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

// CSRF
csrf_verify();

// Validate input
if (!isset($_POST['reset_user_id']) || !ctype_digit($_POST['reset_user_id'])) {
    header("Location: users.php?err=bad_request");
    exit;
}

$reset_user_id = (int)$_POST['reset_user_id'];

// Optional: block resetting your own password (usually a good policy)
if (isset($user_id) && (int)$user_id === $reset_user_id) {
    header("Location: user-details.php?id=" . $reset_user_id . "&err=cannot_reset_self");
    exit;
}

// Ensure user exists
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
if (!$stmt) {
    header("Location: users.php?err=server");
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $reset_user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$exists = mysqli_stmt_num_rows($stmt) === 1;
mysqli_stmt_close($stmt);

if (!$exists) {
    header("Location: users.php?err=not_found");
    exit;
}

// Reset password to default (your chosen policy)
// NOTE: This is still risky in real production. Better approach: generate random temp + force change.
// But we’ll keep your current behavior and make it safe.
$default_plain = 'quizmania@1234';
$new_password_hash = password_hash($default_plain, PASSWORD_DEFAULT);

// Transaction: update + notification must succeed together
mysqli_begin_transaction($conn);

try {
    // Update password + force logout
    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, is_session = 'false' WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("stmt_update_failed");
    }
    mysqli_stmt_bind_param($stmt, "si", $new_password_hash, $reset_user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        throw new Exception("update_failed");
    }

    // Insert notification
    $note = "Password reset to default. Please change it to secure your profile.";
    $d = date("Y-m-d");
    $t = date("H:i:s");

    $stmt = mysqli_prepare($conn, "INSERT INTO notification (user_id, notification, date, time) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("stmt_note_failed");
    }
    mysqli_stmt_bind_param($stmt, "isss", $reset_user_id, $note, $d, $t);
    $ok2 = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok2) {
        throw new Exception("note_failed");
    }

    mysqli_commit($conn);

    header("Location: user-details.php?id=" . $reset_user_id . "&reset=1");
    exit;
} catch (Throwable $e) {
    mysqli_rollback($conn);
    header("Location: user-details.php?id=" . $reset_user_id . "&err=reset_failed");
    exit;
}
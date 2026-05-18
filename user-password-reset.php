<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

csrf_verify();

$target_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$target_user_id) {
    header('Location: users.php');
    exit;
}

// Cannot reset own password via this handler
if ((int)$user_id === $target_user_id) {
    header('Location: user-details.php?user_id=' . $target_user_id . '&err=self');
    exit;
}

// Confirm user exists and is not admin
$stmt = mysqli_prepare($conn,
    'SELECT user_id FROM users WHERE user_id = ? AND role != ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ii', $target_user_id, ROLE_ADMIN);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$user) {
    header('Location: users.php');
    exit;
}

$default_plain = 'acepathhub@1234';
$new_hash      = password_hash($default_plain, PASSWORD_DEFAULT);
$note          = 'Your password has been reset to default. Please change it immediately.';
$date          = date('Y-m-d');
$time          = date('H:i:s');

mysqli_begin_transaction($conn);
try {
    // Reset password + force logout
    $stmt = mysqli_prepare($conn,
        'UPDATE users SET password = ?, is_session = 0 WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $new_hash, $target_user_id);
    if (!mysqli_stmt_execute($stmt)) throw new Exception();
    mysqli_stmt_close($stmt);

    // Notify user
    $stmt = mysqli_prepare($conn,
        'INSERT INTO notification (user_id, notification, date, time)
         VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'isss', $target_user_id, $note, $date, $time);
    if (!mysqli_stmt_execute($stmt)) throw new Exception();
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    header('Location: user-details.php?user_id=' . $target_user_id . '&ok=1');
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    header('Location: user-details.php?user_id=' . $target_user_id . '&err=1');
    exit;
}
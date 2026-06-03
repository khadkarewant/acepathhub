<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

csrf_verify();

$target_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$action         = $_POST['action'] ?? '';

if (!$target_user_id || !in_array($action, ['block', 'unblock'], true)) {
    header('Location: users.php');
    exit;
}

// Confirm user exists and is not admin
$admin_role = ROLE_ADMIN;
$stmt = mysqli_prepare($conn,
    'SELECT user_id FROM users WHERE user_id = ? AND role != ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ii', $target_user_id, $admin_role);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$user) {
    header('Location: users.php');
    exit;
}

$is_blocked = $action === 'block' ? 1 : 0;

$stmt = mysqli_prepare($conn,
    'UPDATE users SET is_blocked = ? WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $is_blocked, $target_user_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    header('Location: user-details.php?user_id=' . $target_user_id . '&err=1');
    exit;
}

header('Location: user-details.php?user_id=' . $target_user_id . '&ok=1');
exit;
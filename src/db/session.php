<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$https =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
require_once __DIR__ . '/../config/csrf.php';


// Redirect if user not logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data and validate session token
$stmt = mysqli_prepare($conn,
    "SELECT user_id, username, first_name, middle_name, last_name,
            email, phone, pin, dob, gender, country, city,
            postal_code, role, status, last_login_on, last_login_at,
            referral_code, is_session, session_token
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);
if (!$stmt) {
    session_destroy();
    header("Location: login.php");
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) !== 1) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check if session is valid (single-device login)
if ($row['is_session'] !== 1 || !isset($_SESSION['session_token']) || !hash_equals($row['session_token'], $_SESSION['session_token'])) {
    // Invalid session → force logout
    session_destroy();
    header("Location: login.php?msg=Session expired");
    exit;
}

// ===================== User Data =====================
$user = $row;
$user['role'] = (int)$row['role'];
$role = $user['role'];

$incomplete_fields = array_filter([
    $user['gender'],
    $user['country'],
    $user['city'],
    $user['postal_code'],
], fn($v) => $v === null);

$uncompleted_details = count($incomplete_fields);
?>

<?php
include("src/db/db_conn.php");
require_once("src/config/public_bootstrap.php");

// Check if user is logged in
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = $_SESSION['id'];

    // Clear session token and mark user as not active
    $stmt = mysqli_prepare($conn,
    'UPDATE users SET last_login = NOW(), is_session = 0, session_token = NULL, session_token_time = NULL WHERE user_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
header("Location: login.php");
exit;
?>

<?php
ob_start();
include("src/db/db_conn.php");
include("src/db/session.php");

// Check if user is logged in
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = $_SESSION['id'];

    // Clear session token and mark user as not active
    mysqli_query($conn, "
        UPDATE `users` 
        SET `last_login_on` = '".date("Y-m-d")."',
            `last_login_at` = '".date("H:i:s")."',
            `is_session` = 'false',
            `session_token` = NULL,
            `session_token_time` = NULL
        WHERE `user_id` = '$user_id'
    ");

    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
header("Location: login.php");
exit;
ob_end_flush();
?>

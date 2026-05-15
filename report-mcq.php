<?php
include("src/db/db_conn.php");
include("src/db/session.php");

$user_id = $_SESSION['id'] ?? 0;
$mcq_id  = intval($_POST['mcq_id'] ?? 0);
$reason  = trim($_POST['reason'] ?? '');

if (!$user_id || !$mcq_id || !$reason) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing data'
    ]);
    exit;
}

// Prevent duplicate reports
$check = mysqli_query($conn, "
    SELECT id 
    FROM mcq_reports 
    WHERE user_id='$user_id' AND mcq_id='$mcq_id'
    LIMIT 1
");

if (mysqli_num_rows($check) > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You already reported this question'
    ]);
    exit;
}

// Insert report
mysqli_query($conn, "
    INSERT INTO mcq_reports (user_id, mcq_id, reason, created_at)
    VALUES (
        '$user_id',
        '$mcq_id',
        '".mysqli_real_escape_string($conn, $reason)."',
        NOW()
    )
");

echo json_encode([
    'status' => 'ok'
]);

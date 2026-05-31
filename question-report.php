<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

header('Content-Type: application/json');

require_role(ROLE_STUDENT);

csrf_verify();

$question_id = isset($_POST['question_id']) && ctype_digit($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$reason      = trim($_POST['reason'] ?? '');

if ($question_id === 0 || $reason === '') {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

// Check duplicate
$stmt = mysqli_prepare($conn,
    "SELECT id FROM question_reports WHERE user_id = ? AND question_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $question_id);
mysqli_stmt_execute($stmt);
$r    = mysqli_stmt_get_result($stmt);
$dupe = mysqli_fetch_assoc($r);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if ($dupe) {
    echo json_encode(['status' => 'error', 'message' => 'You already reported this question']);
    exit;
}

$stmt = mysqli_prepare($conn,
    "INSERT INTO question_reports (user_id, question_id, reason) VALUES (?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'iis', $user_id, $question_id, $reason);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'ok']);
} else {
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit report']);
}
exit;
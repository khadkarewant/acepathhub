<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

csrf_verify();

$question_set_id = filter_input(INPUT_POST, 'question_set_id', FILTER_VALIDATE_INT);
$status          = $_POST['status'] ?? '';

if (!$question_set_id || !in_array($status, ['draft', 'published'], true)) {
    header('Location: home.php');
    exit;
}

// Confirm set exists and is verified (only verified sets can be status-toggled)
$stmt = mysqli_prepare($conn,
    'SELECT topic_id FROM question_sets WHERE id = ? AND verified = 1');
mysqli_stmt_bind_param($stmt, 'i', $question_set_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$set = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$set) {
    header('Location: home.php');
    exit;
}

$topic_id = (int)$set['topic_id'];

$stmt = mysqli_prepare($conn,
    'UPDATE question_sets SET status = ? WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'si', $status, $question_set_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    header('Location: draft-mcq-list.php?topic_id=' . $topic_id . '&err=1');
    exit;
}

if ($status === 'published') {
    header('Location: question-set-list.php?topic_id=' . $topic_id . '&ok=1');
} else {
    header('Location: draft-mcq-list.php?topic_id=' . $topic_id . '&ok=1');
}
exit;
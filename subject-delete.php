<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: exam-body-list.php");
    exit;
}

csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT exam_body_id FROM subjects WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subject = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$subject) {
    header("Location: exam-body-list.php");
    exit;
}

$exam_body_id = (int)$subject['exam_body_id'];
$back = "subject-list.php?exam_body_id={$exam_body_id}";

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM topics WHERE subject_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($count > 0) {
    header("Location: {$back}&error=has_topics");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM subjects WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: {$back}&deleted=1");
    exit;
}
mysqli_stmt_close($stmt);
header("Location: {$back}&error=delete_failed");
exit;
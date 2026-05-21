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

$stmt = mysqli_prepare($conn, "SELECT subject_id FROM topics WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$topic = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$topic) {
    header("Location: exam-body-list.php");
    exit;
}

$subject_id = (int)$topic['subject_id'];
$back = "topic-list.php?subject_id={$subject_id}";

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM question_sets WHERE topic_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $qs_count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($qs_count > 0) {
    header("Location: {$back}&error=has_questions");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM exam_group_topics WHERE topic_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $egt_count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($egt_count > 0) {
    header("Location: {$back}&error=in_use");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM practice_group_topics WHERE topic_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $pgt_count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($pgt_count > 0) {
    header("Location: {$back}&error=in_use");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM topics WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: {$back}&deleted=1");
    exit;
}
mysqli_stmt_close($stmt);
header("Location: {$back}&error=delete_failed");
exit;
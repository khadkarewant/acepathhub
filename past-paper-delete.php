<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: exam-body-list.php");
    exit;
}

csrf_verify();

$id         = (int)($_POST['id'] ?? 0);
$subject_id = (int)($_POST['subject_id'] ?? 0);

if ($id === 0 || $subject_id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

// Block delete if question_sets exist
$stmt = mysqli_prepare($conn, "SELECT id FROM question_sets WHERE past_paper_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$check = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if (mysqli_num_rows($check) > 0) {
    header("Location: past-paper-list.php?subject_id={$subject_id}&error=has_questions");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM past_papers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
$deleted = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($deleted) {
    header("Location: past-paper-list.php?subject_id={$subject_id}&deleted=1");
} else {
    header("Location: past-paper-list.php?subject_id={$subject_id}&error=delete_failed");
}
exit;
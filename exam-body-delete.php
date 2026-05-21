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

$stmt = mysqli_prepare($conn, "SELECT id FROM exam_bodies WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$exists = mysqli_stmt_num_rows($stmt) > 0;
mysqli_stmt_close($stmt);

if (!$exists) {
    header("Location: exam-body-list.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM subjects WHERE exam_body_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($count > 0) {
    header("Location: exam-body-list.php?error=has_subjects");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM exam_bodies WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: exam-body-list.php?deleted=1");
    exit;
}
mysqli_stmt_close($stmt);
header("Location: exam-body-list.php?error=delete_failed");
exit;
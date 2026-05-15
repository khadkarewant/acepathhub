<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";

// Admin only
if (($type ?? null) !== "admin") {
    header("Location: home.php");
    exit;
}

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: home.php");
    exit;
}

// CSRF
csrf_verify();

// Validate input
$mcq_id = filter_input(INPUT_POST, 'mcq_id', FILTER_VALIDATE_INT);
if ($mcq_id === false || $mcq_id === null) {
    header("Location: home.php");
    exit;
}

// Get topic_id (ensures mcq exists)
$stmt = mysqli_prepare($conn, "SELECT topic_id FROM mcqs WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $mcq_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: home.php");
    exit;
}

$row = mysqli_fetch_assoc($res);
$topic_id = (int)$row['topic_id'];

// Delete atomically
mysqli_begin_transaction($conn);

try {
    // child rows first (unless you have FK cascade)
    $stmtQ = mysqli_prepare($conn, "DELETE FROM questions WHERE mcq_id = ?");
    mysqli_stmt_bind_param($stmtQ, "i", $mcq_id);
    if (!mysqli_stmt_execute($stmtQ)) {
        throw new Exception("Failed deleting questions");
    }

    $stmtM = mysqli_prepare($conn, "DELETE FROM mcqs WHERE id = ?");
    mysqli_stmt_bind_param($stmtM, "i", $mcq_id);
    if (!mysqli_stmt_execute($stmtM)) {
        throw new Exception("Failed deleting mcq");
    }

    mysqli_commit($conn);

    header("Location: drafted-mcqs-details.php?topic_id=" . $topic_id);
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    header("Location: drafted-mcqs-details.php?topic_id=" . $topic_id . "&err=delete_failed");
    exit;
}
<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

// POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: updatable-mcqs.php");
    exit;
}

csrf_verify();

// Validate inputs
$e = $_POST['e'] ?? '';
$q = $_POST['q'] ?? '';

if (!in_array($e, ['yes', 'no'], true) || !ctype_digit((string)$q)) {
    header("Location: updatable-mcqs.php?err=bad_request");
    exit;
}

$qid = (int)$q;

// Prepared update
$stmt = mysqli_prepare($conn, "UPDATE questions SET need_upgrade = ? WHERE id = ? LIMIT 1");
if (!$stmt) {
    header("Location: updatable-mcqs.php?err=server");
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $e, $qid);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: updatable-mcqs.php" . ($ok ? "" : "?err=update_failed"));
exit;
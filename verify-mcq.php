<?php
    declare(strict_types=1);
    
    require_once "src/db/db_conn.php";
    require_once "src/db/session.php";
    require_once "src/db/privileges.php";

    // Permission gate (tighten this if you want ONLY admin)
    if (($verify_mcq ?? "false") !== "true" || ($type ?? null) !== "admin") {
        header("Location: courses.php");
        exit;
    }

    // POST only
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: courses.php");
        exit;
    }

    // CSRF
    csrf_verify();

    // Validate inputs
    $mcq_id = filter_input(INPUT_POST, 'mcq_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? null;

    // Only allow expected values (your DB stores 'true'/'false' strings)
    if ($mcq_id === false || $mcq_id === null) {
        header("Location: courses.php");
        exit;
    }
    if ($status !== 'true' && $status !== 'false') {
        header("Location: courses.php");
        exit;
    }

    // Find topic_id (also ensures MCQ exists)
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

    // Update verified safely
    $stmtU = mysqli_prepare($conn, "UPDATE mcqs SET verified = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmtU, "si", $status, $mcq_id);

    if (mysqli_stmt_execute($stmtU)) {
        // Redirect (no JS, no echo, no ob_start hacks)
        header("Location: unverified-questions.php?topic_id=" . $topic_id . "&ok=verified");
        exit;
    }

    // Fail safe
    header("Location: unverified-questions.php?topic_id=" . $topic_id . "&err=verify_failed");
    exit;

?>
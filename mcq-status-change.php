<?php

    require_once "src/db/db_conn.php";
    require_once "src/db/session.php";
    require_once "src/db/privileges.php";

    // Permission check (tighten if you want only admin)
    if (($view_mcq ?? "false") === "false") {
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

    // Only allow expected statuses
    if ($mcq_id === false || $mcq_id === null) {
        header("Location: courses.php");
        exit;
    }
    if ($status !== 'live' && $status !== 'draft') {
        header("Location: courses.php");
        exit;
    }

    // Fetch topic_id (ensures mcq exists)
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

    // Update status safely
    $stmtU = mysqli_prepare($conn, "UPDATE mcqs SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmtU, "si", $status, $mcq_id);

    if (!mysqli_stmt_execute($stmtU)) {
        // fail safe redirect
        header("Location: drafted-mcqs-details.php?topic_id=" . $topic_id . "&err=status_failed");
        exit;
    }

    // Redirect based on status
    if ($status === "live") {
        header("Location: drafted-mcqs-details.php?topic_id=" . $topic_id . "&ok=made_live");
        exit;
    }

    header("Location: topic-details.php?topic_id=" . $topic_id . "&ok=made_draft");
    exit;
?>
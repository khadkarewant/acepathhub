<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";
require_once __DIR__ . "/src/db/privileges.php";

// allow admin + super_admin (match UI)
if (!isset($type) || !in_array($type, ['admin', 'super_admin'], true)) {
    header("Location: home.php");
    exit;
}

// POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: courses.php");
    exit;
}

csrf_verify();

$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$status    = isset($_POST['status']) ? trim((string)($_POST['status'] ?? '')) : '';

if ($course_id <= 0) {
    header("Location: courses.php");
    exit;
}

// allowlist status
$allowed_status = ['live', 'draft'];
if (!in_array($status, $allowed_status, true)) {
    header("Location: courses.php");
    exit;
}

$stmt = $conn->prepare("UPDATE `courses` SET `status` = ? WHERE `id` = ? LIMIT 1");
if (!$stmt) {
    header("Location: courses.php?err=stmt");
    exit;
}

$stmt->bind_param("si", $status, $course_id);

if (!$stmt->execute()) {
    $stmt->close();
    header("Location: courses.php?err=exec");
    exit;
}

$stmt->close();

header("Location: courses.php");
exit;
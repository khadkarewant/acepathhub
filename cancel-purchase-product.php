<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";
require_once __DIR__ . "/src/db/privileges.php";

if (($type ?? null) !== "admin") {
    header("Location: home.php");
    exit;
}

// POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

csrf_verify();

$purchase_id = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
$student_id  = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

if ($purchase_id <= 0) {
    header("Location: users.php");
    exit;
}

// If student_id wasn't posted, derive it (keeps endpoint usable from older callers)
if ($student_id <= 0) {
    $stmtS = $conn->prepare("SELECT `user_id` FROM `purchased_products` WHERE `id` = ? LIMIT 1");
    if (!$stmtS) {
        header("Location: users.php?err=stmt");
        exit;
    }
    $stmtS->bind_param("i", $purchase_id);
    $stmtS->execute();
    $res = $stmtS->get_result();

    if (!$res || $res->num_rows === 0) {
        $stmtS->close();
        header("Location: users.php");
        exit;
    }

    $row = $res->fetch_assoc();
    $student_id = (int)$row['user_id'];
    $stmtS->close();
}

// Cancel only if it belongs to that student (prevents tampering)
$stmtU = $conn->prepare(
    "UPDATE `purchased_products`
     SET `status` = 'inactive'
     WHERE `id` = ? AND `user_id` = ?
     LIMIT 1"
);

if (!$stmtU) {
    header("Location: purchase-history.php?student_id=" . $student_id . "&err=stmt");
    exit;
}

$stmtU->bind_param("ii", $purchase_id, $student_id);

if (!$stmtU->execute()) {
    $stmtU->close();
    header("Location: purchase-history.php?student_id=" . $student_id . "&err=exec");
    exit;
}

$stmtU->close();

// Always go back to purchase history (correct UX)
header("Location: purchase-history.php?student_id=" . $student_id . "&ok=cancelled");
exit;
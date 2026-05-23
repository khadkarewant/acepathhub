<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN);

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

if ($student_id <= 0) {
    $stmt = $conn->prepare("SELECT user_id FROM purchased_products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $purchase_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        header("Location: users.php");
        exit;
    }
    $student_id = (int)$row['user_id'];
}

$stmt = $conn->prepare("
    UPDATE purchased_products
    SET status = 'cancelled'
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $purchase_id, $student_id);
$stmt->execute();
$stmt->close();

header("Location: product-purchase-history.php?student_id=" . $student_id . "&ok=cancelled");
exit;
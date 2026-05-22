<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php"); exit;
}

csrf_verify();

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$status     = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($product_id <= 0) {
    header("Location: products.php"); exit;
}

$allowed_status = ['active', 'inactive'];
if (!in_array($status, $allowed_status, true)) {
    header("Location: product-details.php?product_id=" . $product_id); exit;
}

$stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ? LIMIT 1");
$stmt->bind_param("si", $status, $product_id);
$stmt->execute();
$stmt->close();

header("Location: product-details.php?product_id=" . $product_id . "&ok=1"); exit;
?>
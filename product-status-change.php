<?php
    declare(strict_types=1);

    require_once __DIR__ . "/src/db/db_conn.php";
    require_once __DIR__ . "/src/db/session.php";
    require_once __DIR__ . "/src/db/privileges.php";

    // Admin-only
    if (!isset($type) || $type !== "admin") {
        header("Location: home.php");
        exit;
    }

    // POST-only
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: products.php"); 
        exit;
    }

    csrf_verify();

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($product_id <= 0) {
        header("Location: products.php"); 
        exit;
    }

    // Allowlist status
    $allowed_status = ['live', 'draft'];
    if (!in_array($status, $allowed_status, true)) {
        header("Location: product-details.php?product_id=" . $product_id);
        exit;
    }

    // Prepared statement (no SQL interpolation)
    $stmt = $conn->prepare("UPDATE `products` SET `status` = ? WHERE `id` = ? LIMIT 1");
    if (!$stmt) {
        // Fail closed; optionally log mysqli_error($conn) to server logs
        header("Location: product-details.php?product_id=" . $product_id . "&err=stmt");
        exit;
    }

    $stmt->bind_param("si", $status, $product_id);

    if (!$stmt->execute()) {
        $stmt->close();
        header("Location: product-details.php?product_id=" . $product_id . "&err=exec");
        exit;
    }

    $stmt->close();

    header("Location: product-details.php?product_id=" . $product_id);
    exit;
?>
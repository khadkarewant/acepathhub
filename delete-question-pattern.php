<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

if ($type !== "admin") {
    header("Location: home.php"); exit;
}

// POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php"); exit;
}

csrf_verify();

$pattern_id = isset($_POST['pattern_id']) ? (int)$_POST['pattern_id'] : 0;
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($pattern_id <= 0 || $product_id <= 0) {
    header("Location: products.php"); exit;
}

// Optional: delete child rows first to avoid orphans (recommended)
mysqli_begin_transaction($conn);

try {
    // delete topics mapped to this pattern
    mysqli_query($conn, "DELETE FROM `question_topics` WHERE `pattern_id` = $pattern_id");

    // delete the pattern
    $delete_pattern = mysqli_query($conn, "DELETE FROM `question_patterns` WHERE `id` = $pattern_id");

    if (!$delete_pattern) {
        throw new Exception("Delete failed");
    }

    mysqli_commit($conn);
    header("Location: product-details.php?product_id=" . $product_id); exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    header("Location: product-details.php?product_id=" . $product_id); exit;
}
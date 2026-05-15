<?php
require_once __DIR__ . '/../db/db_conn.php';
require_once __DIR__ . '/../db/session.php';
require_once __DIR__ . '/../db/privileges.php';

if ($type !== 'admin') {
    http_response_code(403);
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');

if (!isset($_GET['txn_no']) || trim($_GET['txn_no']) === '') {
    exit;
}

$txn_no = trim($_GET['txn_no']);

$stmt = mysqli_prepare($conn,
    "SELECT id FROM purchased_products WHERE txn_no = ? LIMIT 1"
);
if (!$stmt) {
    http_response_code(500);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $txn_no);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

echo mysqli_stmt_num_rows($stmt) > 0 ? 'Status 200' : '';

mysqli_stmt_close($stmt);
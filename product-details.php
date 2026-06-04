<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN, ROLE_STUDENT);

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}

$stmt = mysqli_prepare($conn,
    "SELECT id, name, product_type, exam_body_id, description,
            duration_minutes, total_questions, total_marks, sets,
            price, price_1m, price_3m, price_6m, price_12m, status
     FROM products
     WHERE id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$res     = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: products.php");
    exit;
}

// Fetch subjects under this product's exam body
$stmt = mysqli_prepare($conn,
    "SELECT id, name, syllabus_path FROM subjects WHERE exam_body_id = ? ORDER BY name ASC"
);
mysqli_stmt_bind_param($stmt, "i", $product['exam_body_id']);
mysqli_stmt_execute($stmt);
$res      = mysqli_stmt_get_result($stmt);
$subjects = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (has_role(ROLE_STUDENT) && $product['status'] !== 'active') {
    header("Location: products.php");
    exit;
}

$product_type = $product['product_type'];
$ok = isset($_GET['ok']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> — Details</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid p-3">

    <div class="qs-list-header mb-3">
        <div class="qs-list-title"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="mb-3">
        <a href="products.php" class="btn-qs-sm">← Back</a>
    </div>

    <?php if ($ok): ?>
        <div class="alert alert-success alert-dismissible">Saved. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="table-responsive mb-3">
    <table class="table" style="max-width:600px;">
        <tbody>
            <tr><th style="width:160px;">Type</th><td><?= ucfirst(str_replace('_', ' ', $product_type)) ?></td></tr>
                <?php if ($product_type !== 'practice'): ?>
                <tr><th>Duration</th><td><?= $product['duration_minutes'] ?> minutes</td></tr>
                <tr><th>Total Questions</th><td><?= $product['total_questions'] ?></td></tr>
                <tr><th>Total Marks</th><td><?= $product['total_marks'] ?></td></tr>
                <?php endif; ?>
                <?php if ($product_type === 'mock'): ?>
                <tr><th>Sets</th><td><?= $product['sets'] ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['description'])): ?>
                <tr><th>Description</th><td><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endif; ?>
                <tr>
                    <th>Price</th>
                    <td>
                        <?php if ($product_type === 'practice'): ?>
                            <table class="table table-sm mb-0" style="width:auto;">
                                <tbody>
                                    <tr><td>1 Month</td><td>₦<?= number_format((float)$product['price_1m'], 2) ?></td></tr>
                                    <tr><td>3 Months</td><td>₦<?= number_format((float)$product['price_3m'], 2) ?></td></tr>
                                    <tr><td>6 Months</td><td>₦<?= number_format((float)$product['price_6m'], 2) ?></td></tr>
                                    <tr><td>12 Months</td><td>₦<?= number_format((float)$product['price_12m'], 2) ?></td></tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            ₦<?= number_format((float)$product['price'], 2) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge <?= $product['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($product['status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                <th>Subjects</th>
                <td>
                    <?php if (!empty($subjects)): ?>
                        <div class="d-flex flex-column gap-1">
                            <?php foreach ($subjects as $s): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($s['syllabus_path']) && file_exists($s['syllabus_path'])): ?>
                                        <a href="<?= htmlspecialchars($s['syllabus_path'], ENT_QUOTES, 'UTF-8') ?>"
                                        target="_blank" class="btn-qs-sm" style="font-size:0.75rem;padding:2px 8px;">
                                            Syllabus
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    
    <div class="mb-4 d-flex gap-2 flex-wrap">
        <?php if (has_role(ROLE_STUDENT)): ?>
            <a href="https://wa.me/2348169321558?text=<?= rawurlencode('I want to purchase: ' . $product['name'] . '. My username is: ' . $user['username']) ?>"
               target="_blank" class="btn-qs-gold">Purchase</a>
        <?php endif; ?>

        <?php if (has_role(ROLE_ADMIN)): ?>
            <a href="product-edit.php?product_id=<?= $product_id ?>" class="btn-qs-sm">Edit</a>
            <?php if ($product['status'] === 'inactive'): ?>
                <form method="POST" action="product-status-change.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn-qs-gold">Make Active</button>
                </form>
            <?php else: ?>
                <form method="POST" action="product-status-change.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="status" value="inactive">
                    <button type="submit" class="btn-qs-sm">Make Inactive</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
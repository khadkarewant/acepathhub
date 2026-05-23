<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN, ROLE_STUDENT);

if (has_role(ROLE_ADMIN)) {
    $stmt = $conn->prepare("
        SELECT id, name, product_type, duration_minutes, total_questions,
               sets, price, status
        FROM products
        ORDER BY product_type ASC, name ASC
    ");
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $stmt = $conn->prepare("
        SELECT id, name, product_type, duration_minutes, total_questions,
               sets, price
        FROM products
        WHERE status = 'active'
        ORDER BY product_type ASC, name ASC
    ");
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $mock_products      = array_filter($products, fn($p) => $p['product_type'] === 'mock');
    $practice_products  = array_filter($products, fn($p) => $p['product_type'] === 'practice');
    $past_paper_products = array_filter($products, fn($p) => $p['product_type'] === 'past_paper');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid p-3">

<?php if (has_role(ROLE_ADMIN)): ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Products</h4>
        <a href="product-add.php" class="btn btn-sm" style="background:var(--accent);color:#000;">Add Product</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="datatable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Questions</th>
                    <th>Sets</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $p['product_type'])) ?></td>
                    <td><?= $p['duration_minutes'] ?> min</td>
                    <td><?= $p['total_questions'] ?></td>
                    <td><?= $p['product_type'] === 'mock' ? $p['sets'] : '—' ?></td>
                    <td>₦<?= number_format((float)$p['price'], 2) ?></td>
                    <td>
                        <span class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($p['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="product-details.php?product_id=<?= $p['id'] ?>" class="btn btn-sm btn-dark">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>

    <?php if (!empty($mock_products)): ?>
        <h5 class="mb-3" style="color:var(--accent);">Mock Exams</h5>
        <div class="row mb-4">
        <?php foreach ($mock_products as $p): ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100" style="background:#13131a;border:1px solid #2a2a3a;">
                    <div class="card-body">
                        <h6 class="card-title" style="color:var(--accent);">
                            <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h6>
                        <p class="mb-1 small text-muted">
                            <?= $p['duration_minutes'] ?> min &bull;
                            <?= $p['total_questions'] ?> questions &bull;
                            <?= $p['sets'] ?> set<?= $p['sets'] > 1 ? 's' : '' ?>
                        </p>
                        <p class="mb-3" style="color:var(--accent);font-weight:600;">
                            ₦<?= number_format((float)$p['price'], 2) ?>
                        </p>
                        <a href="product-details.php?product_id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-secondary me-1">Details</a>
                        <a href="https://wa.me/2348169321558?text=<?= rawurlencode('I want to purchase: ' . $p['name'] . '. My username is: ' . $username) ?>"
                           target="_blank"
                           class="btn btn-sm"
                           style="background:var(--accent);color:#000;">Purchase</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($practice_products)): ?>
        <h5 class="mb-3" style="color:var(--accent);">Practice</h5>
        <div class="row">
        <?php foreach ($practice_products as $p): ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100" style="background:#13131a;border:1px solid #2a2a3a;">
                    <div class="card-body">
                        <h6 class="card-title" style="color:var(--accent);">
                            <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h6>
                        <p class="mb-1 small text-muted">
                            <?= $p['duration_minutes'] ?> min &bull;
                            <?= $p['total_questions'] ?> questions
                        </p>
                        <p class="mb-3" style="color:var(--accent);font-weight:600;">
                            ₦<?= number_format((float)$p['price'], 2) ?>
                        </p>
                        <a href="product-details.php?product_id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-secondary me-1">Details</a>
                        <a href="https://wa.me/2348169321558?text=<?= rawurlencode('I want to purchase: ' . $p['name'] . '. My username is: ' . $username) ?>"
                           target="_blank"
                           class="btn btn-sm"
                           style="background:var(--accent);color:#000;">Purchase</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($past_paper_products)): ?>
        <h5 class="mb-3" style="color:var(--accent);">Past Papers</h5>
        <div class="row">
        <?php foreach ($past_paper_products as $p): ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100" style="background:#13131a;border:1px solid #2a2a3a;">
                    <div class="card-body">
                        <h6 class="card-title" style="color:var(--accent);">
                            <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h6>
                        <p class="mb-1 small text-muted">
                            <?= $p['duration_minutes'] ?> min &bull;
                            <?= $p['total_questions'] ?> questions
                        </p>
                        <p class="mb-3" style="color:var(--accent);font-weight:600;">
                            ₦<?= number_format((float)$p['price'], 2) ?>
                        </p>
                        <a href="product-details.php?product_id=<?= $p['id'] ?>"
                        class="btn btn-sm btn-outline-secondary me-1">Details</a>
                        <a href="https://wa.me/2348169321558?text=<?= rawurlencode('I want to purchase: ' . $p['name'] . '. My username is: ' . $username) ?>"
                        target="_blank"
                        class="btn btn-sm"
                        style="background:var(--accent);color:#000;">Purchase</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <p class="text-muted">No products available at the moment.</p>
    <?php endif; ?>

<?php endif; ?>

</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
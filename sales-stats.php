<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

$filter = $_GET['filter'] ?? 'all';
$allowed_filters = ['all', 'month', 'year'];
if (!in_array($filter, $allowed_filters)) {
    $filter = 'all';
}

$date_condition = '';
if ($filter === 'month') {
    $date_condition = "AND pp.purchased_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
} elseif ($filter === 'year') {
    $date_condition = "AND pp.purchased_on >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
}

$sql = "
    SELECT
        p.id,
        p.name,
        p.product_type,
        p.sets,
        pp.amount,
        COUNT(pp.id)                                              AS total_sales,
        SUM(pp.sets_remaining)                                    AS remaining_sets,
        SUM(pp.amount)                                            AS revenue,
        SUM(CASE WHEN pp.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM products p
    LEFT JOIN purchased_products pp ON pp.product_id = p.id
    WHERE p.name NOT LIKE '%Demo%'
    $date_condition
    GROUP BY p.id, p.name, p.product_type, p.sets, pp.amount
    ORDER BY p.id, pp.amount
";

$result = mysqli_query($conn, $sql);
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Stats</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container-fluid py-4">

    <div class="qs-list-header mb-3">
        <span class="qs-list-title">Sales Stats</span>
    </div>

    <div class="mb-3 d-flex gap-2">
        <a href="?filter=all"   class="btn btn-sm <?= $filter === 'all'   ? 'btn-warning' : 'btn-outline-secondary' ?>">All Time</a>
        <a href="?filter=month" class="btn btn-sm <?= $filter === 'month' ? 'btn-warning' : 'btn-outline-secondary' ?>">This Month</a>
        <a href="?filter=year"  class="btn btn-sm <?= $filter === 'year'  ? 'btn-warning' : 'btn-outline-secondary' ?>">This Year</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="datatable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Total Sales</th>
                    <th>Cancelled</th>
                    <th>Sets Issued</th>
                    <th>Sets Remaining</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" class="text-center qs-empty">No sales data found.</td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($rows as $row): ?>
                        <?php
                            $active_sales   = (int)$row['total_sales'] - (int)$row['cancelled'];
                            $sets_issued    = $row['product_type'] === 'mock'
                                              ? (int)$row['sets'] * $active_sales
                                              : '—';
                            $sets_remaining = $row['product_type'] === 'mock'
                                              ? (int)($row['remaining_sets'] ?? 0)
                                              : '—';
                            $price_display  = $row['amount'] !== null
                                              ? '₦' . number_format((float)$row['amount'], 2)
                                              : '—';
                            $revenue_display = $row['revenue'] !== null
                                              ? '₦' . number_format((float)$row['revenue'], 2)
                                              : '—';
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><span class="badge-meta"><?= htmlspecialchars($row['product_type']) ?></span></td>
                            <td><?= $price_display ?></td>
                            <td><?= (int)$row['total_sales'] ?></td>
                            <td><?= (int)$row['cancelled'] ?></td>
                            <td><?= $sets_issued ?></td>
                            <td><?= $sets_remaining ?></td>
                            <td><?= $revenue_display ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include "inc/footer.php"; ?>
</body>
</html>
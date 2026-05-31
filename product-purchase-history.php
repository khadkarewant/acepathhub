<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN, ROLE_STUDENT);

if (has_role(ROLE_ADMIN)) {
    $target_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    if ($target_id <= 0) {
        header("Location: users.php");
        exit;
    }
} else {
    $target_id = $user_id;
}

// Fetch target user
$stmt = mysqli_prepare($conn,
    "SELECT user_id, first_name, middle_name, last_name, username
     FROM users
     WHERE user_id = ? AND role = ?
     LIMIT 1"
);
$role_student = ROLE_STUDENT;
mysqli_stmt_bind_param($stmt, 'ii', $target_id, $role_student);
mysqli_stmt_execute($stmt);
$res    = mysqli_stmt_get_result($stmt);
$target = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$target) {
    header("Location: users.php");
    exit;
}

// Fetch purchase history — single JOIN, no N+1
$stmt = mysqli_prepare($conn,
    "SELECT pp.id, pp.amount, pp.sets_remaining, pp.expires_at,
            pp.txn_no, pp.txn_note, pp.txn_mode, pp.mobile,
            pp.status, pp.purchased_on,
            p.name AS product_name, p.product_type,
            u.username AS created_by_username
     FROM purchased_products pp
     INNER JOIN products p ON p.id = pp.product_id
     INNER JOIN users u ON u.user_id = pp.created_by
     WHERE pp.user_id = ?
     ORDER BY pp.purchased_on DESC, pp.id DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $target_id);
mysqli_stmt_execute($stmt);
$res       = mysqli_stmt_get_result($stmt);
$purchases = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$target_name = trim(
    $target['first_name'] . ' ' .
    ($target['middle_name'] ? $target['middle_name'] . ' ' : '') .
    $target['last_name']
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase History</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid p-3">

    <div class="mb-3">
        <?php if (has_role(ROLE_ADMIN)): ?>
            <a href="user-details.php?user_id=<?= $target_id ?>" class="btn-qs-sm">← Back</a>
        <?php else: ?>
            <a href="product-mine.php" class="btn-qs-sm">← Back</a>
        <?php endif; ?>
    </div>

    <h4 class="mb-1">Purchase History</h4>
    <p class="text-muted mb-3">
        <?= htmlspecialchars($target_name, ENT_QUOTES, 'UTF-8') ?>
        (@<?= htmlspecialchars($target['username'], ENT_QUOTES, 'UTF-8') ?>)
    </p>

    <?php if (isset($_GET['ok']) && $_GET['ok'] === 'cancelled'): ?>
        <div class="alert alert-success">Purchase cancelled successfully.</div>
    <?php endif; ?>

    <?php if (empty($purchases)): ?>
        <p class="text-muted">No purchase history found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" id="datatable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Mode</th>
                    <th>TXN No.</th>
                    <th>Note</th>
                    <th>Access</th>
                    <th>Status</th>
                    <th>Purchased On</th>
                    <?php if (has_role(ROLE_ADMIN)): ?>
                        <th>Assigned By</th>
                        <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($purchases as $pp): ?>
                <tr>
                    <td><?= $pp['id'] ?></td>
                    <td><?= htmlspecialchars($pp['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $pp['product_type'])) ?></td>
                    <td>₦<?= number_format((float)$pp['amount'], 2) ?></td>
                    <td><?= ucfirst($pp['txn_mode']) ?></td>
                    <td><?= $pp['txn_no'] ? htmlspecialchars($pp['txn_no'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td><?= $pp['txn_note'] ? htmlspecialchars($pp['txn_note'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td>
                        <?php if ($pp['product_type'] === 'mock'): ?>
                            <?= $pp['sets_remaining'] ?> set<?= $pp['sets_remaining'] != 1 ? 's' : '' ?> left
                        <?php elseif ($pp['product_type'] === 'practice'): ?>
                            <?= $pp['expires_at'] ? 'Expires ' . $pp['expires_at'] : '—' ?>
                        <?php else: ?>
                            Unlimited
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $pp['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($pp['status']) ?>
                        </span>
                    </td>
                    <td><?= $pp['purchased_on'] ?></td>
                    <?php if (has_role(ROLE_ADMIN)): ?>
                        <td><?= htmlspecialchars($pp['created_by_username'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($pp['status'] === 'active'): ?>
                                <form method="POST" action="product-purchase-cancel.php"
                                      onsubmit="return confirm('Cancel this purchase?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="purchase_id" value="<?= $pp['id'] ?>">
                                    <input type="hidden" name="student_id" value="<?= $target_id ?>">
                                    <button type="submit" class="btn-qs-danger">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
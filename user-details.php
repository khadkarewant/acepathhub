<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

if (!isset($_GET['user_id']) || !ctype_digit($_GET['user_id'])) {
    header('Location: users.php');
    exit;
}
$target_user_id = (int)$_GET['user_id'];

$role_labels = [
    ROLE_ADMIN      => 'Admin',
    ROLE_STUDENT    => 'Student',
    ROLE_DATA_ENTRY => 'Data Entry',
];

$stmt = mysqli_prepare($conn,
    'SELECT user_id, first_name, middle_name, last_name, username,
            email, phone, country, city, postal_code, gender, dob,
            role, status, is_blocked, referral_code, referral_by,
            registered_on, last_login
     FROM users
     WHERE user_id = ?
     LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $target_user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$row) {
    header('Location: users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <a href="users.php" class="btn-qs-sm">← Back</a>
        <div class="qs-list-title">
            <?= htmlspecialchars(trim(
                $row['first_name'] . ' ' .
                ($row['middle_name'] ? $row['middle_name'] . ' ' : '') .
                $row['last_name']
            )) ?>
        </div>
        <span class="badge <?= $row['is_blocked'] ? 'badge-unverified' : 'badge-published' ?>">
            <?= $row['is_blocked'] ? 'Blocked' : htmlspecialchars($row['status']) ?>
        </span>
    </div>

    <table class="table">
        <tbody>
            <tr><th>User ID</th>      <td><?= $row['user_id'] ?></td></tr>
            <tr><th>Username</th>     <td><?= htmlspecialchars($row['username']) ?></td></tr>
            <tr><th>Email</th>        <td><?= htmlspecialchars($row['email']) ?></td></tr>
            <tr><th>Phone</th>        <td><?= htmlspecialchars($row['phone']) ?></td></tr>
            <tr><th>Role</th>         <td><?= $role_labels[$row['role']] ?? 'Unknown' ?></td></tr>
            <tr><th>Gender</th>       <td><?= htmlspecialchars($row['gender'] ?? '—') ?></td></tr>
            <tr><th>DOB</th>          <td><?= htmlspecialchars($row['dob'] ?? '—') ?></td></tr>
            <tr><th>Country</th>      <td><?= htmlspecialchars($row['country'] ?? '—') ?></td></tr>
            <tr><th>City</th>         <td><?= htmlspecialchars($row['city'] ?? '—') ?></td></tr>
            <tr><th>Postal Code</th>  <td><?= htmlspecialchars($row['postal_code'] ?? '—') ?></td></tr>
            <tr><th>Referral Code</th><td><?= htmlspecialchars($row['referral_code']) ?></td></tr>
            <tr><th>Referral By</th>  <td><?= htmlspecialchars($row['referral_by'] ?? '—') ?></td></tr>
            <tr><th>Registered</th>   <td><?= htmlspecialchars($row['registered_on']) ?></td></tr>
            <tr><th>Last Login</th>   <td><?= htmlspecialchars($row['last_login'] ?? 'Never') ?></td></tr>
        </tbody>
    </table>

    <div class="ud-actions">

        <?php if ($row['role'] !== ROLE_ADMIN): ?>
            <form method="POST" action="user-password-reset.php" style="display:inline;">
                <?= csrf_input() ?>
                <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                <button type="submit" class="btn-qs-sm"
                        onclick="return confirm('Reset password for this user?')">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>
        <?php if ($row['role'] !== ROLE_ADMIN): ?>
            <?php if ($row['is_blocked']): ?>
                <form method="POST" action="user-block.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                    <input type="hidden" name="action" value="unblock">
                    <button type="submit" class="btn-qs-gold"
                            onclick="return confirm('Unblock this user?')">
                        Unblock
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="user-block.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                    <input type="hidden" name="action" value="block">
                    <button type="submit" class="btn-qs-danger"
                            onclick="return confirm('Block this user?')">
                        Block
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($row['role'] === ROLE_STUDENT): ?>
            <a href="product-assign.php?student_id=<?= $target_user_id ?>" class="btn-qs-sm">
                Assign Product
            </a>
            <a href="product-purchase-history.php?student_id=<?= $target_user_id ?>" class="btn-qs-sm">
                Purchase History
            </a>
        <?php endif; ?>

    </div>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
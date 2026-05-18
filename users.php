<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

$role_labels = [
    ROLE_ADMIN      => 'Admin',
    ROLE_STUDENT    => 'Student',
    ROLE_DATA_ENTRY => 'Data Entry',
];

// Fetch all users before header
$stmt = mysqli_prepare($conn,
    'SELECT user_id, first_name, middle_name, last_name,
            username, role, phone, status, is_blocked
     FROM users
     ORDER BY user_id DESC');
mysqli_stmt_execute($stmt);
$res   = mysqli_stmt_get_result($stmt);
$users = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title">Users</div>
        <a href="add-user.php" class="btn-qs-gold">Add User</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td>
                            <?= htmlspecialchars(trim(
                                $u['first_name'] . ' ' .
                                ($u['middle_name'] ? $u['middle_name'] . ' ' : '') .
                                $u['last_name']
                            )) ?>
                        </td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= $role_labels[$u['role']] ?? 'Unknown' ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= $u['is_blocked'] ? 'Blocked' : htmlspecialchars($u['status']) ?></td>
                        <td>
                            <a href="user-details.php?user_id=<?= $u['user_id'] ?>"
                               class="btn-qs-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
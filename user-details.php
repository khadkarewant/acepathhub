<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

if ($view_user !== "true") {
    header("Location: home.php");
    exit;
}

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Validate id (reject non-numeric)
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: users.php");
    exit;
}
$target_user_id = (int)$_GET['id'];

// Prepared SELECT (kills SQLi)
$stmt = mysqli_prepare($conn, "SELECT first_name, middle_name, last_name, username, email, phone, country, city, postal_code, gender, dob, type, referral_code, referral_by
                              FROM users
                              WHERE user_id = ?
                              LIMIT 1");
if (!$stmt) {
    header("Location: users.php");
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $target_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    header("Location: users.php");
    exit;
}

// Build display vars (still escape on output)
$user_name      = trim(($row['first_name'] ?? '') . " " . ($row['middle_name'] ?? '') . " " . ($row['last_name'] ?? ''));
$user_username  = $row['username'] ?? '';
$email          = $row['email'] ?? '';
$phone          = $row['phone'] ?? '';
$country        = $row['country'] ?? '';
$city           = $row['city'] ?? '';
$postal_code    = $row['postal_code'] ?? '';
$gender         = $row['gender'] ?? '';
$dob            = $row['dob'] ?? '';
$type           = $row['type'] ?? '';
$referral_code  = $row['referral_code'] ?? '';
$referral_by    = $row['referral_by'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 table-responsive p-2">
            <table class="table">
                <tbody>
                    <tr><th>Name</th><td><?= e($user_name) ?></td></tr>
                    <tr><th>Username</th><td><?= e($user_username) ?></td></tr>
                    <tr><th>Email</th><td><?= e($email) ?></td></tr>
                    <tr><th>Phone</th><td><?= e($phone) ?></td></tr>
                    <tr><th>Country</th><td><?= e($country) ?></td></tr>
                    <tr><th>City</th><td><?= e($city) ?></td></tr>
                    <tr><th>Postal Code</th><td><?= e($postal_code) ?></td></tr>
                    <tr><th>Gender</th><td><?= e($gender) ?></td></tr>
                    <tr><th>DOB</th><td><?= e($dob) ?></td></tr>
                    <tr><th>Type</th><td><?= e($type) ?></td></tr>
                    <tr><th>Referral Code</th><td><?= e($referral_code) ?></td></tr>
                    <tr><th>Referral By</th><td><?= e($referral_by) ?></td></tr>
                </tbody>
            </table>

            <form method="POST" action="reset-user-password.php" style="display:inline;">
                <?= csrf_input(); ?>
                <input type="hidden" name="reset_user_id" value="<?= (int)$target_user_id ?>">
                <button class="btn" style="background:var(--primary);color:white;"
                        onclick="return confirm('Reset password to default for this user?');">
                    Reset Password
                </button>
            </form>

            <?php if ($type === "student"): ?>
                <button onclick="window.location.href='assign-product.php?student_id=<?= (int)$target_user_id ?>'"
                        class="btn text-light bg-success">
                    Assign Product
                </button>

                <button onclick="window.location.href='purchase-history.php?student_id=<?= (int)$target_user_id ?>'"
                        class="btn text-dark bg-warning">
                    Purchase History
                </button>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
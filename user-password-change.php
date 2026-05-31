<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN, ROLE_STUDENT, ROLE_DATA_ENTRY);

$c_error  = '';
$cn_error = '';

if (isset($_POST['submit']) && $_POST['submit'] === 'change_pwd') {
    csrf_verify();

    $c_pwd  = (string)($_POST['c_pwd']  ?? '');
    $n_pwd  = (string)($_POST['n_pwd']  ?? '');
    $cn_pwd = (string)($_POST['cn_pwd'] ?? '');

    if ($c_pwd === '' || $n_pwd === '' || $cn_pwd === '') {
        $c_error = "All fields are required.";
    } elseif ($n_pwd !== $cn_pwd) {
        $cn_error = "New and confirm password don't match.";
    } elseif (strlen($n_pwd) < 8) {
        $cn_error = "Password must be at least 8 characters.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $row  = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row || !password_verify($c_pwd, $row['password'])) {
            $c_error = "Current password is incorrect.";
        } else {
            $new_hash = password_hash($n_pwd, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'si', $new_hash, $user_id);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                // Notification (best-effort)
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO notification (user_id, notification, date, time) VALUES (?, ?, ?, ?)"
                );
                if ($stmt) {
                    $note = "Your password was changed successfully.";
                    $date = date('Y-m-d');
                    $time = date('H:i:s');
                    mysqli_stmt_bind_param($stmt, 'isss', $user_id, $note, $date, $time);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                header("Location: logout.php?msg=" . urlencode("Password changed. Please log in again."));
                exit;
            }

            $c_error = "Update failed. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">

    <div class="qs-list-header">
        <div class="qs-list-title">Change Password</div>
        <a href="profile.php" class="btn-qs-sm">← Back</a>
    </div>

    <form method="POST" action="user-password-change.php" class="ep-form">
        <?= csrf_input() ?>

        <div class="profile-card">

            <div class="ep-field">
                <label class="ep-label">Current Password <span class="ep-required">*</span></label>
                <input type="password" name="c_pwd" class="form-control ep-input" required>
                <?php if ($c_error): ?>
                    <div class="text-danger"><?= htmlspecialchars($c_error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="ep-field">
                <label class="ep-label">New Password <span class="ep-required">*</span></label>
                <input type="password" name="n_pwd" class="form-control ep-input" required minlength="8">
            </div>

            <div class="ep-field">
                <label class="ep-label">Confirm New Password <span class="ep-required">*</span></label>
                <input type="password" name="cn_pwd" class="form-control ep-input" required minlength="8">
                <?php if ($cn_error): ?>
                    <div class="text-danger"><?= htmlspecialchars($cn_error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

        </div>

        <div class="ep-submit mt-3">
            <button type="submit" name="submit" value="change_pwd" class="btn-qs-gold">
                Change Password
            </button>
            <a href="profile.php" class="btn-qs-sm">Cancel</a>
        </div>

    </form>
</div>

<?php include "inc/footer.php"; ?>
</body>
</html>
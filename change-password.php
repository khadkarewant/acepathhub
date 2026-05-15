<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

$c_error = "";
$cn_error = "";

if (isset($_POST["submit"]) && $_POST["submit"] === "change_pwd") {
    csrf_verify();

    $uid   = (int)($user_id ?? 0);
    $c_pwd = (string)($_POST["c_pwd"] ?? '');
    $n_pwd = (string)($_POST["n_pwd"] ?? '');
    $cn_pwd = (string)($_POST["cn_pwd"] ?? '');

    if ($uid <= 0) {
        $c_error = "Unauthorized.";
    } elseif ($c_pwd === '' || $n_pwd === '' || $cn_pwd === '') {
        $c_error = "All fields are required.";
    } elseif ($n_pwd !== $cn_pwd) {
        $cn_error = "New and confirm password doesn't match.";
    } elseif (strlen($n_pwd) < 8) {
        $cn_error = "Password must be at least 8 characters.";
    } else {
        // Fetch current password hash
        $stmt = $conn->prepare("SELECT `password` FROM `users` WHERE `user_id` = ? LIMIT 1");
        if (!$stmt) {
            $c_error = "Service temporarily unavailable.";
        } else {
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->bind_result($hash);
            $found = $stmt->fetch();
            $stmt->close();

            if (!$found || !is_string($hash) || $hash === '' || !password_verify($c_pwd, $hash)) {
                $c_error = "Current password doesn't match.";
            } else {
                $new_hash = password_hash($n_pwd, PASSWORD_DEFAULT);

                // Update password
                $stmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `user_id` = ?");
                if (!$stmt) {
                    $c_error = "Service temporarily unavailable.";
                } else {
                    $stmt->bind_param("si", $new_hash, $uid);
                    $stmt->execute();
                    $stmt->close();

                    // Send notification (best-effort)
                    $stmt = $conn->prepare(
                        "INSERT INTO `notification`(`user_id`,`notification`,`date`,`time`) VALUES (?,?,?,?)"
                    );
                    if ($stmt) {
                        $note = "Password has been changed recently.";
                        $date = date("Y-m-d");
                        $time = date("H:i:s");
                        $stmt->bind_param("isss", $uid, $note, $date, $time);
                        $stmt->execute();
                        $stmt->close();
                    }

                    header("Location: logout.php?msg=" . urlencode("Password changed successfully."));
                    exit;
                }
            }
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
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-5">
            <h2>Change Password</h2>

            <form method="POST" action="change-password.php">
                <?= csrf_input(); ?>

                <label>Current Password:</label>
                <input type="password" name="c_pwd" class="form-control" required/>
                <span class="text-danger" style="font-size:12px;">
                    <i><?= htmlspecialchars($c_error, ENT_QUOTES, 'UTF-8') ?></i>
                </span>
                <br>

                <label>New Password:</label>
                <input type="password" name="n_pwd" class="form-control" required/>
                <span class="text-danger" style="font-size:12px;">
                    <i><?= htmlspecialchars($cn_error, ENT_QUOTES, 'UTF-8') ?></i>
                </span>
                <br>

                <label>Confirm New Password:</label>
                <input type="password" name="cn_pwd" class="form-control" required/>
                <br>

                <button type="submit" name="submit" value="change_pwd"
                        class="btn" style="background:var(--primary);color:white;">
                    Change Password
                </button>
            </form>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
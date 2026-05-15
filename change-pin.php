<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

$c_error = "";
$cn_error = "";
$general_error = "";

if (isset($_POST["submit"]) && $_POST["submit"] === "change_pin") {
    csrf_verify();

    $uid = (int)($user_id ?? 0);

    $account_password = (string)($_POST["c_pwd"] ?? '');
    $new_pin          = (string)($_POST["n_pin"] ?? '');
    $confirm_pin      = (string)($_POST["cn_pin"] ?? '');

    if ($uid <= 0) {
        $general_error = "Unauthorized.";
    } elseif ($account_password === '' || $new_pin === '' || $confirm_pin === '') {
        $general_error = "All fields are required.";
    } elseif (!preg_match('/^\d{4,6}$/', $new_pin)) {
        $cn_error = "PIN must be 4–6 digits.";
    } elseif ($new_pin !== $confirm_pin) {
        $cn_error = "New and confirm PIN doesn't match.";
    } else {
        // Fetch password hash
        $stmt = $conn->prepare("SELECT `password` FROM `users` WHERE `user_id` = ? LIMIT 1");
        if (!$stmt) {
            $general_error = "Service temporarily unavailable.";
        } else {
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->bind_result($pass_hash);
            $found = $stmt->fetch();
            $stmt->close();

            if (!$found || !is_string($pass_hash) || $pass_hash === '') {
                $general_error = "Service temporarily unavailable.";
            } elseif (!password_verify($account_password, $pass_hash)) {
                $c_error = "Current password doesn't match.";
            } else {
                // Update PIN (secure hash; do NOT use md5)
                $pin_hash = password_hash($new_pin, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE `users` SET `pin` = ? WHERE `user_id` = ?");
                if (!$stmt) {
                    $general_error = "Service temporarily unavailable.";
                } else {
                    $stmt->bind_param("si", $pin_hash, $uid);
                    $stmt->execute();
                    $stmt->close();

                    // Notification (best-effort)
                    $stmt = $conn->prepare(
                        "INSERT INTO `notification`(`user_id`,`notification`,`date`,`time`) VALUES (?,?,?,?)"
                    );
                    if ($stmt) {
                        $note = "PIN has been changed recently.";
                        $date = date("Y-m-d");
                        $time = date("H:i:s");
                        $stmt->bind_param("isss", $uid, $note, $date, $time);
                        $stmt->execute();
                        $stmt->close();
                    }

                    header("Location: profile.php?msg=" . urlencode("PIN updated successfully."));
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
    <title>Change PIN</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <h2>Change PIN</h2>

            <?php if ($general_error !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($general_error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="change-pin.php">
                <?= csrf_input(); ?>

                <label>Current Password:</label>
                <input type="password" name="c_pwd" class="form-control" required/>
                <span class="text-danger" style="font-size:12px;">
                    <i><?= htmlspecialchars($c_error, ENT_QUOTES, 'UTF-8') ?></i>
                </span>
                <br>

                <label>New PIN:</label>
                <input type="password" name="n_pin" class="form-control" required/>
                <span class="text-danger" style="font-size:12px;">
                    <i><?= htmlspecialchars($cn_error, ENT_QUOTES, 'UTF-8') ?></i>
                </span>
                <br>

                <label>Confirm New PIN:</label>
                <input type="password" name="cn_pin" class="form-control" required/>
                <br>

                <button type="submit" name="submit" value="change_pin"
                        class="btn" style="background:var(--primary);color:white;">
                    Change PIN
                </button>
            </form>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
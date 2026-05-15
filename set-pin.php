<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

$cn_error = "";
$general_error = "";

if (isset($_POST["submit"]) && $_POST["submit"] === "set_pin") {
    csrf_verify();

    $uid   = (int)($user_id ?? 0);
    $pin   = (string)($_POST["n_pwd"] ?? '');
    $cpin  = (string)($_POST["cn_pwd"] ?? '');

    if ($uid <= 0) {
        $general_error = "Unauthorized.";
    } elseif ($pin === '' || $cpin === '') {
        $general_error = "All fields are required.";
    } elseif ($pin !== $cpin) {
        $cn_error = "New and confirm PIN doesn't match.";
    } elseif (!preg_match('/^\d{4,6}$/', $pin)) {
        // choose your rule: 4–6 digits is common
        $cn_error = "PIN must be 4–6 digits.";
    } else {
        // Hash PIN securely (do NOT use md5)
        $pin_hash = password_hash($pin, PASSWORD_DEFAULT);

        // Update pin using prepared statement
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
                $note = "Congratulations! You added PIN to your profile.";
                $date = date("Y-m-d");
                $time = date("H:i:s");
                $stmt->bind_param("isss", $uid, $note, $date, $time);
                $stmt->execute();
                $stmt->close();
            }

            header("Location: profile.php?msg=" . urlencode("PIN set successfully."));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SET PIN</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <h2>Set New PIN</h2>

            <?php if ($general_error !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($general_error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="set-pin.php" class="form p-1">
                <?= csrf_input(); ?>

                <label>New PIN:</label>
                <input type="password" name="n_pwd" class="form-control" required/>
                <span class="text-danger" style="font-size:12px;">
                    <i><?= htmlspecialchars($cn_error, ENT_QUOTES, 'UTF-8') ?></i>
                </span>
                <br>

                <label>Confirm New PIN:</label>
                <input type="password" name="cn_pwd" class="form-control" required/>
                <br>

                <button type="submit" name="submit" value="set_pin"
                        class="btn" style="background:var(--primary);color:white;">
                    SET PIN
                </button>
            </form>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
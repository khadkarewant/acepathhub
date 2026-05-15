<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

if (!isset($user_id) || !is_numeric($user_id)) {
    header("Location: login.php");
    exit;
}

$error_phone = "";
$error_email = "";
$errors = [];

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function clean_text(?string $s, int $maxLen): string {
    $s = trim((string)$s);
    $s = preg_replace('/[^\P{C}]+/u', '', $s); // strip control chars
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!isset($_POST['submit']) || $_POST['submit'] !== 'update_contact') {
        header("Location: profile.php");
        exit;
    }

    $new_phone = clean_text($_POST['phone'] ?? '', 20);
    $new_email = clean_text($_POST['email'] ?? '', 120);

    // Validation
    // Phone: reuse your existing regex philosophy (Nepal-friendly). Adjust if your project already has a strict regex elsewhere.
    if ($new_phone === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $new_phone)) {
        $error_phone = "Invalid phone number.";
        $errors[] = $error_phone;
    }

    if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL) || strlen($new_email) > 120) {
        $error_email = "Invalid email address.";
        $errors[] = $error_email;
    }

    if (empty($errors)) {
        // Duplicate email check (exclude current user)
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
        if (!$stmt) {
            header("Location: profile.php?err=server");
            exit;
        }
        mysqli_stmt_bind_param($stmt, "si", $new_email, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $email_exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($email_exists) {
            $error_email = "Email already exist.";
        }

        // Duplicate phone check (exclude current user)
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM users WHERE phone = ? AND user_id <> ? LIMIT 1");
        if (!$stmt) {
            header("Location: profile.php?err=server");
            exit;
        }
        mysqli_stmt_bind_param($stmt, "si", $new_phone, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $phone_exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($phone_exists) {
            $error_phone = "Phone No. already exist.";
        }

        if (!$email_exists && !$phone_exists) {
            // Update (prepared)
            $stmt = mysqli_prepare($conn, "UPDATE users SET phone = ?, email = ? WHERE user_id = ? LIMIT 1");
            if (!$stmt) {
                header("Location: profile.php?err=server");
                exit;
            }
            mysqli_stmt_bind_param($stmt, "ssi", $new_phone, $new_email, $user_id);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                header("Location: profile.php?updated=contact");
                exit;
            }

            header("Location: profile.php?err=update_failed");
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
    <title>Update Contact</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="contact_form shadow p-1">
    <h4><u>Update Contact Details:</u></h4>
    <div style="width:300px;display:inline-block;" class="light p-1">
        <!-- No PHP_SELF -->
        <form class="form p-1 border" action="update-contact.php" method="POST">
            <?= csrf_input(); ?>

            <label>Phone:</label>
            <input type="text" name="phone" value="<?= e($phone ?? '') ?>" class="form-control" required />
            <?php if ($error_phone): ?>
                <i class="text-danger"><?= e($error_phone) ?></i><br>
            <?php endif; ?>

            <label>Email:</label>
            <input type="text" name="email" value="<?= e($email ?? '') ?>" class="form-control" required />
            <?php if ($error_email): ?>
                <i class="text-danger"><?= e($error_email) ?></i><br>
            <?php endif; ?>

            <button type="submit" name="submit" value="update_contact"
                    class="btn" style="background:var(--primary);color:white;">
                Update Contact
            </button>
        </form>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
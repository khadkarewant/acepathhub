<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

if (!isset($user_id) || !is_numeric($user_id)) {
    header("Location: login.php");
    exit;
}

$errors = [];
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function clean_text(?string $s, int $maxLen): string {
    $s = trim((string)$s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = preg_replace('/[^\P{C}]+/u', '', $s); // strip control chars
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!isset($_POST['submit']) || $_POST['submit'] !== 'update_address') {
        header("Location: profile.php");
        exit;
    }

    $new_country = clean_text($_POST['country'] ?? '', 50);
    $new_city = clean_text($_POST['city'] ?? '', 80);
    $new_postal_code = clean_text($_POST['postal_code'] ?? '', 20);

    // Allowlist countries (match your UI options)
    $allowed_countries = ['Nepal', 'India'];
    if (!in_array($new_country, $allowed_countries, true)) {
        $errors[] = "Invalid country selection.";
    }

    // City: basic sanity
    if ($new_city === '' || mb_strlen($new_city, 'UTF-8') < 2) {
        $errors[] = "Invalid city.";
    }

    // Postal code: keep it flexible (Nepal varies). Allow digits/letters/space/hyphen.
    if ($new_postal_code === '' || !preg_match('/^[0-9A-Za-z\- ]{2,20}$/', $new_postal_code)) {
        $errors[] = "Invalid postal code.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET country = ?, city = ?, postal_code = ? WHERE user_id = ? LIMIT 1");
        if (!$stmt) {
            header("Location: profile.php?err=server");
            exit;
        }

        mysqli_stmt_bind_param($stmt, "sssi", $new_country, $new_city, $new_postal_code, $user_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            header("Location: profile.php?updated=address");
            exit;
        }

        header("Location: profile.php?err=update_failed");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Address</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="contact_form shadow p-1">
    <h4><u>Update Address:</u></h4>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin:10px 0;">
            <ul style="margin:0; padding-left:18px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="width:300px;display:inline-block;" class="light p-1">
        <!-- No PHP_SELF -->
        <form class="form p-1 border" action="update-address.php" method="POST">
            <?= csrf_input(); ?>

            <label>Country:</label>
            <?php $c = $country ?? ''; ?>
            <select name="country" class="form-control" required>
                <option value="">SELECT ONE</option>
                <option value="Nepal" <?= ($c === 'Nepal') ? 'selected' : '' ?>>Nepal</option>
                <option value="India" <?= ($c === 'India') ? 'selected' : '' ?>>India</option>
            </select>

            <label>City:</label>
            <input type="text" name="city" value="<?= e($city ?? '') ?>" class="form-control" required />

            <label>Postal Code:</label>
            <input type="text" name="postal_code" value="<?= e($postal_code ?? '') ?>" class="form-control" required />

            <button type="submit" name="submit" value="update_address"
                    class="btn" style="background:var(--primary);color:white;">
                Update Address
            </button>
        </form>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
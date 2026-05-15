<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";

// IMPORTANT: do NOT include public_bootstrap.php here since session.php is already included.
require_once "src/security/csrf.php";

// Require login (session.php likely sets $user_id; still guard it)
if (!isset($user_id) || !is_numeric($user_id)) {
    header("Location: login.php");
    exit;
}

function clean_name(?string $s, int $maxLen): string {
    $s = trim((string)$s);
    $s = preg_replace('/\s+/', ' ', $s);              // normalize spaces
    $s = preg_replace('/[^\P{C}]+/u', '', $s);        // strip control chars
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Must be POST-only + CSRF
    csrf_verify();

    // Optional: enforce correct submit intent (avoid random POSTs)
    if (!isset($_POST['submit']) || $_POST['submit'] !== 'update_personal_info') {
        header("Location: profile.php");
        exit;
    }

    $new_first_name  = clean_name($_POST['first_name'] ?? '', 50);
    $new_middle_name = clean_name($_POST['middle_name'] ?? '', 50);
    $new_last_name   = clean_name($_POST['last_name'] ?? '', 50);

    $new_dob = trim((string)($_POST['dob'] ?? ''));
    $new_gender = trim((string)($_POST['gender'] ?? ''));

    // Validation
    if ($new_first_name === '' || mb_strlen($new_first_name, 'UTF-8') < 2) $errors[] = "Invalid first name.";
    if ($new_last_name === '' || mb_strlen($new_last_name, 'UTF-8') < 2) $errors[] = "Invalid last name.";

    // gender allowlist
    $allowed_genders = ['male', 'female', 'other'];
    if (!in_array($new_gender, $allowed_genders, true)) {
        $errors[] = "Invalid gender.";
    }

    // DOB validation: must be valid date and not in the future
    $dob_dt = DateTime::createFromFormat('Y-m-d', $new_dob);
    $dob_ok = $dob_dt && $dob_dt->format('Y-m-d') === $new_dob;
    if (!$dob_ok) {
        $errors[] = "Invalid date of birth.";
    } else {
        $today = new DateTime('today');
        if ($dob_dt > $today) $errors[] = "DOB cannot be in the future.";
    }

    if (empty($errors)) {
        // Prepared statement (no string concatenation)
        $sql = "UPDATE users
                SET first_name = ?, middle_name = ?, last_name = ?, dob = ?, gender = ?
                WHERE user_id = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            // Fail closed without exposing SQL errors
            header("Location: profile.php?err=server");
            exit;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssssi",
            $new_first_name,
            $new_middle_name,
            $new_last_name,
            $new_dob,
            $new_gender,
            $user_id
        );

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            header("Location: profile.php?updated=personal");
            exit;
        }

        header("Location: profile.php?err=update_failed");
        exit;
    }
}

// Escape output (minimal protection even before full XSS sweep)
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Personal Info</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="contact_form shadow p-1">
    <h4><u>Update Personal Info:</u></h4>

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
        <!-- No PHP_SELF. Post to this file directly. -->
        <form class="form p-1 border" action="update-personal-info.php" method="POST">
            <?= csrf_input(); ?>

            <label>First Name:</label>
            <input type="text" name="first_name" value="<?= e($first_name ?? '') ?>" class="form-control" required />

            <label>Middle Name:</label>
            <input type="text" name="middle_name" value="<?= e($middle_name ?? '') ?>" class="form-control" />

            <label>Last Name:</label>
            <input type="text" name="last_name" value="<?= e($last_name ?? '') ?>" class="form-control" required />

            <label>DOB:</label>
            <input type="date" name="dob" value="<?= e($dob ?? '') ?>" class="form-control" required />

            <label>Gender:</label><br>
            <?php $g = $gender ?? ''; ?>
            <label><input type="radio" name="gender" value="male"   <?= ($g === 'male') ? 'checked' : '' ?>> Male</label>
            <label><input type="radio" name="gender" value="female" <?= ($g === 'female') ? 'checked' : '' ?>> Female</label>
            <label><input type="radio" name="gender" value="other"  <?= ($g === 'other') ? 'checked' : '' ?>> Other</label>

            <br><br>
            <button type="submit" name="submit" value="update_personal_info"
                    class="btn" style="background:var(--primary);color:white;">
                Update
            </button>
        </form>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/config/countries.php";
require_role(ROLE_ADMIN, ROLE_STUDENT, ROLE_DATA_ENTRY);

$u = $user;
$errors = [];

function clean_name(?string $s, int $maxLen): string {
    $s = trim((string)$s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = preg_replace('/[^\P{C}]+/u', '', $s);
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

function clean_text(?string $s, int $maxLen): string {
    $s = trim((string)$s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = preg_replace('/[^\P{C}]+/u', '', $s);
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!isset($_POST['submit']) || $_POST['submit'] !== 'update_profile') {
        header("Location: profile.php");
        exit;
    }

    // Sanitize
    $new_first_name  = clean_name($_POST['first_name']  ?? '', 50);
    $new_middle_name = clean_name($_POST['middle_name'] ?? '', 50);
    $new_last_name   = clean_name($_POST['last_name']   ?? '', 50);
    $new_dob         = trim((string)($_POST['dob']      ?? ''));
    $new_gender      = trim((string)($_POST['gender']   ?? ''));
    $new_phone       = clean_text($_POST['phone']       ?? '', 15);
    $new_email       = clean_text($_POST['email']       ?? '', 100);
    $new_country     = clean_text($_POST['country']     ?? '', 60);
    $new_city        = clean_text($_POST['city']        ?? '', 20);
    $new_postal_code = clean_text($_POST['postal_code'] ?? '', 10);

    // Validate personal
    if ($new_first_name === '' || mb_strlen($new_first_name, 'UTF-8') < 2)
        $errors[] = "Invalid first name.";
    if ($new_last_name === '' || mb_strlen($new_last_name, 'UTF-8') < 2)
        $errors[] = "Invalid last name.";

    $allowed_genders = ['male', 'female', 'other'];
    if (!in_array($new_gender, $allowed_genders, true))
        $errors[] = "Invalid gender.";

    $dob_dt = DateTime::createFromFormat('Y-m-d', $new_dob);
    if (!$dob_dt || $dob_dt->format('Y-m-d') !== $new_dob)
        $errors[] = "Invalid date of birth.";
    elseif ($dob_dt > new DateTime('today'))
        $errors[] = "DOB cannot be in the future.";

    // Validate contact
    if ($new_phone === '' || !preg_match('/^[0-9+\-\s]{7,15}$/', $new_phone))
        $errors[] = "Invalid phone number.";
    if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email address.";

    // Validate address
    if (!in_array($new_country, COUNTRIES, true))
        $errors[] = "Invalid country.";
    if ($new_city === '' || mb_strlen($new_city, 'UTF-8') < 2)
        $errors[] = "Invalid city.";
    if ($new_postal_code === '' || !preg_match('/^[0-9A-Za-z\- ]{2,10}$/', $new_postal_code))
        $errors[] = "Invalid postal code.";

    // Duplicate checks
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $new_email, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) $errors[] = "Email already in use.";
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "SELECT 1 FROM users WHERE phone = ? AND user_id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $new_phone, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) $errors[] = "Phone already in use.";
        mysqli_stmt_close($stmt);
    }

    // Update
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn,
            "UPDATE users
             SET first_name = ?, middle_name = ?, last_name = ?,
                 dob = ?, gender = ?, phone = ?, email = ?,
                 country = ?, city = ?, postal_code = ?
             WHERE user_id = ?
             LIMIT 1"
        );
        if (!$stmt) {
            header("Location: profile.php?err=server");
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'ssssssssssi',
            $new_first_name, $new_middle_name, $new_last_name,
            $new_dob, $new_gender, $new_phone, $new_email,
            $new_country, $new_city, $new_postal_code,
            $user_id
        );
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header($ok
            ? "Location: profile.php?updated=1"
            : "Location: profile.php?err=update_failed"
        );
        exit;
    }

    // Re-populate $u with submitted values on error
    $u = array_merge($u, [
        'first_name'  => $new_first_name,
        'middle_name' => $new_middle_name,
        'last_name'   => $new_last_name,
        'dob'         => $new_dob,
        'gender'      => $new_gender,
        'phone'       => $new_phone,
        'email'       => $new_email,
        'country'     => $new_country,
        'city'        => $new_city,
        'postal_code' => $new_postal_code,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">

    <div class="qs-list-header">
        <div class="qs-list-title">Edit Profile</div>
        <a href="profile.php" class="btn-qs-sm">← Back</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="profile-edit.php" method="POST" class="ep-form">
        <?= csrf_input() ?>

        <!-- Personal -->
        <div class="profile-card mb-3">
            <div class="profile-card-title">Personal Details</div>

            <div class="ep-field">
                <label class="ep-label">First Name <span class="ep-required">*</span></label>
                <input type="text" name="first_name"
                       value="<?= htmlspecialchars($u['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" required maxlength="50">
            </div>

            <div class="ep-field">
                <label class="ep-label">Middle Name</label>
                <input type="text" name="middle_name"
                       value="<?= htmlspecialchars((string)($u['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" maxlength="50">
            </div>

            <div class="ep-field">
                <label class="ep-label">Last Name <span class="ep-required">*</span></label>
                <input type="text" name="last_name"
                       value="<?= htmlspecialchars($u['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" required maxlength="50">
            </div>

            <div class="ep-field">
                <label class="ep-label">Date of Birth <span class="ep-required">*</span></label>
                <input type="date" name="dob"
                       value="<?= htmlspecialchars((string)($u['dob'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" required>
            </div>

            <div class="ep-field">
                <label class="ep-label">Gender <span class="ep-required">*</span></label>
                <div class="ep-radio-group">
                    <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label): ?>
                        <label class="ep-radio">
                            <input type="radio" name="gender" value="<?= $val ?>"
                                <?= ($u['gender'] ?? '') === $val ? 'checked' : '' ?> required>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div class="profile-card mb-3">
            <div class="profile-card-title">Contact Details</div>

            <div class="ep-field">
                <label class="ep-label">Phone <span class="ep-required">*</span></label>
                <input type="text" name="phone"
                       value="<?= htmlspecialchars($u['phone'], ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" required maxlength="15">
            </div>

            <div class="ep-field">
                <label class="ep-label">Email <span class="ep-required">*</span></label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" required maxlength="100">
            </div>
        </div>

        <!-- Address -->
        <div class="profile-card mb-3">
            <div class="profile-card-title">Address</div>

            <div class="ep-field">
                <label class="ep-label">Country <span class="ep-required">*</span></label>
                <select name="country" class="form-control ep-input" required>
                    <option value="">— Select Country —</option>
                    <?php foreach (COUNTRIES as $c): ?>
                        <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($u['country'] ?? '') === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ep-field">
                <label class="ep-label">City <span class="ep-required">*</span></label>
                <input type="text" name="city"
                       value="<?= htmlspecialchars((string)($u['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" required maxlength="20">
            </div>

            <div class="ep-field">
                <label class="ep-label">Postal Code <span class="ep-required">*</span></label>
                <input type="text" name="postal_code"
                       value="<?= htmlspecialchars((string)($u['postal_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control ep-input" required maxlength="10">
            </div>
        </div>

        <div class="ep-submit">
            <button type="submit" name="submit" value="update_profile" class="btn-qs-gold">
                Save Changes
            </button>
            <a href="profile.php" class="btn-qs-sm">Cancel</a>
        </div>

    </form>
</div>

<?php include "inc/footer.php"; ?>
</body>
</html>
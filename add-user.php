<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");
require_once "src/security/csrf.php";

//  Admin-only (adjust if your policy differs)
if (!isset($type) || $type !== 'admin') {
    header("Location: home.php");
    exit;
}

$errors = [
    'username' => '',
    'email' => '',
    'phone' => '',
    'password' => '',
    'general' => ''
];

function clean_text($s, $maxLen) {
    $s = trim((string)$s);
    $s = preg_replace('/\s+/', ' ', $s);          // collapse multiple spaces
    $s = preg_replace('/[^\P{C}]+/u', '', $s);    // remove control chars
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

$user_first_name = "";
$user_middle_name = "";
$user_last_name = "";
$user_username = "";
$user_dob = "";
$user_email = "";
$user_phone = "";
$new_user_type = "";

// Allowlist user types
$allowed_types = ['admin', 'teacher', 'agent', 'data_entry', 'biller'];

if (isset($_POST["submit"]) && $_POST["submit"] === "submit") {
    csrf_verify();

    $new_user_type = (string)($_POST['user_type'] ?? '');

    $user_first_name  = clean_text($_POST["first_name"] ?? '', 50);
    $user_middle_name = clean_text($_POST["middle_name"] ?? '', 50);
    $user_last_name   = clean_text($_POST["last_name"] ?? '', 50);

    $user_username = strtolower(clean_text($_POST["username"] ?? '', 20));
    $user_email    = strtolower(clean_text($_POST["email"] ?? '', 120));
    $user_phone    = clean_text($_POST["phone"] ?? '', 20);

    $user_dob = clean_text($_POST["dob"] ?? '', 10); // still validate separately
    
    $password_plain = (string)($_POST["password"] ?? '');
    $user_c_password = (string)($_POST["c_password"] ?? '');

    // --- Basic validation ---
    if (!in_array($new_user_type, $allowed_types, true)) {
        $errors['general'] = "Invalid user type.";
    }

    if ($user_first_name === '' || $user_last_name === '' || $user_username === '' || $user_email === '' || $user_phone === '' || $user_dob === '') {
        $errors['general'] = "All required fields must be filled.";
    }

    // STEP 4: DOB validation (place right here)
    if ($errors['general'] === '' && $user_dob !== '') {
        $dob_dt = DateTime::createFromFormat('Y-m-d', $user_dob);
        $dob_ok = $dob_dt && $dob_dt->format('Y-m-d') === $user_dob;

        if (!$dob_ok) {
            $errors['general'] = "Invalid date of birth.";
        } else {
            $today = new DateTime('today');
            if ($dob_dt > $today) {
                $errors['general'] = "DOB cannot be in the future.";
            }
        }
    }

    if($errors['general'] === ''){
    
        if ($user_username !== '' && !preg_match('/^[a-z0-9_.]{3,20}$/', $user_username)) {
            $errors['username'] = "Username must be 3–20 chars (a-z, 0-9, _ .).";
        }

        if ($user_email !== '' && !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email address.";
        }

        if ($user_phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $user_phone)) {
            $errors['phone'] = "Phone must be 7–15 digits.";
        }

        if ($password_plain === '' || $user_c_password === '') {
            $errors['password'] = "Password fields are required.";
        } elseif ($password_plain !== $user_c_password) {
            $errors['password'] = "Passwords do not match.";
        } elseif (strlen($password_plain) < 8) {
            $errors['password'] = "Password must be at least 8 characters.";
        }
    }

    // --- Uniqueness checks (prepared) ---
    if ($errors['general'] === '' && $errors['username'] === '' && $errors['email'] === '' && $errors['phone'] === '' && $errors['password'] === '') {
        $stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
        if (!$stmt) {
            $errors['general'] = "Service temporarily unavailable.";
        } else {
            $stmt->bind_param("sss", $user_username, $user_email, $user_phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (($row['username'] ?? '') === $user_username) $errors['username'] = "Username already taken.";
                if (($row['email'] ?? '') === $user_email) $errors['email'] = "Email already taken.";
                if (($row['phone'] ?? '') === $user_phone) $errors['phone'] = "Contact already taken.";
            }
            $stmt->close();
        }
    }

    // --- Insert (prepared) ---
    if ($errors['general'] === '' && $errors['username'] === '' && $errors['email'] === '' && $errors['phone'] === '' && $errors['password'] === '') {
        $user_password = password_hash($password_plain, PASSWORD_DEFAULT);
        $date = date("Y-m-d");
        $time = date("H:i:s");
        $referral_code = "none";

        $stmt = $conn->prepare("
            INSERT INTO users
            (first_name, middle_name, last_name, username, email, phone, dob, password, registered_on, type, registered_at, referral_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            $errors['general'] = "Service temporarily unavailable.";
        } else {
            $stmt->bind_param(
                "ssssssssssss",
                $user_first_name,
                $user_middle_name,
                $user_last_name,
                $user_username,
                $user_email,
                $user_phone,
                $user_dob,
                $user_password,
                $date,
                $new_user_type,
                $time,
                $referral_code
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: users.php?msg=" . urlencode("User has been added."));
                exit;
            } else {
                $errNo = $stmt->errno;   // 1062 = duplicate key
                $stmt->close();

                if ($errNo === 1062) {
                    $errors['general'] = "Duplicate detected. Username/Email/Phone must be unique.";
                } else {
                    $errors['general'] = "Failed to add user.";
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
    <title>Add User</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 p-1">
            <h4>Add User</h4>

            <?php if ($errors['general'] !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="add-user.php" class="form p-1" method="POST">
                <?= csrf_input(); ?>

                <div class="row">
                    <div class="col-md-12 p-1">
                        <label>User Type</label>
                        <select name="user_type" class="form-control" required>
                            <option value="" disabled <?= $new_user_type === '' ? 'selected' : '' ?>>SELECT ONE</option>
                            <?php foreach ($allowed_types as $t): ?>
                                <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $new_user_type === $t ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $t)), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 p-1">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user_first_name, ENT_QUOTES, 'UTF-8') ?>" class="form-control" required>
                    </div>

                    <div class="col-md-4 p-1">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?= htmlspecialchars($user_middle_name, ENT_QUOTES, 'UTF-8') ?>" class="form-control">
                    </div>

                    <div class="col-md-4 p-1">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user_last_name, ENT_QUOTES, 'UTF-8') ?>" class="form-control" required>
                    </div>

                    <div class="col-md-6 p-1">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user_username, ENT_QUOTES, 'UTF-8') ?>" class="form-control" required>
                        <div class="text-danger"><?= htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="col-md-6 p-1">
                        <label>Date Of Birth</label>
                        <input type="date" name="dob" value="<?= htmlspecialchars($user_dob, ENT_QUOTES, 'UTF-8') ?>" class="form-control" required>
                    </div>

                    <div class="col-md-6 p-1">
                        <label>Email</label>
                        <input type="text" name="email" value="<?= htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8') ?>" class="form-control" required>
                        <div class="text-danger"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="col-md-6 p-1">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user_phone, ENT_QUOTES, 'UTF-8') ?>" class="form-control" required>
                        <div class="text-danger"><?= htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="col-md-6 p-1">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="col-md-6 p-1">
                        <label>Confirm Password</label>
                        <input type="password" name="c_password" class="form-control" required>
                        <div class="text-danger"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="col-md-12 p-1">
                        <button type="submit" name="submit" value="submit" class="btn" style="background:var(--primary);color:white;">
                            Add User
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
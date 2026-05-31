<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

$allowed_roles = [
    ROLE_DATA_ENTRY => 'Data Entry',
];

function clean_text(string $s, int $maxLen): string {
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = preg_replace('/[^\P{C}]+/u', '', $s);
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

function generate_referral_code(): string {
    return strtoupper(bin2hex(random_bytes(4)));
}

$errors = [
    'general'  => '',
    'username' => '',
    'email'    => '',
    'phone'    => '',
    'password' => '',
];

$fields = [
    'first_name'  => '',
    'middle_name' => '',
    'last_name'   => '',
    'username'    => '',
    'email'       => '',
    'phone'       => '',
    'dob'         => '',
    'role'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fields['first_name']  = clean_text($_POST['first_name']  ?? '', 50);
    $fields['middle_name'] = clean_text($_POST['middle_name'] ?? '', 50);
    $fields['last_name']   = clean_text($_POST['last_name']   ?? '', 50);
    $fields['username']    = strtolower(clean_text($_POST['username'] ?? '', 30));
    $fields['email']       = strtolower(clean_text($_POST['email']    ?? '', 100));
    $fields['phone']       = clean_text($_POST['phone'] ?? '', 15);
    $fields['dob']         = clean_text($_POST['dob']   ?? '', 10);
    $fields['role']        = (int)($_POST['role'] ?? 0);

    $password_plain = (string)($_POST['password']   ?? '');
    $confirm        = (string)($_POST['c_password'] ?? '');

    // Role validation
    if (!array_key_exists($fields['role'], $allowed_roles)) {
        $errors['general'] = 'Invalid role selected.';
    }

    // Required fields
    if ($errors['general'] === '' &&
        ($fields['first_name'] === '' || $fields['last_name'] === '' ||
         $fields['username']   === '' || $fields['email']     === '' ||
         $fields['phone']      === '' || $fields['dob']       === '')) {
        $errors['general'] = 'All required fields must be filled.';
    }

    // DOB validation
    if ($errors['general'] === '') {
        $dob_dt = DateTime::createFromFormat('Y-m-d', $fields['dob']);
        if (!$dob_dt || $dob_dt->format('Y-m-d') !== $fields['dob']) {
            $errors['general'] = 'Invalid date of birth.';
        } elseif ($dob_dt > new DateTime('today')) {
            $errors['general'] = 'DOB cannot be in the future.';
        }
    }

    // Field format validation
    if ($errors['general'] === '') {
        if (!preg_match('/^[a-z0-9_.]{3,30}$/', $fields['username'])) {
            $errors['username'] = 'Username must be 3–30 chars (a-z, 0-9, _ .).';
        }
        if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }
        if (!preg_match('/^[0-9+\-\s]{7,15}$/', $fields['phone'])) {
            $errors['phone'] = 'Phone must be 7–15 digits.';
        }
        if ($password_plain === '' || $confirm === '') {
            $errors['password'] = 'Password fields are required.';
        } elseif ($password_plain !== $confirm) {
            $errors['password'] = 'Passwords do not match.';
        } elseif (strlen($password_plain) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
    }

    // Uniqueness check
    $has_errors = array_filter($errors, fn($e) => $e !== '');
    if (empty($has_errors)) {
        $stmt = mysqli_prepare($conn,
            'SELECT username, email, phone FROM users
             WHERE username = ? OR email = ? OR phone = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'sss',
            $fields['username'], $fields['email'], $fields['phone']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $dup = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);

        if ($dup) {
            if ($dup['username'] === $fields['username']) $errors['username'] = 'Username already taken.';
            if ($dup['email']    === $fields['email'])    $errors['email']    = 'Email already taken.';
            if ($dup['phone']    === $fields['phone'])    $errors['phone']    = 'Phone already taken.';
        }
    }

    // Insert
    $has_errors = array_filter($errors, fn($e) => $e !== '');
    if (empty($has_errors)) {
        $password_hash  = password_hash($password_plain, PASSWORD_DEFAULT);
        $registered_on  = date('Y-m-d');
        $registered_at  = date('H:i:s');
        $referral_code  = generate_referral_code();

        $stmt = mysqli_prepare($conn,
            'INSERT INTO users
             (first_name, middle_name, last_name, username, email, phone, dob,
              password, role, registered_on, registered_at, referral_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'ssssssssisss',
            $fields['first_name'],
            $fields['middle_name'],
            $fields['last_name'],
            $fields['username'],
            $fields['email'],
            $fields['phone'],
            $fields['dob'],
            $password_hash,
            $fields['role'],
            $registered_on,
            $registered_at,
            $referral_code
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header('Location: users.php?ok=1');
            exit;
        }

        $errno = mysqli_stmt_errno($stmt);
        mysqli_stmt_close($stmt);

        if ($errno === 1062) {
            $errors['general'] = 'Duplicate detected. Username/email/phone must be unique.';
        } else {
            $errors['general'] = 'Failed to add user. Try again.';
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
    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title">Add User</div>
    </div>

    <?php if ($errors['general'] !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="user-add.php" class="user-form">
        <?= csrf_input() ?>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="" disabled <?= $fields['role'] === '' ? 'selected' : '' ?>>
                        Select role
                    </option>
                    <?php foreach ($allowed_roles as $val => $label): ?>
                        <option value="<?= $val ?>"
                            <?= $fields['role'] === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control"
                       value="<?= htmlspecialchars($fields['first_name']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-control"
                       value="<?= htmlspecialchars($fields['middle_name']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control"
                       value="<?= htmlspecialchars($fields['last_name']) ?>" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       value="<?= htmlspecialchars($fields['username']) ?>" required>
                <?php if ($errors['username'] !== ''): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['username']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control"
                       value="<?= htmlspecialchars($fields['dob']) ?>" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($fields['email']) ?>" required>
                <?php if ($errors['email'] !== ''): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['email']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= htmlspecialchars($fields['phone']) ?>" required>
                <?php if ($errors['phone'] !== ''): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['phone']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="c_password" class="form-control" required>
                <?php if ($errors['password'] !== ''): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-12">
                <button type="submit" class="btn-qs-gold">Add User</button>
            </div>
        </div>
    </form>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
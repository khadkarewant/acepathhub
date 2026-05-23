<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN);

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($student_id <= 0) {
    header("Location: users.php");
    exit;
}

// Fetch student — must be ROLE_STUDENT
$stmt = $conn->prepare("
    SELECT user_id, first_name, middle_name, last_name, username, phone
    FROM users
    WHERE user_id = ? AND role = ? AND is_blocked = 0
    LIMIT 1
");
$stmt->bind_param("ii", $student_id, ROLE_STUDENT);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: users.php");
    exit;
}

// Fetch active products
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.product_type, p.price, p.sets
    FROM products p
    WHERE p.status = 'active'
    ORDER BY p.product_type ASC, p.name ASC
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$student_name = trim(
    $student['first_name'] . ' ' .
    ($student['middle_name'] ? $student['middle_name'] . ' ' : '') .
    $student['last_name']
);

$err = '';
$txn_no  = '';
$mobile  = $student['phone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit'] ?? '') === 'assign_product') {
    csrf_verify();

    $posted_student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    if ($posted_student_id !== $student_id) {
        header("Location: users.php");
        exit;
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $txn_no     = trim((string)($_POST['txn_no'] ?? ''));
    $txn_mode   = trim((string)($_POST['txn_mode'] ?? ''));
    $mobile     = trim((string)($_POST['mobile'] ?? ''));
    $discount   = isset($_POST['discount']) ? (float)$_POST['discount'] : 0.0;
    $duration   = isset($_POST['duration']) ? (int)$_POST['duration'] : 0; // months, practice only

    $allowed_modes = ['bank', 'free', 'other'];

    if ($product_id <= 0) {
        $err = 'Please select a product.';
    } elseif (!in_array($txn_mode, $allowed_modes, true)) {
        $err = 'Invalid transaction mode.';
    } elseif ($txn_mode !== 'free' && ($txn_no === '' || strlen($txn_no) > 64)) {
        $err = 'Transaction number is required and must be under 64 characters.';
    } elseif (!preg_match('/^[0-9]{7,15}$/', $mobile)) {
        $err = 'Invalid mobile number (7–15 digits).';
    } elseif ($discount < 0) {
        $err = 'Discount cannot be negative.';
    } else {
        // Fetch product to verify it's active and get type/sets/price
        $stmt = $conn->prepare("
            SELECT id, product_type, price, sets
            FROM products
            WHERE id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prod) {
            $err = 'Selected product is not available.';
        } elseif ($prod['product_type'] === 'practice' && !in_array($duration, [1, 3, 6, 12], true)) {
            $err = 'Select a valid duration for practice product.';
        } elseif ($txn_mode !== 'free' && $txn_no !== '') {
            // Duplicate txn_no check
            $stmt = $conn->prepare("SELECT id FROM purchased_products WHERE txn_no = ? LIMIT 1");
            $stmt->bind_param("s", $txn_no);
            $stmt->execute();
            $dup = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($dup) {
                $err = 'Transaction number already exists.';
            }
        }

        if ($err === '') {
            $amount = max(0.0, (float)$prod['price'] - $discount);
            $today  = date('Y-m-d');

            // Compute per-type values
            $sets_remaining = 0;
            $expires_at     = null;

            if ($prod['product_type'] === 'mock') {
                $sets_remaining = (int)$prod['sets'];
            } elseif ($prod['product_type'] === 'practice') {
                $expires_at = date('Y-m-d', strtotime("+{$duration} months"));
            }
            // past_paper: both stay 0/null

            $txn_no_val = $txn_mode === 'free' ? null : ($txn_no === '' ? null : $txn_no);

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("
                    INSERT INTO purchased_products
                        (user_id, product_id, amount, sets_remaining, expires_at,
                         txn_no, txn_mode, mobile, status, purchased_on, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
                ");
                $stmt->bind_param(
                    "iidisssssi",
                    $student_id, $product_id, $amount, $sets_remaining, $expires_at,
                    $txn_no_val, $txn_mode, $mobile, $today, $user_id
                );
                $stmt->execute();
                $stmt->close();

                $note = "A product has been assigned to your account. Check My Products for details.";
                $date = date('Y-m-d');
                $time = date('H:i:s');
                $stmt = $conn->prepare("
                    INSERT INTO notification (user_id, notification, date, time)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $student_id, $note, $date, $time);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                header("Location: user-details.php?user_id=" . $student_id . "&ok=assigned");
                exit;

            } catch (Throwable $e) {
                $conn->rollback();
                $err = 'Failed to assign product. Please try again.';
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
<title>Assign Product</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid p-3">
    <div class="row">
        <div class="col-md-6">

            <div class="mb-3">
                <a href="user-details.php?user_id=<?= $student_id ?>" class="btn btn-sm btn-secondary">
                    &larr; Back to User
                </a>
            </div>

            <h4 class="mb-3">Assign Product</h4>

            <?php if ($err !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST" id="assignForm">
                <?= csrf_input() ?>
                <input type="hidden" name="student_id" value="<?= $student_id ?>">

                <div class="mb-3">
                    <label class="form-label">Student</label>
                    <input type="text" class="form-control" disabled
                           value="<?= htmlspecialchars($student_name . ' (@' . $student['username'] . ')', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-control" required id="productSelect">
                        <option value="" disabled selected>Select Product</option>
                        <?php
                        $type_labels = ['mock' => 'Mock Exam', 'practice' => 'Practice', 'past_paper' => 'Past Paper'];
                        $current_type = '';
                        foreach ($products as $p):
                            if ($p['product_type'] !== $current_type):
                                if ($current_type !== '') echo '</optgroup>';
                                $current_type = $p['product_type'];
                                echo '<optgroup label="' . htmlspecialchars($type_labels[$current_type] ?? $current_type, ENT_QUOTES, 'UTF-8') . '">';
                            endif;
                        ?>
                            <option value="<?= $p['id'] ?>"
                                    data-type="<?= htmlspecialchars($p['product_type'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-price="<?= (float)$p['price'] ?>">
                                <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                                (₦<?= number_format((float)$p['price'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_type !== '') echo '</optgroup>'; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="durationRow">
                    <label class="form-label">Duration</label>
                    <select name="duration" class="form-control">
                        <option value="1">1 Month</option>
                        <option value="3">3 Months</option>
                        <option value="6">6 Months</option>
                        <option value="12">12 Months</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Discount (₦)</label>
                    <input type="number" name="discount" class="form-control" min="0" step="0.01" value="0">
                </div>

                <div class="mb-3">
                    <label class="form-label">Transaction Mode</label><br>
                    <?php foreach (['bank' => 'Bank', 'free' => 'Free', 'other' => 'Other'] as $val => $label): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="txn_mode"
                                   value="<?= $val ?>" id="mode_<?= $val ?>"
                                   <?= $val === 'bank' ? 'checked' : '' ?>
                                   onchange="toggleTxn(this.value)">
                            <label class="form-check-label" for="mode_<?= $val ?>"><?= $label ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mb-3" id="txnRow">
                    <label class="form-label">Transaction No.</label>
                    <input type="text" name="txn_no" class="form-control" maxlength="64"
                           value="<?= htmlspecialchars($txn_no, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="mobile" class="form-control"
                           maxlength="15" pattern="[0-9]{7,15}" required
                           value="<?= htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" name="submit" value="assign_product"
                        class="btn" style="background:var(--accent);color:#000;">
                    Assign Product
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTxn(mode) {
    document.getElementById('txnRow').classList.toggle('d-none', mode === 'free');
}
document.getElementById('productSelect').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const type = opt.dataset.type;
    document.getElementById('durationRow').classList.toggle('d-none', type !== 'practice');
});
</script>

<?php include("inc/footer.php"); ?>
</body>
</html>
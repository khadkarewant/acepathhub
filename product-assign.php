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
$role_student = ROLE_STUDENT;
$stmt = mysqli_prepare($conn,
    "SELECT user_id, first_name, middle_name, last_name, username, phone
     FROM users
     WHERE user_id = ? AND role = ? AND is_blocked = 0
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ii", $student_id, $role_student);
mysqli_stmt_execute($stmt);
$res     = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: users.php");
    exit;
}

// Fetch active products
$stmt = mysqli_prepare($conn,
    "SELECT p.id, p.name, p.product_type, p.price, p.sets, p.exam_body_id
     FROM products p
     WHERE p.status = 'active'
     ORDER BY p.product_type ASC, p.name ASC"
);
mysqli_stmt_execute($stmt);
$res      = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// Fetch all subjects grouped by exam_body_id
$stmt = mysqli_prepare($conn, "SELECT id, name, exam_body_id FROM subjects ORDER BY exam_body_id ASC, name ASC");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$all_subjects = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// Group subjects by exam_body_id for JS
$subjects_by_exam_body = [];
foreach ($all_subjects as $s) {
    $subjects_by_exam_body[(int)$s['exam_body_id']][] = [
        'id'   => (int)$s['id'],
        'name' => $s['name'],
    ];
}

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
    $txn_mode   = trim((string)($_POST['txn_mode'] ?? ''));
    $txn_note = trim((string)(
        $txn_mode === 'bank' ? ($_POST['bank_name'] ?? '') :
    ($txn_mode === 'other' ? ($_POST['other_name'] ?? '') : '')
    ));
    $txn_no     = trim((string)($_POST['txn_no'] ?? ''));
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
        $stmt = mysqli_prepare($conn,
            "SELECT id, product_type, price, price_1m, price_3m, price_6m, price_12m, sets
            FROM products
            WHERE id = ? AND status = 'active'
            LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $prod = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);

        if (!$prod) {
            $err = 'Selected product is not available.';
        } elseif ($prod['product_type'] === 'practice' && !in_array($duration, [1, 3, 6, 12], true)) {
            $err = 'Select a valid duration for practice product.';
        } elseif ($txn_mode !== 'free' && $txn_no !== '') {
            // Duplicate txn_no check
            $stmt = mysqli_prepare($conn, "SELECT id FROM purchased_products WHERE txn_no = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $txn_no);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $dup = mysqli_fetch_assoc($res);
            mysqli_free_result($res);
            mysqli_stmt_close($stmt);
            if ($dup) {
                $err = 'Transaction number already exists.';
            }
        }
        

        if ($err === '') {
            if ($prod['product_type'] === 'practice') {
                $price_col_map = [1 => 'price_1m', 3 => 'price_3m', 6 => 'price_6m', 12 => 'price_12m'];
                $base_price = (float)($prod[$price_col_map[$duration]] ?? 0.0);
            } else {
                $base_price = (float)$prod['price'];
            }
            $amount = max(0.0, $base_price - $discount);
        
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

            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO purchased_products
                        (user_id, product_id, amount, sets_remaining, expires_at, txn_note,
                        txn_no, txn_mode, mobile, status, purchased_on, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmt, "iidissssssi",
                    $student_id, $product_id, $amount, $sets_remaining, $expires_at, $txn_note,
                    $txn_no_val, $txn_mode, $mobile, $today, $user_id
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Insert selected subjects
                $subject_ids = $_POST['subject_ids'] ?? [];
                if (!empty($subject_ids)) {
                    $purchased_id = (int)mysqli_insert_id($conn);
                    $stmt = mysqli_prepare($conn,
                        "INSERT INTO purchased_product_subjects (purchased_id, subject_id) VALUES (?, ?)"
                    );
                    foreach ($subject_ids as $sid) {
                        $sid = (int)$sid;
                        if ($sid > 0) {
                            mysqli_stmt_bind_param($stmt, "ii", $purchased_id, $sid);
                            mysqli_stmt_execute($stmt);
                        }
                    }
                    mysqli_stmt_close($stmt);
                }

                $note = "A product has been assigned to your account. Check My Products for details.";
                $date = date('Y-m-d');
                $time = date('H:i:s');
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO notification (user_id, notification, date, time)
                    VALUES (?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, "isss", $student_id, $note, $date, $time);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);
                header("Location: user-details.php?user_id=" . $student_id . "&ok=assigned");
                exit;

            } catch (Throwable $e) {
                mysqli_rollback($conn);
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
                        <option value="" disabled selected hidden>Select Product</option>
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
                                data-price="<?= (float)$p['price'] ?>"
                                data-exam-body="<?= (int)$p['exam_body_id'] ?>">

                                <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                                <?= $p['product_type'] !== 'practice' ? '(₦' . number_format((float)$p['price'], 2) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_type !== '') echo '</optgroup>'; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="subjectRow">
                    <label class="form-label">Subjects</label>
                    <div id="subjectCheckboxes" class="d-flex flex-wrap gap-2">
                        <!-- populated by JS -->
                    </div>
                    <small class="text-muted">Select the subjects for this student.</small>
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

                <div class="mb-3 d-none" id="bankNameRow">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($_POST['bank_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3 d-none" id="otherNameRow">
                    <label class="form-label">Other Details</label>
                    <input type="text" name="other_name" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($_POST['other_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
    document.getElementById('bankNameRow').classList.toggle('d-none', mode !== 'bank');
    document.getElementById('otherNameRow').classList.toggle('d-none', mode !== 'other');
}

const subjectsByExamBody = <?= json_encode($subjects_by_exam_body, JSON_HEX_TAG) ?>;

document.getElementById('productSelect').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const type = opt.dataset.type;
    const examBodyId = parseInt(opt.dataset.examBody);

    document.getElementById('durationRow').classList.toggle('d-none', type !== 'practice');

    const subjectRow = document.getElementById('subjectRow');
    const subjectBoxes = document.getElementById('subjectCheckboxes');
    subjectBoxes.innerHTML = '';

    const subjects = subjectsByExamBody[examBodyId] || [];
    if (subjects.length > 0) {
        subjects.forEach(function(s) {
            const label = document.createElement('label');
            label.className = 'form-check-label d-flex align-items-center gap-1';
            label.innerHTML = `<input type="checkbox" name="subject_ids[]" value="${s.id}" class="form-check-input"> ${s.name}`;
            subjectBoxes.appendChild(label);
        });
        subjectRow.classList.remove('d-none');
    } else {
        subjectRow.classList.add('d-none');
    }
});

toggleTxn('bank');
</script>

<?php include("inc/footer.php"); ?>
</body>
</html>
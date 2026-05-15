<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";
require_once __DIR__ . "/src/db/privileges.php";

if (($assign_product ?? "false") !== "true") {
    header("Location: home.php");
    exit;
}

$product_id   = "";
$txn_no       = "";
$txn_mode     = "bank";
$txn_mbl_no   = "";
$txn_no_erro  = "";

// Must come from GET for page context
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($student_id <= 0) {
    header("Location: users.php");
    exit;
}

// Fetch student safely
$stmtS = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, username, phone, email
                         FROM users
                         WHERE type = 'student' AND user_id = ?
                         LIMIT 1");
if (!$stmtS) {
    header("Location: home.php?err=stmt");
    exit;
}
$stmtS->bind_param("i", $student_id);
$stmtS->execute();
$resS = $stmtS->get_result();

if (!$resS || $resS->num_rows !== 1) {
    $stmtS->close();
    header("Location: home.php");
    exit;
}
$row = $resS->fetch_assoc();
$stmtS->close();

$student_name     = trim(($row['first_name'] ?? '') . " " . ($row['middle_name'] ?? '') . " " . ($row['last_name'] ?? ''));
$student_username = (string)($row['username'] ?? '');
$txn_mbl_no       = (string)($row['phone'] ?? '');
$student_email    = (string)($row['email'] ?? '');

// ---------- FORM SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit'] ?? '') === "assign_product") {
    csrf_verify();

    // Never trust hidden student_id for security decisions; at most ensure it matches.
    $posted_student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    if ($posted_student_id !== $student_id) {
        header("Location: users.php");
        exit;
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $txn_no     = trim((string)($_POST['txn_no'] ?? ''));
    $txn_mode   = (string)($_POST['txn_mode'] ?? '');
    $txn_mbl_no = trim((string)($_POST['txn_mbl_no'] ?? ''));
    $discount   = isset($_POST['discount']) ? (int)$_POST['discount'] : 0;

    // Validate inputs
    if ($product_id <= 0) {
        header("Location: assign-product.php?student_id=" . $student_id . "&err=product");
        exit;
    }

    // Reasonable bounds
    if ($discount < 0) $discount = 0;
    if ($discount > 1000000) { // sanity cap
        header("Location: assign-product.php?student_id=" . $student_id . "&err=discount");
        exit;
    }

    // txn_mode allowlist
    $allowed_modes = ['esewa', 'khalti', 'bank'];
    if (!in_array($txn_mode, $allowed_modes, true)) {
        header("Location: assign-product.php?student_id=" . $student_id . "&err=mode");
        exit;
    }

    // txn_no basic validation (tighten as needed)
    if ($txn_no === '' || strlen($txn_no) > 64) {
        header("Location: assign-product.php?student_id=" . $student_id . "&err=txn");
        exit;
    }

    // Mobile validation
    if (!preg_match('/^[0-9]{7,15}$/', $txn_mbl_no)) {
        header("Location: assign-product.php?student_id=" . $student_id . "&err=mobile");
        exit;
    }

    // 1) Duplicate TXN check (prepared)
    $stmtT = $conn->prepare("SELECT id FROM purchased_products WHERE txn_no = ? LIMIT 1");
    if (!$stmtT) {
        header("Location: assign-product.php?student_id=" . $student_id . "&err=stmt");
        exit;
    }
    $stmtT->bind_param("s", $txn_no);
    $stmtT->execute();
    $resT = $stmtT->get_result();

    if ($resT && $resT->num_rows > 0) {
        $stmtT->close();
        $txn_no = "";
        $txn_no_erro = "TXN Id already taken.";
    } else {
        $stmtT->close();

        // 2) Product details (must be live)
        $stmtP = $conn->prepare("SELECT id, price, sets FROM products WHERE id = ? AND status = 'live' LIMIT 1");
        if (!$stmtP) {
            header("Location: assign-product.php?student_id=" . $student_id . "&err=stmt");
            exit;
        }
        $stmtP->bind_param("i", $product_id);
        $stmtP->execute();
        $resP = $stmtP->get_result();

        if (!$resP || $resP->num_rows !== 1) {
            $stmtP->close();
            header("Location: assign-product.php?student_id=" . $student_id . "&err=product_not_live");
            exit;
        }

        $product = $resP->fetch_assoc();
        $stmtP->close();

        $price          = (int)($product['price'] ?? 0);
        $remaining_sets = (int)($product['sets'] ?? 0);

        // Amount cannot go negative
        $amount = $price - $discount;
        if ($amount < 0) $amount = 0;

        // 3) Insert purchase + notification in a transaction
        if (!mysqli_begin_transaction($conn)) {
            header("Location: assign-product.php?student_id=" . $student_id . "&err=txn_begin");
            exit;
        }

        try {
            $date = date('Y-m-d');
            $time = date('H:i:s');

            $stmtI = $conn->prepare(
                "INSERT INTO purchased_products
                 (user_id, product_id, amount, remaining_sets, purchased_on, purchased_at, txn_no, txn_mode, mobile, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)"
            );
            if (!$stmtI) throw new Exception("prepare insert failed");

            $stmtI->bind_param(
                "iiiisssssi",
                $student_id,
                $product_id,
                $amount,
                $remaining_sets,
                $date,
                $time,
                $txn_no,
                $txn_mode,
                $txn_mbl_no,
                $user_id
            );

            if (!$stmtI->execute()) {
                $stmtI->close();
                throw new Exception("insert execute failed");
            }
            $stmtI->close();

            $note = "You just purchased a Product. Thank you for choosing our services.";
            $stmtN = $conn->prepare(
                "INSERT INTO notification (user_id, notification, date, time)
                 VALUES (?, ?, ?, ?)"
            );
            if (!$stmtN) throw new Exception("prepare notification failed");

            $stmtN->bind_param("isss", $student_id, $note, $date, $time);

            if (!$stmtN->execute()) {
                $stmtN->close();
                throw new Exception("notification execute failed");
            }
            $stmtN->close();

            mysqli_commit($conn);

            // PRG redirect (no JS)
            header("Location: user-details.php?id=" . $student_id . "&ok=assigned");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            header("Location: assign-product.php?student_id=" . $student_id . "&err=assign_failed");
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
<title>Assign Product</title>

<?php include("src/inc/links.php"); ?>

<script>
$(document).ready(function () {
    $(".txn_no").keyup(function () {
        let txn_no = this.value;
        $.get("src/api/data-check-api.php?txn_no=" + txn_no, function (data) {
            if (data === "Status 200") {
                $("#txn_no").css("border", "2px solid red");
                $(".txn_no_error").text("TXN No. is already submitted.");
                $(".submit").prop("disabled", true);
            } else {
                $("#txn_no").css("border", "2px solid green");
                $(".txn_no_error").text("");
                $(".submit").prop("disabled", false);
            }
        });
    });
});
</script>
</head>

<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
<div class="row">
<div class="col-md-6 p-1">

<h4>Assign Product:</h4>

<form method="POST">
<?= csrf_input(); ?>

<label>@Username:</label>
<input value="<?= htmlspecialchars($student_username, ENT_QUOTES, 'UTF-8') ?>" class="form-control" disabled>

<input type="hidden" name="student_id" value="<?= $student_id ?>">

<label>Product</label>
<select name="product_id" class="form-control" required>
<option disabled selected>SELECT ONE</option>
<?php
$products = mysqli_query($conn, "SELECT * FROM products WHERE status='live'");
while ($p = mysqli_fetch_assoc($products)) {
    echo "<option value='{$p['id']}'>{$p['name']}</option>";
}
?>
</select>

<label>Transaction No.</label>
<input type="text" name="txn_no" id="txn_no"
       value="<?= htmlspecialchars($txn_no, ENT_QUOTES, 'UTF-8') ?>"
       class="txn_no form-control" required>
<i class="text-danger txn_no_error"><?= $txn_no_erro ?></i>

<label>Discount (NPR)</label>
<input type="number" name="discount" value="0" class="form-control">

<label>Mobile Number</label>
<input type="text"
       name="txn_mbl_no"
       value="<?= htmlspecialchars($txn_mbl_no, ENT_QUOTES, 'UTF-8') ?>"
       class="form-control"
       maxlength="15"
       pattern="[0-9]{7,15}"
       required>

<label>Transaction Mode</label>
<table class="table">
<tr>
<td><input type="radio" name="txn_mode" value="esewa"> Esewa</td>
<td><input type="radio" name="txn_mode" value="khalti"> Khalti</td>
<td><input type="radio" name="txn_mode" value="bank" checked> Bank</td>
</tr>
</table>

<button type="submit" name="submit" value="assign_product"
        class="btn submit bg-success text-light">
Assign Product
</button>

</form>
</div>
</div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

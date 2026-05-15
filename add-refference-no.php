<?php
ob_start();
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";

if (($add_sales_bill  ?? "false") !== "true") {
    header("Location: home.php");
    exit;
}

// Validate purchased_id
if (!isset($_GET['purchased_id']) || !ctype_digit($_GET['purchased_id'])) {
    header("Location: home.php");
    exit;
}

$purchased_id = (int)$_GET['purchased_id'];

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit'] ?? '') === 'add_reff_no') {
    csrf_verify();

    $reference_no = trim($_POST['reference_no'] ?? '');

    if ($reference_no === '') {
    $error = "Reference number cannot be empty.";
    } elseif (!ctype_digit($reference_no)) {
        $error = "Reference number must be numeric.";
    } elseif ((int)$reference_no <= 0) {
        $error = "Reference number must be greater than zero.";
    }

    else {
        $reference_no = (int)$reference_no;
        $stmt = mysqli_prepare($conn,
            "UPDATE purchased_products SET refrence_no = ? WHERE id = ? LIMIT 1"
        );
        if (!$stmt) {
            $error = "Server error. Try again.";
        } else {
            mysqli_stmt_bind_param($stmt, 'ii', $reference_no, $purchased_id);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                header("Location: sales-bill.php");
                exit;
            } else {
                $error = "Failed to add reference number.";
            }
        }
    }
}

// Fetch existing record
$stmt = mysqli_prepare($conn,
    "SELECT txn_no, txn_mode, amount FROM purchased_products WHERE id = ? LIMIT 1"
);
if (!$stmt) {
    header("Location: home.php");
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $purchased_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    header("Location: home.php");
    exit;
}

$txn_no   = $row['txn_no'];
$txn_mode = $row['txn_mode'];
$amount   = $row['amount'];

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Reference No.</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
    <?php include("src/inc/header.php"); ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 p-2 m-1">
                <h2>Add Reference No.</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?purchased_id=<?= $purchased_id ?>" method="POST">
                    <?= csrf_input(); ?>

                    <label>Amount:</label>
                    <input disabled type="text" value="<?= htmlspecialchars($amount) ?>" class="form-control"/>

                    <label>TXN No.:</label>
                    <input disabled type="text" value="<?= htmlspecialchars($txn_no) ?>" class="form-control"/>

                    <label>TXN Mode:</label>
                    <input disabled type="text" value="<?= htmlspecialchars($txn_mode) ?>" class="form-control"/>

                    <label>Reference No.:</label>
                    <input type="text" placeholder="Enter Reference No." name="reference_no" class="form-control" required/>

                    <br>
                    <button type="submit" name="submit" value="add_reff_no" class="btn text-light" style="background:var(--primary);">Add Reference Number</button>
                </form>
            </div>
        </div>
    </div>

    <?php include("src/inc/footer.php"); ?>
</body> 
</html>
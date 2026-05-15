<?php
ob_start();
require_once("src/db/db_conn.php");
require_once("src/db/session.php");
require_once("src/db/privileges.php");

if ($type !== "agent") {
    header("Location: home.php");
    exit;
}

csrf_verify();

$product_id = $txn_no = $txn_mode = $txn_mbl_no = "";
$txn_no_erro = $error = $success = "";

if (isset($_POST['submit']) && $_POST['submit'] === "assign_product") {

    if (empty($_POST['student_id']) || !ctype_digit($_POST['student_id'])) {
        $error = "Please select a valid student.";

    } elseif (empty($_POST['product_id']) || !ctype_digit($_POST['product_id'])) {
        $error = "Please select a valid product.";

    } elseif (!isset($_POST['txn_mode']) || !in_array($_POST['txn_mode'], ['esewa', 'khalti', 'bank'], true)) {
        $error = "Invalid transaction mode.";

    } elseif (empty(trim($_POST['txn_no']))) {
        $error = "Transaction number required.";

    } elseif (!preg_match('/^[0-9]{7,15}$/', trim($_POST['txn_mbl_no']))) {
        $error = "Invalid mobile number. Use digits only (7–15 digits).";

    } else {
        $student_id = (int) $_POST['student_id'];
        $product_id = (int) $_POST['product_id'];
        $txn_no     = trim($_POST['txn_no']);
        $txn_mode   = $_POST['txn_mode'];
        $txn_mbl_no = trim($_POST['txn_mbl_no']);

        mysqli_begin_transaction($conn);

        try {
            $stmt1 = mysqli_prepare($conn, "SELECT remaining_product_credit FROM agent_stat WHERE agent_id = ? FOR UPDATE");
            mysqli_stmt_bind_param($stmt1, "i", $user_id);
            mysqli_stmt_execute($stmt1);
            $agent_result = mysqli_stmt_get_result($stmt1);
            mysqli_stmt_close($stmt1);

            if (mysqli_num_rows($agent_result) === 0) {
                throw new Exception("Agent record not found.");
            }

            $agentData = mysqli_fetch_assoc($agent_result);
            $remaining_credit = $agentData['remaining_product_credit'];

            if ($remaining_credit < 1) {
                throw new Exception("You have no product credits. Please load credit.");
            }

            $stmt2 = mysqli_prepare($conn, "SELECT id FROM purchased_products WHERE txn_no = ?");
            mysqli_stmt_bind_param($stmt2, "s", $txn_no);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_store_result($stmt2);
            if (mysqli_stmt_num_rows($stmt2) > 0) {
                throw new Exception("Transaction number already used.");
            }
            mysqli_stmt_close($stmt2);

            $stmt3 = mysqli_prepare($conn, "SELECT price, sets FROM products WHERE id = ? AND status = 'live' AND is_practice = 0 AND price > 0 LIMIT 1");
            mysqli_stmt_bind_param($stmt3, "i", $product_id);
            mysqli_stmt_execute($stmt3);
            $product_result = mysqli_stmt_get_result($stmt3);
            mysqli_stmt_close($stmt3);

            if (mysqli_num_rows($product_result) === 0) {
                throw new Exception("Invalid product selected.");
            }

            $prod = mysqli_fetch_assoc($product_result);
            $amount = $prod['price'] - ($prod['price'] * 0.2);
            $remaining_sets = (int) $prod['sets'];

            $stmt4 = mysqli_prepare($conn,
                "INSERT INTO purchased_products (user_id, product_id, amount, remaining_sets, purchased_on, purchased_at, txn_no, txn_mode, mobile, status, created_by)
                 VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, 'active', ?)"
            );
            mysqli_stmt_bind_param($stmt4, "iidisssi", $student_id, $product_id, $amount, $remaining_sets, $txn_no, $txn_mode, $txn_mbl_no, $user_id);
            if (!mysqli_stmt_execute($stmt4)) {
                throw new Exception("Failed to assign product.");
            }
            mysqli_stmt_close($stmt4);

            $new_credit = $remaining_credit - 1;
            $stmt5 = mysqli_prepare($conn, "UPDATE agent_stat SET remaining_product_credit = ? WHERE agent_id = ?");
            mysqli_stmt_bind_param($stmt5, "ii", $new_credit, $user_id);
            if (!mysqli_stmt_execute($stmt5)) {
                throw new Exception("Failed to deduct credit.");
            }
            mysqli_stmt_close($stmt5);

            $notification_msg = "You just purchased a product. Thank you!";
            $stmt6 = mysqli_prepare($conn, "INSERT INTO notification (user_id, notification, date, time) VALUES (?, ?, CURDATE(), CURTIME())");
            mysqli_stmt_bind_param($stmt6, "is", $student_id, $notification_msg);
            mysqli_stmt_execute($stmt6);
            mysqli_stmt_close($stmt6);

            mysqli_commit($conn);
            $success = "Product assigned successfully!";

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Remaining credit display
$remaining_credit = 0;
$stmt_credit = mysqli_prepare($conn, "SELECT remaining_product_credit FROM agent_stat WHERE agent_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_credit, "i", $user_id);
mysqli_stmt_execute($stmt_credit);
$credit_result = mysqli_stmt_get_result($stmt_credit);
mysqli_stmt_close($stmt_credit);
if (mysqli_num_rows($credit_result) > 0) {
    $agent_data = mysqli_fetch_assoc($credit_result);
    $remaining_credit = (int) $agent_data['remaining_product_credit'];
}

// Product dropdown — one JOIN query, no N+1
$stmt_products = mysqli_prepare($conn,
    "SELECT p.id, p.name FROM products p
     INNER JOIN courses c ON c.id = p.course_id AND c.status = 'live'
     WHERE p.is_practice = 0 AND p.status = 'live' AND p.price > 0"
);
mysqli_stmt_execute($stmt_products);
$products_result = mysqli_stmt_get_result($stmt_products);
mysqli_stmt_close($stmt_products);

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Product</title>
<?php include("src/inc/links.php"); ?>
<style>
.search { position:relative; }
#result { position:absolute; width:100%; cursor:pointer; overflow-y:auto; max-height:400px; box-sizing:border-box; }
.link-class { border:1px solid grey; padding:2px 10px; display:inline-block; }
.link-class:hover { background:#f1f1f1; }
</style>
<script>
$(document).ready(function() {

    $("#search").keyup(function(){
        var keyWord = this.value;
        $.ajax({
            url: 'src/api/students.php?p=' + encodeURIComponent(keyWord),
            type: 'GET',
            success: function(response){
                var data = $.parseJSON(response);
                $(".list-group").html(
                    '<li class="list-group link-class">' +
                    $('<span>').text(data.first_name + ' ' + data.middle_name + ' ' + data.last_name).html() +
                    ' | <span class="text-muted">@' + $('<span>').text(data.username).html() + '</span></li>'
                );
                $(".link-class").click(function(){
                    $(".student_id").html(
                        '<label>Student Id</label>' +
                        '<input type="hidden" name="student_id" value="' + parseInt(data.user_id) + '">' +
                        '<input type="text" class="form-control" value="' + parseInt(data.user_id) + '" disabled>'
                    );
                    $(".student_username").html(
                        '<label>@Username:</label>' +
                        '<input type="text" class="form-control" value="' + $('<span>').text(data.username).html() + '" disabled>'
                    );
                    $("#result").hide();
                });
            }
        });
    });

    $(".txn_no").keyup(function(){
        var txn_no = this.value;
        $.ajax({
            url: "src/api/data-check-api.php",
            type: "POST",
            data: {
                txn_no: txn_no,
                csrf_token: '<?php echo csrf_token(); ?>'
            },
            success: function(data){
                if(data === "Status 200"){
                    $("#txn_no").css("border", "2px solid red");
                    $(".txn_no_error").text("TXN No. is already submitted.");
                    $(".submit-btn").attr("disabled", true);
                } else {
                    $("#txn_no").css("border", "2px solid green");
                    $(".txn_no_error").text("");
                    $(".submit-btn").removeAttr("disabled");
                }
            }
        });
    });
});
</script>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container mt-3">
    <div class="alert alert-info">
        Your Remaining Product Credits: <strong><?php echo (int) $remaining_credit; ?></strong>
    </div>
    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($success !== ""): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 mt-1">
            <div class="search">
                <label>Search Student</label>
                <input type="text" class="form-control" id="search" placeholder="Search student">
                <div id="result">
                    <ul class="list-group"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mt-5 p-1">
            <h5>Assign Product:</h5>
            <form action="a-assign-product.php" method="POST">
                    <?php echo csrf_input(); ?>
                <div class="row">
                    <div class="col-md-6 student_id"></div>
                    <div class="col-md-6 student_username"></div>

                    <div class="col-md-12 p-3">
                        <label>Product</label>
                        <select name="product_id" class="form-control" required>
                            <option value="" disabled selected>SELECT ONE</option>
                            <?php while ($p = mysqli_fetch_assoc($products_result)): ?>
                                <option value="<?php echo (int) $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <label>Transaction No.:</label>
                        <input type="text" name="txn_no" id="txn_no"
                               value="<?php echo htmlspecialchars($txn_no, ENT_QUOTES, 'UTF-8'); ?>"
                               class="txn_no form-control" required>
                        <i class="text-danger txn_no_error"><?php echo htmlspecialchars($txn_no_erro, ENT_QUOTES, 'UTF-8'); ?></i><br>

                        <label>Mobile Number:</label>
                        <input type="text" name="txn_mbl_no"
                               value="<?php echo htmlspecialchars($txn_mbl_no, ENT_QUOTES, 'UTF-8'); ?>"
                               class="form-control" required>

                        <label>Transaction Mode:</label>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><input type="radio" name="txn_mode" value="esewa" id="esewa"> <label for="esewa">Esewa</label></td>
                                    <td><input type="radio" name="txn_mode" value="khalti" id="khalti"> <label for="khalti">Khalti</label></td>
                                    <td><input checked type="radio" name="txn_mode" value="bank" id="bank"> <label for="bank">Bank</label></td>
                                </tr>
                            </tbody>
                        </table>

                        <button class="btn submit-btn bg-success text-light"
                                type="submit" name="submit" value="assign_product">
                            Assign Product
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
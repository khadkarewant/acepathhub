<?php
ob_start();
require_once("src/db/db_conn.php");
require_once("src/db/session.php");
require_once("src/db/privileges.php");

if($type !== "admin"){
    header("Location: home.php");
    exit;
}

csrf_verify();

$txn_no    = "";
$txn_mode  = "";
$txn_mbl_no = "";
$txn_no_erro = "";
$error     = "";
$agent_id  = "";

if(isset($_POST['submit']) && $_POST['submit'] === "add_credit"){

    if(!isset($_POST['agent_id']) || !ctype_digit($_POST['agent_id'])){
        $error = "Please select a valid Agent.";
    } elseif(!isset($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] <= 0){
        $error = "Invalid amount.";
    } elseif(!isset($_POST['credit']) || !is_numeric($_POST['credit']) || $_POST['credit'] <= 0){
        $error = "Invalid credit.";
    } elseif(!isset($_POST['txn_mode']) || !in_array($_POST['txn_mode'], ['esewa','khalti','bank'], true)){
        $error = "Invalid transaction mode.";
    } elseif(!isset($_POST['txn_no']) || trim($_POST['txn_no']) === ""){
        $error = "Transaction number required.";
    } else {
        $agent_id = (int) $_POST['agent_id'];
        $amount   = (float) $_POST['amount'];
        $credit   = (int) $_POST['credit'];
        $txn_mode = $_POST['txn_mode'];
        $txn_no   = trim($_POST['txn_no']);
        $date     = date("Y-m-d");
        $time     = date("H:i:s");

        mysqli_begin_transaction($conn);

        try {
            $stmt1 = mysqli_prepare($conn, "INSERT INTO `agent_txn`(`agent_id`,`amount`,`product_credit`,`date`,`time`,`txn_no`,`txn_mode`,`refrence_no`) VALUES(?,?,?,?,?,?,?,NULL)");
            mysqli_stmt_bind_param($stmt1, "iddssss", $agent_id, $amount, $credit, $date, $time, $txn_no, $txn_mode);
            if(!mysqli_stmt_execute($stmt1)) throw new Exception("Failed to log transaction.");
            mysqli_stmt_close($stmt1);

            $stmt2 = mysqli_prepare($conn, "SELECT `product_credited`,`remaining_product_credit`,`payment_deposited` FROM `agent_stat` WHERE `agent_id` = ?");
            mysqli_stmt_bind_param($stmt2, "i", $agent_id);
            mysqli_stmt_execute($stmt2);
            $result = mysqli_stmt_get_result($stmt2);
            mysqli_stmt_close($stmt2);

            if(mysqli_num_rows($result) === 1){
                $row = mysqli_fetch_assoc($result);
                $new_total_product            = $credit + $row['product_credited'];
                $new_remaining_product_credit = $credit + $row['remaining_product_credit'];
                $new_payment_deposited        = $amount + $row['payment_deposited'];

                $stmt3 = mysqli_prepare($conn, "UPDATE `agent_stat` SET `product_credited`=?,`remaining_product_credit`=?,`payment_deposited`=? WHERE `agent_id`=?");
                mysqli_stmt_bind_param($stmt3, "dddi", $new_total_product, $new_remaining_product_credit, $new_payment_deposited, $agent_id);
                if(!mysqli_stmt_execute($stmt3)) throw new Exception("Failed to update agent stat.");
                mysqli_stmt_close($stmt3);
            } else {
                $stmt4 = mysqli_prepare($conn, "INSERT INTO `agent_stat`(`agent_id`,`product_credited`,`remaining_product_credit`,`payment_deposited`) VALUES(?,?,?,?)");
                mysqli_stmt_bind_param($stmt4, "iiid", $agent_id, $credit, $credit, $amount);
                if(!mysqli_stmt_execute($stmt4)) throw new Exception("Failed to insert agent stat.");
                mysqli_stmt_close($stmt4);
            }

            mysqli_commit($conn);
            header("Location: agent-stat.php");
            exit;

        } catch(Exception $e){
            mysqli_rollback($conn);
            $error = "Transaction failed. Please try again.";
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Credit</title>
    <?php include("src/inc/links.php"); ?>
    <style>
        .search{ position:relative; }
        #result{
            position:absolute;
            width:100%;
            cursor:pointer;
            overflow-y:auto;
            max-height:400px;
            box-sizing:border-box;
        }
        .link-class{
            border:1px solid grey;
            padding:2px 10px;
            display:inline-block;
        }
        .link-class:hover{ background:#f1f1f1; }
    </style>
    <script>
        $(document).ready(function(){
            $("#search").keyup(function(){
                var keyWord = this.value;
                $.ajax({
                    url: 'src/api/agents.php?p=' + encodeURIComponent(keyWord),
                    type: 'GET',
                    success: function(response){
                        var data = $.parseJSON(response);
                        $(".list-group").html(
                            '<li class="list-group link-class">' +
                            $('<span>').text(data.first_name + ' ' + data.middle_name + ' ' + data.last_name).html() +
                            ' | <span class="text-muted" style="display:inline">@' +
                            $('<span>').text(data.username).html() +
                            '</span></li>'
                        );
                        $(".link-class").click(function(){
                            $(".agent_id").html(
                            '<label>Agent Id</label>' +
                            '<input type="hidden" name="agent_id" value="' + parseInt(data.user_id) + '">' +
                            '<input type="text" class="form-control" value="' + parseInt(data.user_id) + '" disabled>'
                        );
                        $(".agent_username").html(
                            '<label>Agent Username</label>' +
                            '<input type="text" class="form-control" value="' + $('<span>').text(data.username).html() + '" disabled>'
                        );
                            $("#result").css("display", "none");
                        });
                    }
                });
            });

            $(".txn_no").keyup(function(){
                var txn_no = this.value;
                $.ajax({
                    url: "src/api/agent-data-check-api.php",
                    type: "POST",
                    data: {
                        txn_no: txn_no,
                        csrf_token: '<?php echo csrf_token(); ?>'
                    },
                    success: function(data){
                        if(data === "Status 200"){
                            $("#txn_no").css("border","2px solid red");
                            $(".txn_no_error").text("TXN No. is already submitted.");
                            $(".submit-btn").attr("disabled", true);
                        } else {
                            $("#txn_no").css("border","2px solid green");
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

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 mt-1">
                <div class="search">
                    <label>Search Agent</label>
                    <input type="text" class="form-control" id="search" placeholder="Search agent">
                    <div id="result">
                        <ul class="list-group"></ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if($error !== ""): ?>
            <div class="row mt-2">
                <div class="col-md-6">
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mt-5">
            <div class="col-md-6">
                <form method="POST" action="">
                    <?php echo csrf_input(); ?>
                    <div class="row">
                        <div class="col-md-6 agent_id"></div>
                        <div class="col-md-6 agent_username"></div>
                        <div class="col-md-12">
                            <label>Amount Deposited:</label>
                            <input type="number" name="amount" class="form-control" min="1" required>

                            <label>Transaction No.:</label>
                            <input type="text" name="txn_no" id="txn_no" value="<?php echo htmlspecialchars($txn_no, ENT_QUOTES, 'UTF-8'); ?>" class="txn_no form-control" required>
                            <i class="text-danger txn_no_error"><?php echo htmlspecialchars($txn_no_erro, ENT_QUOTES, 'UTF-8'); ?></i>

                            <br>
                            <label>Product Credit:</label>
                            <input type="number" name="credit" class="form-control" min="1" required>

                            <label>Transaction Mode:</label>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td><input type="radio" name="txn_mode" value="esewa" id="esewa"> &nbsp;<label for="esewa">Esewa</label></td>
                                        <td><input type="radio" name="txn_mode" value="khalti" id="khalti"> &nbsp;<label for="khalti">Khalti</label></td>
                                        <td><input type="radio" name="txn_mode" value="bank" id="bank"> &nbsp;<label for="bank">Bank</label></td>
                                    </tr>
                                </tbody>
                            </table>

                            <button class="btn submit-btn bg-success text-light" type="submit" name="submit" value="add_credit">Add Credit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include("src/inc/footer.php"); ?>
</body>
</html>
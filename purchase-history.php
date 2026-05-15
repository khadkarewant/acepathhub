<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");
    if($view_user !== "true"){
        header("Location: home.php"); exit;
    }

    if (!isset($_GET['student_id'])) {
        header("Location: users.php"); exit;
    }

    $student_id = (int)$_GET['student_id'];

    if($student_id <= 0){
        header("Location: users.php"); exit;
    }

    $check_user_availibility = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` = $student_id ");

    if(mysqli_num_rows($check_user_availibility) == 0){
        header("Location: users.php"); exit;
    }else{
        while ($row = mysqli_fetch_assoc($check_user_availibility)) {
            $user_name = $row['first_name']." ".$row['middle_name']." ".$row['last_name'];
            $user_username = $row['username'];
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History</title>
    <?php
        include("src/inc/links.php");
    ?>
</head>
<body>
<?php
   include("src/inc/header.php");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 table-responsive p-2">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username:</th>
                        <td><?php echo $user_username ?></td>
                    </tr>
                    <tr>
                        <th>Student Name:</th>
                        <td><?php echo $user_name ?></td>
                    </tr>
                </thead>
            </table>

            <h5>Purchase History:</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Refrence No.</th>
                        <th>TXN Mode</th>
                        <th>TXN No.</th>
                        <th>Status</th>
                        <th>Purchased On</th>
                        <th>Remaining Sets</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $get_purchased_history = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `user_id` = $student_id ");

                        if(mysqli_num_rows($get_purchased_history)>0){
                            while ($row = mysqli_fetch_assoc($get_purchased_history)) {

                                $get_product_name = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$row['product_id']."' ");
                                foreach ($get_product_name as $key => $value) {
                                    $product_name = $value['name'];
                                }
                                echo'
                                    <tr>
                                        <td>'.$product_name.'</td>
                                        <td>'.$row['refrence_no'].'</td>
                                        <td>'.$row['txn_mode'].'</td>
                                        <td>'.$row['txn_no'].'</td>
                                        <td>'.$row['status'].'</td>
                                        <td>'.$row['purchased_on'].'</td>
                                        <td>'.$row['remaining_sets'].'</td>
                                        <td>
                                            <button class="bg-success text-light" onclick="window.location.href=\'exam-history.php?product_id='.$row['product_id'].'&user_id='.$row['user_id'].'\'">Exam History</button>
                                ';
                                    if($row['status'] !== "inactive" && $row['remaining_sets'] > 0 ){
                                    ?>
                                      <form method="POST" action="cancel-purchase-product.php" style="display:inline;">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="purchase_id" value="<?= (int)$row['id']; ?>">
                                        <input type="hidden" name="student_id" value="<?= (int)$student_id; ?>">
                                        <button type="submit" class="bg-danger text-light">Cancel</button>
                                    </form>  
                                    <?php    
                                    }
                                echo'

                                        </td>
                                    </tr>
                                ';
                            }
                        }else{
                            echo'
                                <tr>
                                    <td colspan="9">No Product Purchased</td>
                                </tr>
                            ';
                        }
                    ?>
                    <tr>

                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php
    include("src/inc/footer.php");
?>
</body>
</html>
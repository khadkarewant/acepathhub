<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($type !== "admin" && $type !== "biller"){
        header("Location: home.php");
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Bill</title>
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
            <div class="col-md-12 p-2">
                <h4>Bill Summery:</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="datatable">
                        <thead>
                            <tr>
                                <th>Purchase Id</th>
                                <th>Product Name</th>
                                <th>Product Id</th>
                                <th>TXN Id</th>
                                <th>Student Username</th>
                                <th>Price</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $get_bills = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `refrence_no` IS NULL ORDER BY `id` DESC ");

                                while ($row = mysqli_fetch_assoc($get_bills)) {
                                    $purchase_id = $row['id'];
                                    $product_id = $row['product_id'];
                                    $price = $row['amount'];
                                    
                                    $get_product_name = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."'");

                                    foreach ($get_product_name as $key => $value) {
                                        $product_name = $value['name'];
                                    }

                                    $get_student_name = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` ='".$row['user_id']."' "); 
                                    foreach ($get_student_name as $key => $value) {
                                        $student_name = $value['first_name']." ".$value['middle_name']." ".$value['last_name'];
                                    }

                                    if($price > 0 ){
                                        echo'
                                            <tr>
                                                <td>'.$row['id'].' </td>
                                                <td>'.$product_name.' </td>
                                                <td>'.$product_id.' </td>
                                                <td>'.$row['txn_no'].' </td>
                                                <td>'.$student_name.' </td>
                                                <td>'.$price.' </td>
                                                <td>'.$row['purchased_on'].' </td>
                                                <td>'.$row['purchased_at'].' </td>
                                                <td>
                                                ';
    
                                                if($row['refrence_no'] == ""){
                                                    echo'
                                                        <button onclick="window.location.href=\'add-refference-no.php?purchased_id='.$row['id'].'\'" style="background:var(--primary);color:white;">Add Ref. No.</button>
                                                    ';
                                                }
    
                                                echo'
                                                </td>
                                            </tr>
                                        ';
                                    }
                                }
                            ?>
                           
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php
    include("src/inc/footer.php");
?>
</body>
</html>
<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if(isset($_GET['user_id']) && isset($_GET['product_id'])){
        $check_purchased_products = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `product_id` = '".$_GET['product_id']."' AND `user_id` = '".$_GET['user_id']."' ");
        
        $student_id = $_GET['user_id'];
        $get_student_name =mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` = '".$student_id."' ");
        
        if(mysqli_num_rows($get_student_name) == 0){
            header("Location: home.php");
        }
        
        foreach ($get_student_name as $key => $value) {
            $student_username = $value['username'];
        }

        $get_product_name = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$_GET['product_id']."' ");
        foreach ($get_product_name as $key => $value) {
            $product_name = $value['name'];
        }

        if(mysqli_num_rows($check_purchased_products) == 0){
            header("Location: home.php");
        }

        $total_product_purchased = mysqli_num_rows($check_purchased_products);
    }else{
        header("Location: home.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam history</title>
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
        <div class="col-md-12 table-responsive">
            <?php
                if($type == "student"){
                    echo'
                        <h4>Your Performance Stats:</h4>
                    ';
                }else{
                    echo'
                        <h4>Students Performance Stats:</h4>
                    ';
                }
            ?>



            <table class="table">
                <thead>
                    <tr>
                        <th>@username:</th>
                        <td colspan="4"><?php echo $student_username; ?></td>
                    </tr>
                    <tr>
                        <th>Product Id:</th>
                        <td colspan="4"><?php echo $_GET['product_id']; ?></td>
                    </tr>
                    <tr>
                        <th>Product Name:</th>
                        <td colspan="4"><?php echo $product_name ?></td>
                    </tr>
                    <tr>
                        <th>Unique Stat Id</th>
                        <th>Purchase Id</th>
                        <th>Set Id</th>
                        <th>Attepted Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                        $get_exam_stats = mysqli_query($conn ,"SELECT * FROM `exam_stats` WHERE `product_id` = '".$_GET['product_id']."' AND `user_id` = '".$student_id."' ORDER BY `id` DESC ");

                        if (mysqli_num_rows($get_exam_stats)>0) {
                            foreach ($get_exam_stats as $key => $value) {
                                echo'
                                    <tr>
                                        <td>'.$value['id'].'</td>
                                        <td>'.$value['product_id'].'</td>
                                        <td>'.$value['product_name'].'</td>
                                        <td>'.$value['attempted_date'].'</td>
                                        <td>
                                            <button class="btn bg-success text-light" onclick="window.location.href=\'exam-summery.php?set_id='.$value['set_id'].'\'">View Details</button>
                                        </td>
                                    </tr>
                                ';
                            }
                        }else{
                            echo'
                                <tr>
                                    <td colspan="6">No Stats Found</td>
                                </tr>
                            ';
                        }
                    ?>
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
<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <?php
        include("src/inc/links.php");
    ?>
</head>
<body>
    <?php
        include("src/inc/header.php");
    ?>

    <!-- for ! student -->
    <!-- for ! student -->
    <!-- for ! student -->
    <?php
        if($type !== "student"){
    ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 table-responsive">
                
                    <h3><strong>All Products</strong></h3>
                    <?php
                        if($create_product == "true"){
                            echo'
                            <button class="btn" style="background:var(--primary);color:white;" onclick="window.location.href=\'add-product.php\'">Add Product</button>
                            <br>
                            <hr>
                            ';
                        }
                    ?>

                    <table class="table" id="datatable">
                        <thead>
                            <tr>
                                <th>Product Id</th>
                                <th>Product Name</th>
                                <th>Course Name</th>
                                <th>No. of Sets</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $get_products = mysqli_query($conn, "SELECT * FROM `products` ");
                            if (mysqli_num_rows($get_products)>0) {
                                while ($row = mysqli_fetch_assoc($get_products)) { 
                                    $get_course_name = mysqli_query($conn, "SELECT * FROM `courses` WHERE `id` = '".$row['course_id']."' ");
                                    foreach ($get_course_name as $key => $value) {
                                        $course_name = $value['name'];
                                    }

                                    echo'
                                        <tr>
                                            <td>'.$row['id'].'</td>
                                            <td>'.$row['name'].'</td>
                                            <td>'.$value['name'].'</td>
                                            <td>'.$row['sets'].'</td>
                                            <td>
                                    ';

                                    echo'
                                        <button class="btn bg-dark text-light" onclick="window.location.href=\'product-details.php?product_id='.$row['id'].'\'">View</button>

                                        <button class="btn bg-success text-light" onclick="window.location.href=\'discussion.php?product_id='.$row['id'].'\'">Discussion</button>
                                    ';
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
    <?php
        } 
    ?>

    <!-- for student -->
    <!-- for student -->
    <!-- for student -->
    <?php
        if($type =="student"){
    ?>
        <div class="container-fluid">
            <div class="row">
                    <?php
                        $get_product_tag = mysqli_query($conn,"SELECT * FROM `products` WHERE `status`='live' GROUP BY `tag` ");

                        while ($row = mysqli_fetch_assoc($get_product_tag)) {

                            if($row['tag'] !== "demo" && $row['tag'] !=="practice"){
                                echo'
                                    <h4 style="text-transform:uppercase; color:var(--primary);">'.$row['tag'].'</h4>
                                    <hr>
                                ';
                                $get_product = mysqli_query($conn, "SELECT * FROM `products` WHERE `tag` = '".$row['tag']."' AND `status` = 'live'  ");
    
                                foreach ($get_product as $key => $value) {
                                    echo'
                                        <div class="col-md-4 mb-5 p-2 border shadow rounded mx-auto">
                                            <h5>'.$value['name'].'</h5>
                                            <div><strong>Product Id: </strong>'.$value['id'].'</div>

                                            <button class="btn text-light" onclick="window.location.href=\'product-details.php?product_id='.$value['id'].'\'" style="background:var(--primary);">View</button>

                                            <button class="btn btn-success" onclick="window.open(\'https://wa.me/9779700186061?text='.rawurlencode('I want to buy the product: '.$value['name'].' and My username is: '.$username).'\', \'_blank\');">Purchase</button>

                                        </div>
                                    ';
                                }
                            }
                        }
                    ?>
            </div>
        </div>
    <?php
        }
    ?>

    

    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($type !== "admin"){
        header("Location: home.php");
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Stats</title>
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
                <h4>Product Summery:</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="datatable">
                        <thead>
                            <tr>
                                <th>Product Id</th>
                                <th>Product Name</th>
                                <th>Total Sets Issued</th>
                                <th>Remaining Sets</th>
                                <th>Total Sales</th>
                                <th>Price</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                                $get_products = mysqli_query($conn, "SELECT * FROM `products` WHERE `name` NOT LIKE '%Demo%' ");
                                while ($row = mysqli_fetch_assoc($get_products)) {
                                    $product_id = $row['id'];
                                    
                                    $get_price_variation = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `product_id` = '".$product_id."' GROUP BY `amount` ");
                                    foreach ($get_price_variation as $key => $value) {
                                        $sales_price = $value['amount'];

                                        $get_sales_data = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `product_id` = '".$product_id."' AND `amount` = '".$sales_price."' ");

                                        $total_sales = mysqli_num_rows($get_sales_data);

                                        $total_sets_issued = $row['sets']*$total_sales;

                                        $get_remaining_sets = mysqli_query($conn, "SELECT SUM(remaining_sets), SUM(amount) FROM `purchased_products` WHERE `product_id` = '".$product_id."' AND `amount` = '".$sales_price."' ");

                                        foreach ($get_remaining_sets as $key => $value) {
                                            $remaining_sets = $value['SUM(remaining_sets)'];
                                            $revenue = $value['SUM(amount)'];
                                        }

                                        echo'
                                            <tr>
                                                <td>'.$row['id'].'</td>
                                                <td>'.$row['name'].' </td>
                                                <td>'.$total_sets_issued.' </td>
                                                <td>'.$remaining_sets.' </td>
                                                <td>'.$total_sales.' </td>
                                                <td>'.$sales_price.' </td>
                                                <td>'.$revenue.' </td>
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
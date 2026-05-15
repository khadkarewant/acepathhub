<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    

    if(isset($_GET['product_id']) && $_GET['product_id'] !== ""){
        
        $product_id = $_GET['product_id'];
        $get_data = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."'");
       
        if(mysqli_num_rows($get_data) == 0){
            header("Location: home.php");
        }
        
        while ($row = mysqli_fetch_assoc($get_data)) {
            if($row['created_by'] == $user_id || $type == "super_admin"){
                $product_name = $row['name'];
                $description = $row['description'];
                $price = $row['price'];
                $level_1 = $row['level_1'];
                $level_2 = $row['level_2'];
                $exam_duration = $row['exam_duration'];
                $total_question = $row['total_question'];
            }else{
                header("Location: products.php");
            }
        }
        if(isset($_GET['submit']) && $_GET['submit'] == "update_product"){
            $new_name = htmlentities($_GET['product_name']);
            $new_description =  htmlentities($_GET['description']);
            $new_price =  htmlentities($_GET['price']);
            $new_level_1 =  htmlentities($_GET['level_1']);
            $new_level_2 =  htmlentities($_GET['level_2']);
            $new_exam_duration =  htmlentities($_GET['exam_duration']);
            $new_total_question =  htmlentities($_GET['total_question']);
    
            $update_product = mysqli_query($conn, "UPDATE `products` SET `name` = '".$new_name."', `description` = '".$new_description."', `price` = '".$new_price."', `exam_duration` = '".$new_exam_duration."', `total_question` = '".$new_total_question."', `level_1` = '".$new_level_1."', `level_2` = '".$new_level_2."' WHERE `id` = '".$product_id."' ");
    
            if ($update_product) {
                echo'
                    <script>
                        alert("Product Updated successfully.");
                        window.location.href="product-details.php?product_id='.$product_id.'";
                    </script>
                ';
            }
        }

    }else{
        header("Location: products.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product</title>
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
            <div class="col-md-4 p-1">
                <h4>Update Product</h4>
                <form class="form p-1 shadow" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" name="product_id" hidden value="<?php echo $product_id; ?>" required/>
                    <label>Product Name:</label>
                    <input type="text" name="product_name" class="form-control" value="<?php echo $product_name; ?>" required/>
                    
                    
                    <label>description:</label>
                    <input type="text" name="description" class="form-control"  value="<?php echo $description; ?>" required/>
            
                    <label>price:</label>
                    <input type="number" name="price" value="<?php echo $price ?>" class="form-control">
                    <div class="row">
                        <div class="col-6">
                            <label>Level 1 Questions</label>
                            <input type="number" class="form-control" name="level_1" value="<?php echo $level_1 ?>"  required>
                        </div>
                        <div class="col-6">
                            <label>Level 2 Questions</label>
                            <input type="number" class="form-control" name="level_2" value="<?php echo $level_2 ?>"  required>
                        </div>
                    </div>

                    <label>Exam Duration:</label>
                    <input type="number" placeholder="In minutes" name="exam_duration" value="<?php echo $exam_duration ?>" class="form-control">

                    <label>Total Question:</label>
                    <input type="number" name="total_question" value="<?php echo $total_question ?>" class="form-control">
                    <br>
                    <button type="submit" name="submit" value="update_product" class="btn" style="background:var(--primary);color:white;">Update Product</button>
                    <br>
                </form>
            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
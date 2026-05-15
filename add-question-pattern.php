<?php
    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");



    if(isset($_GET['product_id']) && $_GET['product_id'] !== ""){

        $product_id = $_GET['product_id'];
        $get_product_details = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."' ");

        if(mysqli_num_rows($get_product_details) == 0){
            header("Location: home.php");
        }

        while ($row = mysqli_fetch_assoc($get_product_details)) {
            $product_name = $row['name'];
        }
        
        if(isset($_GET['submit']) && $_GET['submit'] == "add_question_pattern"){
            
            $product_id = $_GET['product_id'];
            $pattern_name = htmlentities($_GET['pattern_name']);
            $question_weight =  htmlentities($_GET['question_weight']);


            $insert_question_pattern = mysqli_query($conn, "INSERT INTO `question_patterns`(`product_id`,`name`, `question_weight`)VALUES('".$product_id."', '".$pattern_name."', '".$question_weight."')");
    
            if ($insert_question_pattern) {
                echo'
                    <script>
                        alert("Question Pattern Created successfully.");
                        window.location.href="product-details.php?product_id='.$product_id.'";
                    </script>
                ';
            }
        }
    }else{
        header("Location: products.php");
    }

    ob_end_flush();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question Pattern</title>
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
            <div class="col-md-6">
                <h2>Add New Question Pattern</h2>
                <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" hidden name="product_id" value="<?php echo $product_id ?>" required/>
            
                    <label>Product Name:</label>
                    <input type="text" name="product_name" disabled class="form-control" value="<?php echo $product_name; ?>"/>
                    
                    
                    <label>Pattern Name:</label>
                    <input type="text" placeholder="Pattern Name" name="pattern_name" class="form-control" required/>

                    <label>Question Weight</label>
                    <input type="number" class="form-control" name="question_weight" required>
                    <br>
                    <button type="submit" name="submit" value="add_question_pattern" class="btn bg-success">Add Question Pattern</button>
                </form>
            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
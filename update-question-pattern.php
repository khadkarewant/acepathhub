<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");



    if(isset($_GET['pattern_id']) && $_GET['pattern_id'] !== ""){

        $pattern_id = $_GET['pattern_id'];
        $get_pattern_name = mysqli_query($conn, "SELECT * FROM `question_patterns` WHERE `id` = '".$pattern_id."'");

        if(mysqli_num_rows($get_pattern_name) == 0){
            header("Location: home.php");
        }

        while ($row = mysqli_fetch_assoc($get_pattern_name)) {
            $pattern_name = $row['name'];
            $product_id = $row['product_id'];
            $question_weight = $row['question_weight'];
        }

        $get_product_details = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."' ");

        while ($row = mysqli_fetch_assoc($get_product_details)) {
            $product_id = $row['id'];
            $product_name = $row['name'];
        }
        
        if(isset($_GET['submit']) && $_GET['submit'] == "update_question_pattern"){
            
            $new_pattern_name = htmlentities($_GET['pattern_name']);
            $new_question_weight = htmlentities($_GET['question_weight']);

            $update_question_pattern = mysqli_query($conn, "UPDATE `question_patterns` SET `name` = '".$new_pattern_name."', `question_weight` = '".$new_question_weight."' WHERE `id` = '".$pattern_id."'  ");
    
            if ($update_question_pattern) {
                echo'
                    <script>
                        alert("Question Pattern Updated successfully.");
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
    <title>Update Question Pattern</title>
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
                <h2>Update Question Pattern</h2>
                <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" hidden name="pattern_id" value="<?php echo $pattern_id ?>" required/>
            
                    <label>Product Name:</label>
                    <input type="text" name="product_name" disabled class="form-control" value="<?php echo $product_name; ?>"/>
                    
                    <label>Pattern Name:</label>
                    <input type="text" placeholder="Pattern Name" value="<?php echo $pattern_name; ?>" name="pattern_name" class="form-control" required/>

                    <label>Question Weight:</label>
                    <input type="text" placeholder="Pattern Name" value="<?php echo $question_weight; ?>" name="question_weight" class="form-control" required/>

                    <br>
                    <button type="submit" name="submit" value="update_question_pattern" class="btn bg-success text-light">Update Question Pattern</button>
                </form>
            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
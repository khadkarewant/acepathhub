<?php

    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

   
    if(isset($_GET['pattern_id']) && $_GET['pattern_id'] !== ""){
        
        $pattern_id = $_GET['pattern_id'];

        $get_product_id = mysqli_query($conn, "SELECT `product_id` FROM `question_patterns` WHERE `id` = '".$pattern_id."' ");
        
        if(mysqli_num_rows($get_product_id) == 0){
            header("Location: home.php");
        }

        foreach ($get_product_id as $key => $value) {
           $product_id = $value['product_id'];
        }

        $get_course_id =  mysqli_query($conn, "SELECT `course_id` FROM `products` WHERE `id` = '".$product_id."' ");

        foreach ($get_course_id as $key => $value) {
           $course_id = $value['course_id'];
        }


        $get_pattern_details = mysqli_query($conn, "SELECT * FROM `question_patterns` WHERE `id` = '".$pattern_id."' ");

        while ($row = mysqli_fetch_assoc($get_pattern_details)) {
            $pattern_name = $row['name'];
        }
        
        if(isset($_GET['submit']) && $_GET['submit'] == "add_question_topic"){
            
            $pattern_id = $_GET['pattern_id'];
            $topic_id = htmlentities($_GET['topic_id']);


            $insert_question_topic = mysqli_query($conn, "INSERT INTO `question_topics`(`pattern_id`, `topic_id`)VALUES('".$pattern_id."','".$topic_id."')");
    
            if ($insert_question_topic) {
                echo'
                    <script>
                        alert("Question topic added successfully.");
                        window.location.href="question-pattern-details.php?id='.$pattern_id.'";
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
    <title>Add Question Topic</title>
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
            <div class="col-md-8">
                <h4>Add Question Topic</h4>
                <form  action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET"  enctype="multipart/form-data">
            
                    <input type="text" hidden name="pattern_id" value="<?php echo $pattern_id ?>" required/>

                    
                    <label>Pattern Name:</label>
                    <input type="text" disabled class="form-control" value="<?php echo $pattern_name; ?>"/>
                    
                    <label>Topic:</label>
                    <select name="topic_id" class="form-control" required>
                        <option value="" disabled selected>SELECT ONE</option>
                        <?php
                            $get_topics = mysqli_query($conn, "SELECT * FROM `topics` WHERE `course_id` = '".$course_id."' ");

                            while ($row = mysqli_fetch_assoc($get_topics)) {
                                echo'
                                    <option value="'.$row['id'].'">'.$row['name'].'('.$row['id'].')</option> 
                                ';
                            }
                        ?>
                    </select>

                    <br>
                    <button type="submit" name="submit" value="add_question_topic" class="btn" style="background:var(--primary);color:white;">Add Question Topic</button>
            
                </form>
            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
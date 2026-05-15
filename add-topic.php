<?php
    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($create_topic !== "true"){
        header("Location: courses.php");
    }

    if(isset($_GET['course_id']) && $_GET['course_id'] !== ""){

        $course_id = $_GET['course_id'];
        $get_course_details = mysqli_query($conn, "SELECT * FROM `courses` WHERE `id` = '".$course_id."' ");
        
        if(mysqli_num_rows($get_course_details) == 0){
            header("Location: home.php");
        }
        
        while ($row = mysqli_fetch_assoc($get_course_details)) {
            $course_name = $row['name'];
        }
        
        if(isset($_GET['submit']) && $_GET['submit'] == "add_topic"){
            
            $course_id = $_GET['course_id'];
            $topic_name = htmlentities($_GET['topic_name']);
            $description =  htmlentities($_GET['description']);


            $insert_topic = mysqli_query($conn, "INSERT INTO `topics`(`name`, `description`,`course_id`,`created_by`,`created_on`,`created_at`)VALUES('".$topic_name."', '".$description."', '".$course_id."', '".$user_id."', '".date("Y-m-d")."','".date("H:i:s")."')");
    
            if ($insert_topic) {
                echo'
                    <script>
                        alert("Topic Created successfully.");
                        window.location.href="course-details.php?course_id='.$course_id.'";
                    </script>
                ';
            }
        }
    }else{
        header("Location: courses.php");
    }

    ob_end_flush();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Topic</title>
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
                <h2>Add New Topic</h2>
                <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" hidden name="course_id" value="<?php echo $course_id ?>" required/>
            
                    <label>course Name:</label>
                    <input type="text" disabled class="form-control" value="<?php echo $course_name; ?>"/>
                    
                    
                    <label>Topic Name:</label>
                    <input type="text" placeholder="Topic Name" name="topic_name" class="form-control" required/>
            
                    <label>Topic Description:</label>
                    <input type="text" placeholder="Enter description" name="description" class="form-control" required/>
                    <br>
                    <button type="submit" name="submit" value="add_topic" class="btn bg-success">Add Topic</button>
            
                </form>
            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
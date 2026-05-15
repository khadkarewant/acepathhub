<?php
    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");


    if(isset($_GET['course_id']) && $_GET['course_id'] !== ""){

        $course_id = $_GET['course_id'];
        $get_course_name = mysqli_query($conn, "SELECT * FROM `courses` WHERE `id` = '".$course_id."' ");

        if(mysqli_num_rows($get_course_name) == 0){
            header("Location: home.php");
        }

        while ($row = mysqli_fetch_assoc($get_course_name)) {
            $course_name = $row['name'];
        }
        
        if(isset($_GET['submit']) && $_GET['submit'] == "add_subject"){
            
            $course_id = $_GET['course_id'];
            $subject_name = htmlentities($_GET['subject_name']);
            $description =  htmlentities($_GET['description']);


            $insert_subject = mysqli_query($conn, "INSERT INTO `subjects`(`name`, `description`,`course_id`,`created_by`,`created_on`,`created_at`)VALUES('".$subject_name."', '".$description."', '".$course_id."', '".$user_id."', '".date("Y-m-d")."','".date("H:i:s")."')");
    
            if ($insert_subject) {
                echo'
                    <script>
                        alert("Subject Created successfully.");
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
    <title>Add Subject</title>
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
                <h2>Add New Course</h2>
                <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
        
                    <input type="text" hidden name="course_id" value="<?php echo $course_id ?>" required/>
        
                    <label>Course Name:</label>
                    <input type="text" disabled class="form-control" value="<?php echo $course_name; ?>"/>
                    
                    
                    <label>Subject Name:</label>
                    <input type="text" placeholder="Subject Name" name="subject_name" class="form-control" required/>
        
                    <label>Subject Description:</label>
                    <input type="text" placeholder="Enter description" name="description" class="form-control" required/>
                    <br>
                    <button type="submit" name="submit" value="add_subject" class="btn bg-success">Add Subject</button>
        
                </form>
            </div>
        </div>
    </div>

    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
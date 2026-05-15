<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    
   

    if(isset($_GET['course_id']) && $_GET['course_id'] !== ""){
        
        $course_id = $_GET['course_id'];
        $get_data = mysqli_query($conn, "SELECT * FROM `courses` WHERE `id` = '".$course_id."'");
       
        
        if(mysqli_num_rows($get_data) == 0){
            header("Location: home.php");
        }

        while ($row = mysqli_fetch_assoc($get_data)) {
            if($row['created_by'] == $user_id){
                $course_name = $row['name'];
                $description = $row['description'];
                $syllabus = $row['syllabus'];
            }else{
                header("Location: courses.php");
            }
        }
        if(isset($_GET['submit']) && $_GET['submit'] == "update_course"){
            $new_name = htmlentities($_GET['course_name']);
            $new_description =  htmlentities($_GET['description']);
            $new_syllabus =  htmlentities($_GET['syllabus']);
    
            $update_course = mysqli_query($conn, "UPDATE `courses` SET `name` = '".$new_name."', `description` = '".$new_description."', `syllabus` = '".$new_syllabus."' WHERE `id` = '".$course_id."' ");
    
            if ($update_course) {
                echo'
                    <script>
                        alert("Course Updated successfully.");
                        window.location.href="course-details.php?course_id='.$course_id.'";
                    </script>
                ';
            }
        }

    }else{
        header("Location: courses.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course</title>
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
                <h2>Update Course</h2>
                <form class="form p-1" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" name="course_id" hidden value="<?php echo $course_id; ?>" required/>
                    <label>Course Name:</label>
                    <input type="text" name="course_name" placeholder="Enter Course Name" class="form-control" value="<?php echo $course_name; ?>" required/>
                    
                    
                    <label>description:</label>
                    <input type="text" placeholder="Enter description" name="description" class="form-control"  value="<?php echo $description; ?>" required/>
            
                    <label>Syllabus:</label>
                    <textarea name="syllabus" class="form-control"><?php echo $syllabus ?></textarea>
                    <br>
                    <button type="submit" name="submit" value="update_course" class="btn form-submit" style="color:white;background:var(--primary);">Update Course</button>
                </form>

            </div>
        </div>
    </div>



    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
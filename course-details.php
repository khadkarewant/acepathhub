<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    
    
    if(isset($_GET['course_id']) && $_GET['course_id'] !== ""){

        $get_course_details = mysqli_query($conn, "SELECT * FROM `courses` WHERE `id` = '".$_GET['course_id']."' ");
        if(mysqli_num_rows($get_course_details) == 0){
            header("Location: home.php");
        }
        
        while ($row = mysqli_fetch_assoc($get_course_details)) {
            $course_id = $row['id'];
            $course_name = $row['name'];
            $description = $row['description'];
            $syllabus =  $row['syllabus'];
            // $cover_image =  $row['cover_img'];
            $created_by = $row['created_by'];
            $created_on =  $row['created_on'];
            $created_at =  $row['created_at'];
            $modified_by =  $row['modified_by'];
            $modified_on =  $row['modified_on'];
            $modified_at =  $row['modified_at'];
            $status = $row['status'];
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
    <title>Course Details</title>
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
                <h3><strong>Course Name: </strong><?php echo $course_name; ?></h3>
                <?php
                    if($modify_course == "true"){
                        echo'
                            <button class="bg-warning" onclick="window.location.href=\'update-course.php?course_id='.$_GET['course_id'].'\'">Update</button>
                            <br>
                            <br>
                        ';
                    }
                ?>

                <strong>Description: </strong><?php echo $description; ?> <br> <br>
                <strong><u>Syllabus:</u> </strong> <br> <br><?php echo $syllabus; ?> <br>
                <hr>
                <div>
                    Topics: <br>
                    <?php
                        if($create_topic == "true"){
                            echo'
                                <button class="float-right bg-success text-light" onclick="window.location.href=\'add-topic.php?course_id='.$_GET['course_id'].'\'">Add New</button> 
                            
                            ';
                        }
                    ?>
                </div>
                <br>
                <table class="table table-bordered table-hovered">
                    <thead>
                        <tr>
                            <th>S.N</th>
                            <th>Topic Name</th>
                            <?php
                                if($type == "admin"){
                                    echo'
                                        <th>Verified MCQs</th>
                                        <th>Live MCQs</th>
                                    ';
                                }
                            ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $get_topics = mysqli_query($conn, "SELECT * FROM `topics` WHERE `course_id` = '".$course_id."' ");
                            if (mysqli_num_rows($get_topics)>0) {
                                while ($row = mysqli_fetch_assoc($get_topics)) { 
                                    $get_verified_topic = mysqli_query($conn, "SELECt * FROM `mcqs` WHERE `topic_id` ='".$row['id']."' AND `verified` = 'true' ")   ;
                                    $verified_mcqs = mysqli_num_rows($get_verified_topic);

                                    $get_live_topic = mysqli_query($conn, "SELECt * FROM `mcqs` WHERE `topic_id` ='".$row['id']."' AND `status` = 'live' ")   ;
                                    $live_mcqs = mysqli_num_rows($get_live_topic);
                                    
                                    echo'
                                        <tr>
                                            <td>'.$row['id'].'</td>
                                            <td>'.$row['name'].'</td>
                                    ';
                                    if($type == "admin"){
                                        echo'
                                            <td>'.$verified_mcqs.'</td>
                                            <td>'.$live_mcqs.'</td>
                                        ';
                                    }

                                    echo'
                                        <td>
                                    ';
                                        if($create_mcq == "true"){
                                            echo'
                                                <button class="bg-success text-light" onclick="window.location.href=\'add-mcq.php?topic_id='.$row['id'].'\'">Add MCQ</button>
                                            ';
                                        }
                                    if($view_topic == "true"){
                                        echo'
                                            <button class="bg-info" onclick="window.location.href=\'topic-details.php?topic_id='.$row['id'].'\'">Details</button>
                                        ';
                                    }
                                    echo'
                                        </td>
                                        </tr>
                                    ';
                                }
                            }else{
                                echo'
                                    <tr>
                                        <td colspan="4">No Topic Available</td>
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
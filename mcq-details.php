<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($view_mcq == "false"){
        header("Location: courses.php");
    }
    
    if(isset($_GET['mcq_id']) && $_GET['mcq_id'] !== ""){

        $get_mcq_details = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `id` = '".$_GET['mcq_id']."' ");

        if(mysqli_num_rows($get_mcq_details) == 0){
            header("Location: home.php");
        }

        while ($row = mysqli_fetch_assoc($get_mcq_details)) {
            $question_weight = $row['question_weight'];
            $mcq_id = $row['id'];
            $topic_id = $row['topic_id'];
            // $cover_image =  $row['cover_img'];
            $created_by = $row['created_by'];
            $created_on =  $row['created_on'];
            $created_at =  $row['created_at'];
            $status = $row['status'];
            $remarks = $row['remarks'];
            $verification = $row['verified'];

            $get_topic_name = mysqli_query($conn, "SELECT `name`,`course_id` FROM `topics` WHERE `id` = '".$topic_id."'");

            foreach ($get_topic_name as $key => $value) {
                $topic_name = $value['name'];
                $course_id = $value['course_id'];
            }

            $get_course_name = mysqli_query($conn, "SELECT `name` FROM `courses` WHERE `id` = '".$course_id."'");

            foreach ($get_course_name as $key => $value) {
                $course_name = $value['name'];
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
    <title>Mcq Details</title>
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
            <div class="col-md-12 mt-2 table-responsive">
                
                <table class="table table-hovered">
                    <thead>
                        <tr>
                            <th><strong>Course: </strong></th>
                            <td><?php echo $course_name."(".$course_id.")"; ?></td>
                        </tr>
                        <tr>
                            <th><strong>Topic: </strong></th>
                            <td><?php echo $topic_name."(".$topic_id.")"; ?></td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th> <strong>Remarks: </strong></th>
                            <td> <?php echo $remarks; ?></td>
                        </tr>
                        <tr>
                            <th> <strong>Total Question:: </strong></th>
                            <td> <?php echo $question_weight; ?></td>
                        </tr>
                    </tbody>
                </table>

                <?php if ($modify_mcq == "true" && $type == "admin"): ?>
                    <button class="bg-info" onclick="window.location.href='update-mcq.php?mcq_id=<?php echo (int)$mcq_id; ?>'">
                        Update MCQ
                    </button>

                    <form action="delete-mcq.php" method="POST" style="display:inline;">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="mcq_id" value="<?php echo (int)$mcq_id; ?>">
                        <button type="submit" class="bg-danger" onclick="return confirm('Delete this MCQ permanently?');">
                            Delete MCQ
                        </button>
                    </form>
                <?php endif; ?>

                
                <?php if ($verify_mcq == "true" && $verification == "false"): ?>
                    <form action="verify-mcq.php" method="POST" style="display:inline;">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="mcq_id" value="<?php echo (int)$mcq_id; ?>">
                        <input type="hidden" name="status" value="true">
                        <button type="submit" class="bg-warning" onclick="return confirm('Verify this MCQ?');">
                            Verify MCQ
                        </button>
                    </form>
                <?php endif; ?>

                <hr>

                <?php
                    if($create_mcq == "true"){
                        echo'
                            <button class="bg-info" onclick="window.location.href=\'add-question.php?mcq_id='.$mcq_id.'\'">Add Question</button>
                        ';
                    }
                ?>


                <h4>Question/s:</h4>
                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>ID</th>
                                <th>Question</th>
                                <th>Opt A</th>
                                <th>Opt B</th>
                                <th>Opt C</th>
                                <th>Opt D</th>
                                <th>Answer</th>

                                <?php
                                    if($verification == "true" && $type == "admin"){
                                        echo'
                                            <th>Action</th>
                                        ';
                                    }
                                ?>
                            </tr>
                            <?php
                                $get_questions = mysqli_query($conn, "SELECT * FROM `questions` WHERE `mcq_id` = '".$mcq_id."' ");
                                echo'
                                ';
                                while ($row = mysqli_fetch_assoc($get_questions)) {
                                    echo'
                                        <tr>
                                            <td>'.$row['id'].'</td>
                                            <td> <div class="form-control"> '.$row['question'].'</div></td>
                                            
                                            <td>'.$row['option_a'].'</td>
                                            <td>'.$row['option_b'].'</td>
                                            <td>'.$row['option_c'].'</td>
                                            <td>'.$row['option_d'].'</td>
                                            <td>'.$row['answer'].'</td>
                                            <td>
                                    ';

                                    
                                    if($modify_topic == "true"){
                                        echo'
                                            <button onclick="window.location.href=\'update-question.php?question_id='.$row['id'].'\'" style="background:green;color:white;">Update Question</button>

                                        ';
                                    }
                                    echo'
                                            </td>
                                        </tr>
                                    ';
                                }
                                
                            ?>
                        </tbody>
                    </table>
                </div>
                
                
               
            </div>
        </div>
    </div>
    <br>
    
    

    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
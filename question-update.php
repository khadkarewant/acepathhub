<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($modify_mcq !== "true"){
        header("Location: courses.php");
    }

    if(isset($_GET['mcq_id']) && $_GET['mcq_id'] !== ""){
        
        $mcq_id = $_GET['mcq_id'];

        $get_data = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `id` = '".$mcq_id."'");
        
        if(mysqli_num_rows($get_data) == 0){
            header("Location: home.php");
        }
        
        while ($row = mysqli_fetch_assoc($get_data)) {
            $remarks =  $row['remarks'];

            $get_topic_name = mysqli_query($conn, "SELECT * FROM `topics` WHERE `id` ='".$row['topic_id']."' ");

            foreach ($get_topic_name as $key => $value) {
                $topic_name = $value['name'];
                $topic_id = $value['id'];
                $course_id = $value['course_id'];
            }

        }
        if(isset($_GET['submit']) && $_GET['submit'] == "update_mcq"){

            $mcq_id = $_GET['mcq_id'];
            $new_topic_id = $_GET['new_topic_id'];
            $new_remarks = null;
            if($_GET['remarks'] == ""){
                $new_remarks = null;
            }else{
                $new_remarks = $_GET['remarks'];
            }
            
            $update_mcq = mysqli_query($conn, "UPDATE `mcqs` SET `remarks` = '".$new_remarks."', `topic_id` = '".$new_topic_id."' WHERE `id` = '".$mcq_id."' ");
    
            if ($update_mcq) {
                echo'
                    <script>
                        alert("MCQ Updated successfully.");
                        window.location.href="mcq-details.php?mcq_id='.$mcq_id.'";
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
    <title>Update MCQ</title>
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

                <h2>Update MCQ</h2>
                <form class="form p-1" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">

                    <label>Topic</label>
                    <select name="new_topic_id" class="form-control" required>
                        
                        <option  value="<?php echo $topic_id ?>" selected><?php echo $topic_name.'('.$topic_id.')' ?></option>

                        <?php
                            $get_all_topic = mysqli_query($conn, "SELECT * FROM `topics` WHERE `course_id` = '$course_id' ");
                            while ($row = mysqli_fetch_assoc($get_all_topic)) {
                                echo '
                                    <option value="'.$row['id'].'">'.$row['name'].'('.$row['id'].')</option>
                                ';
                            }

                        ?>
                    </select>
        
                    <input type="text" hidden name="mcq_id" value="<?php echo $mcq_id ?>" required/>


                    <label>Remarks</label>
                    <input type="text" class="form-control" value="<?php echo $remarks; ?>" name="remarks">
                    <br>
                    <button type="submit" name="submit" value="update_mcq" class="btn form-submit" style="background:var(--primary);color:white;">Update MCQ</button>
        
                </form>
            </div>
        </div>
    </div>

    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
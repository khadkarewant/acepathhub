<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($view_topic !== "true"){
        header("Location: courses.php");
    }

    if(isset($_GET['topic_id']) && $_GET['topic_id'] !== ""){

        $get_topic_details = mysqli_query($conn, "SELECT * FROM `topics` WHERE `id` = '".$_GET['topic_id']."' ");

        if(mysqli_num_rows($get_topic_details) == 0){
            header("Location: home.php");
        }
        
        while ($row = mysqli_fetch_assoc($get_topic_details)) {
            $topic_id = $row['id'];
            $topic_name = $row['name'];
            $description = $row['description'];
            $created_by = $row['created_by'];
            $created_on =  $row['created_on'];
            $created_at =  $row['created_at'];
            $modified_by =  $row['modified_by'];
            $modified_on =  $row['modified_on'];
            $modified_at =  $row['modified_at'];
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
    <title>Topic Details</title>
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
                <h3><strong>Topic Name: </strong><?php echo $topic_name; ?> <br></h3>
                <?php
                    if($modify_topic =="true" ){
                        echo'
                            <button class="bg-warning" onclick="window.location.href=\'update-topic.php?topic_id='.$_GET['topic_id'].'\'">Update</button>
                            <br>
                            <br>
                        ';
                    }
                ?>
                <strong>Description: </strong><?php echo $description; ?> <br> <br>
                <hr>
                <div>
                    MCQs:
                    <br>
                    <?php
                        if($create_mcq == "true" ){
                            echo'
                                <button class="float-" style="background:var(--primary);color:white" onclick="window.location.href=\'add-mcq.php?topic_id='.$_GET['topic_id'].'\'">Add New</button> 
                                <br>
                                <br>
                            
                            ';
                        }
                    ?>
                </div>
                <table class="table table-bordered table-hovered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Main Question</th>
                            <?php
                                if($view_mcq == "true" ){
                                    echo'
                                        <th>Action</th>
                                    ';
                                }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $get_mcq = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `topic_id` = '".$topic_id."' AND `verified` = 'true' AND `status` = 'live'");
                            if (mysqli_num_rows($get_mcq)>0) {
                                while ($row = mysqli_fetch_assoc($get_mcq)) {

                                    $get_question = mysqli_query($conn, "SELECT * FROM `questions` WHERE `mcq_id` = '".$row['id']."' LIMIT 1");

                                    foreach ($get_question as $key => $value) {
                                        $question = $value['question'];
                                    }      
                                    echo'
                                        <tr>
                                            <td>'.$row['id'].'</td>
                                            <td>'.$question.'</td>
                                            <td>';
                                                if ($row['status'] == "draft" && $row['verified'] == "true" && ($type == "super_admin" || $type == "admin")) {
                                                ?>
                                                <form action="mcq-status-change.php" method="POST" style="display:inline;">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="mcq_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="status" value="live">
                                                    <button type="submit" class="bg-warning" onclick="return confirm('Make this MCQ live?');">
                                                        Make Live
                                                    </button>
                                                </form>
                                                <?php
                                            }
                                            // Make Offline (POST + CSRF)
                                            elseif ($row['status'] == "live" && $row['verified'] == "true" && ($type == "super_admin" || $type == "admin")) {
                                                ?>
                                                <form action="mcq-status-change.php" method="POST" style="display:inline;">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="mcq_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="status" value="draft">
                                                    <button type="submit" class="bg-success" onclick="return confirm('Make this MCQ offline?');">
                                                        Make Offline
                                                    </button>
                                                </form>
                                                <?php
                                            }

                                            // Verify (POST + CSRF) - standardized to verify-mcq.php
                                            if ($row['verified'] == "false" && ($type == "super_admin" || $type == "admin")) {
                                                ?>
                                                <form action="verify-mcq.php" method="POST" style="display:inline;">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="mcq_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="status" value="true">
                                                    <button type="submit" class="bg-warning" onclick="return confirm('Verify this MCQ?');">
                                                        Verify
                                                    </button>
                                                </form>
                                                <?php
                                            }

                                            // Data entry indicator (read-only)
                                            if ($type == "data_entry" && $row['verified'] == "false") {
                                                echo '<i class="text-danger">unverified</i>';
                                            }

                                            // Details (GET is fine)
                                            if ($view_mcq == "true") {
                                                ?>
                                                <button class="bg-info" onclick="window.location.href='mcq-details.php?mcq_id=<?php echo (int)$row['id']; ?>'">
                                                    Details
                                                </button>
                                                <?php
                                            }
                                            echo'
                                            </td>
                                        </tr>
                                    ';
                                }
                            }else{
                                echo'
                                    <tr>
                                        <td colspan="4">No MCQ Available</td>
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
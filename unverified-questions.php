<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if(!isset($_GET['topic_id'])){
        header("Location: unverified-mcqs.php");
    }

    $get_topic_name = mysqli_query($conn, "SELECT * FROM `topics` WHERE `id` = '".$_GET['topic_id']."'");

    if(mysqli_num_rows($get_topic_name) == 0){
        header("Location: home.php");
    }

    foreach ($get_topic_name as $key => $value) {
        $topic_name = $value['name'];
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unverified Mcq</title>
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
            <div class="col-md-12 p-1 table-responsive">
                
                <h5><strong>Unverified MCQs for: <?php echo $topic_name; ?></strong></h3>
            
                <table class="table">
                    <thead>
                        <tr>
                            <th>Q. ID</th>
                            <th>Question</th>
                           
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $get_mcqs = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `verified` = 'false' AND `topic_id` = '".$_GET['topic_id']."' LIMIT 159");

                            if (mysqli_num_rows($get_mcqs)>0) {
                                while ($row = mysqli_fetch_assoc($get_mcqs)) {
                                    
                                    $get_question = mysqli_query($conn, "SELECT * FROM `questions` WHERE `mcq_id` = '".$row['id']."' LIMIT 1");
                                    foreach ($get_question as $key => $value) {
                                        $question = $value['question'];
                                    }
                                    echo'
                                        <tr>
                                            <td>'.$row['id'].'</td>
                                            <td>'.$question.'</td>
                                            <td>
                                    ';
                                   
                                    echo'
                                        <button class="btn" style="background:var(--primary);color:white;" onclick="window.location.href=\'mcq-details.php?mcq_id='.$row['id'].'\'">Details</button>
                                            </td>
                                        </tr>
                                    ';

                                }
                            }else{
                                echo'
                                    <tr>
                                        <td colspan="4">No Courses Available</td>
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
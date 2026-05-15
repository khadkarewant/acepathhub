<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");
    if($type == "student" && $type == "data_entry"){
        header("Location: home.php");
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft MCQs</title>
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
            <div class="col-md-12">
                <?php
                    $get_topic_order = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `verified` = 'true' AND `status` = 'draft' GROUP BY `topic_id`");
                    if(mysqli_num_rows($get_topic_order)>0){
                        while ($row = mysqli_fetch_assoc($get_topic_order)) {
    
                            $get_topic_name = mysqli_query($conn, "SELECT * FROM `topics` WHERE `id` = '".$row['topic_id']."' ");
                            
                            foreach ($get_topic_name as $key => $value) {
                                
                                $count_drafted_mcqs = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `topic_id` = '".$row['topic_id']."' AND `verified` ='true' AND `status` = 'draft' ");

                                $drafted_mcqs = mysqli_num_rows($count_drafted_mcqs);

                                if($drafted_mcqs > 0){
                                    echo '
                                        <div 
                                            onclick="window.location.href=\'drafted-mcqs-details.php?topic_id='.$row['topic_id'].'\'" 
                                        ';
                                            
                                   
                                        
                                    echo' style="padding:2px 10px 0px 5px;cursor:pointer;font-weight:700">
                                        '.$value['name'].'
                                    ';
    
                                    if($drafted_mcqs !==0){
                                        echo'(';
                                        echo $drafted_mcqs;
                                        echo' Drafted MCQ/s)';
                                    }
                                    echo'
                                        </div>
                                        <hr>
                                    ';
                                }
                                
                            }
    
                        }
                    }else{
                        echo'
                            <div class="text-danger">No Draft MCQs Available.</div>
                        ';
                    }

                ?>
            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
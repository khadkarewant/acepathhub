<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");
    require_once "src/security/csrf.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification</title>
    <?php
        include("src/inc/links.php");
    ?>
</head>
<body>
    <?php
       include("src/inc/header.php");
    ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 p-2">
                <h3 style="color:var(--primary)">Notification:</h3>
                <?php
                    if($modify_notification == "true"){
                        ?>
                        <form method="POST" action="mark-read-notification.php" style="display:inline;">
                            <?= csrf_input(); ?>
                            <button type="submit" class="btn text-light" style="background:var(--primary);">
                                Mark read
                            </button>
                        </form>
                        <?php
                    }
                ?>
            </div>
            <div class="col-md-12 rounded p-2 mb-3" style="background:rgba(22, 89, 235, 0.2)">
         
                <?php  
                    $group_by_date = mysqli_query($conn, "SELECT * FROM `notification` WHERE `user_id` = '".$user_id."' GROUP BY `date` ORDER BY `date` DESC ");
                    foreach ($group_by_date as $key => $value) {

                        echo'
                            <div class="btn p-1 mt-1 mb-1" style="background:var(--primary);color:white; display:inline-block;">'.$value['date'].'</div>
                        '; 

                        $get_notice_data = mysqli_query($conn, "SELECT * FROM `notification` WHERE `user_id` = '".$user_id."' AND `date` ='".$value['date']."' ORDER BY `id` DESC LIMIT 50");
                
                        if(mysqli_num_rows($get_notice_data)>0){
                            while ($row = mysqli_fetch_assoc($get_notice_data)) {
                                if($row['status'] == "unread"){
                                    echo'
                                        <div class="rounded p-1 bg-secondary text-light">'.$row['notification'].'('.$row['time'].')</div>

                                    ';
                                }else{
                                    echo'
                                    <div class="rounded p-1">'.$row['notification'].'('.$row['time'].')</div>
                                    ';
                                }
                            }
                        } 

                        echo'
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
<?php
    http_response_code(410);
    exit('Gone');
    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($type !== "admin"){
        header("Location: home.php");
    }
    
    if(isset($_GET['id']) && isset($_GET['id'])){
        $student_id = $_GET['id'];

       

        $unblock_user = mysqli_query($conn, "UPDATE `users` SET `is_blocked` = 'false' WHERE `user_id` = '".$student_id."' ");
        

        if($unblock_user){
            header("Location: block-list.php");

            eixt();
        }
    }else{
        header("Location:products.php");
    }

    ob_end_flush();
?>
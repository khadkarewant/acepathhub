<?php
    ob_start();
    http_response_code(410);
    exit('Gone');   
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($type !== "admin"){
        header("Location: home.php");
        exit();
    }
    
    if(isset($_GET['product_id']) && isset($_GET['user_id'])){
        $product_id = $_GET['product_id'];
        $student_id = $_GET['user_id'];

       

        $block_user = mysqli_query($conn, "UPDATE `users` SET `is_blocked` = 'true' WHERE `user_id` = '".$student_id."' ");
        
        
        $delete_chat = mysqli_query($conn, "DELETE FROM `discussions` WHERE `user_id` = '".$student_id."' ");

        

        if($block_user){
            header("Location:discussion-user-list.php?product_id=".$product_id);

            exit();
        }
    }else{
        header("Location:products.php");
        exit();
    }

    ob_end_flush();
?>
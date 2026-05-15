<?php
    include("../db/db_conn.php");

    // check if txn no. is already inserte in the database

    $get_users = mysqli_query($conn, "SELECT * FROM `users` WHERE (`username` = '".$_GET['p']."' OR `first_name` =  '".$_GET['p']."' OR `phone` = '".$_GET['p']."') AND `type` = 'student' LIMIT 1 ");
    if(mysqli_num_rows($get_users) ==1){
        foreach ($get_users as $key => $value) {
            $agent = $value;
        }
        echo json_encode($agent);
    }else{
        $agent = "Not found";
    }

?>

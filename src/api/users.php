<?php
    include("../db/db_conn.php");

    // check if txn no. is already inserte in the database

    $get_users = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` LIKE '%".$_GET['p']."%' ");
    foreach ($get_users as $key => $value) {
        $users = $value;
    }

    echo json_encode($users);
?>

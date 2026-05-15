<?php

    date_default_timezone_set("Asia/Kathmandu");

    $username = "localhost";
    $user = "root";
    $pasword = "";
    $type="";
    $db_name = "quizmania_restore_test";


    // connect db and use current db
    $conn = mysqli_connect($username, $user, $pasword, $db_name);
?>
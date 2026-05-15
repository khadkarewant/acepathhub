<?php
http_response_code(410);
exit('Gone');
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");
    
    if($view_user !== "true"){
        header("Location: home.php");
        exit();
    }

    if(isset($_GET['product_id']) && $_GET['product_id'] !== ""){

        $get_product_name = mysqli_query($conn, "SELECT `name` FROM `products` WHERE `id` = '".$_GET['product_id']."' ");

        if(mysqli_num_rows($get_product_name) == 0){
            header("Location: home.php");
            exit();
        }

        foreach ($get_product_name as $key => $value) {
            $product_name = $value['name'];
        }
        
        $check_product_chat = mysqli_query($conn, "SELECT * FROM `discussions` WHERE `product_id` = '".$_GET['product_id']."' ");

        if(mysqli_num_rows($check_product_chat) == 0){
            header("Location: home.php");
            exit();
        }

    }else{
        header("Location: home.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users In dicussion</title>
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
            <h4>List of users from <?php echo $product_name; ?>: </h4>

            <table class="table" id="datatable">
                <thead>
                    <tr>
                        <th>User Id</th>
                        <th>Username</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $get_user_list = mysqli_query($conn, "SELECT * FROM `discussions` WHERE `product_id` = '".$_GET['product_id']."' GROUP BY `user_id` ");

                        foreach ($get_user_list as $key => $value) {

                            $get_username = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` = '".$value['user_id']."' AND `type`= 'student' ");
                            while ($row = mysqli_fetch_assoc($get_username)) {
                                $student_username = $row['username'];
                            }

                            if(mysqli_num_rows($get_username)>0){
                            ?>
                                <tr>
                                    <td><?php $value['user_id'] ?></td>
                                    <td><?php $student_username ?></td>
                                    <td>
                                        <!-- <button class="bg-danger" onclick="window.location.href=\'block-user-chat.php?user_id='.$value['user_id'].'&product_id='.$value['product_id'].'\'">Block</button> -->
                                    </td>
                                </tr>
                            <?php
                            }


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
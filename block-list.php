<?php
http_response_code(410);
exit('Gone');
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($view_user_list !== "true"){
        header("Location: home.php");
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block List</title>
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
            <div class="col-md-12 p-2">
                <h4>Blocked Users:</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="datatable" id="datatable">
                        <thead>
                            <tr>
                                <th>User Id</th>
                                <th>User Name</th>
                                <th>@username</th>
                                <th>Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $get_users = mysqli_query($conn, "SELECT * FROM `users` WHERE `is_blocked` = 'true'");
                                while ($row = mysqli_fetch_assoc($get_users)) {
                                    echo'
                                        <tr>
                                            <td>'.$row['user_id'].'</td>
                                            <td>'.$row['first_name'].' '.$row['middle_name'].' '.$row['last_name'].'</td>
                                            <td>'.$row['username'].'</td>
                                            <td>'.$row['phone'].'</td>
                                            <td>';

                                            if($view_user == "true"){
                                                ?>
                                                    <button class="bg-primary text-light" onclick="window.location.href=\'user-details.php?id='.$row['user_id'].'\'">View</button>

                                                    <!-- <button class="bg-success text-light" onclick="window.location.href=\'unblock.php?id='.$row['user_id'].'\'">Unblock</button> -->
                                                <?php
                                            }
                                            echo'       
                                            </td>
                                        </tr>
                                    ';
                                }
                            ?>
                           
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php
    include("src/inc/footer.php");
?>
</body>
</html>
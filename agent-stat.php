<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($type !== "admin"){
        header("Location: home.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Stats</title>
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
            
            <h4>Agent Statics:</h4>
                   
            <table class="table" id="datatable">
                <thead>
                    <tr> 
                        <th>Agent Id</th>
                        <th>Username</th> 
                        <th>Total Credit</th>
                        <th>Credit Remaining</th>
                        <th>Total Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                        $get_agent_stat = mysqli_query($conn ,"SELECT * FROM `agent_stat`");

                        if (mysqli_num_rows($get_agent_stat)>0) {
                            foreach ($get_agent_stat as $key => $value) {
                                // get_username 

                                $get_username = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` ='".$value['agent_id']."' ");
                                while ($row =mysqli_fetch_assoc($get_username)) {
                                    $agent_username = $row['username'];
                                }

                                echo'
                                    <tr>
                                        <td>'.$value['id'].'</td>
                                        <td>'.$agent_username.'</td>
                                        <td>'.$value['product_credited'].'</td>
                                        <td>'.$value['remaining_product_credit'].'</td>
                                        <td>'.$value['payment_deposited'].'</td>
                                    </tr>
                                ';
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
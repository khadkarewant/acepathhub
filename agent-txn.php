<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($type !== "admin" && $type !=="agent"){
        header("Location: home.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent TXN</title>
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
            <?php
                if($type == "agent"){
                    echo'
                        <h4>Your Transactions:</h4>
                    ';
                }else{
                    echo'
                        <h4>Agent Transactions:</h4>
                    ';
                }
            ?>

            <table class="table" id="datatable">
                <thead>
                    <tr>
                        <?php
                            if($type !== "agent"){
                                echo'
                                    <th>Agent Id</th>
                                    <th>Username</th>
                                ';
                            }
                        ?>
                        <th>TXN No</th>
                        <th>TXN Mode</th>
                        <th>Amount</th>
                        <th>Credit</th>
                        <th>Date</th>
                        <th>Time</th>
                        <?php
                            if($type !== "agent"){
                                echo'
                                    <th>refrence_no</th>
                                ';
                            }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php

                        if($type == "agent"){
                            $get_agent_txn = mysqli_query($conn ,"SELECT * FROM `agent_txn` WHERE `agent_id` = '".$user_id."' ");
                        }else{
                            $get_agent_txn = mysqli_query($conn ,"SELECT * FROM `agent_txn`");
                        }

                        if (mysqli_num_rows($get_agent_txn)>0) {
                            foreach ($get_agent_txn as $key => $value) {
                                // get_username 

                                $get_username = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` ='".$value['agent_id']."' ");
                                while ($row =mysqli_fetch_assoc($get_username)) {
                                    $agent_username = $row['username'];
                                }

                                echo'
                                    <tr>
                                    ';
                                    if($type !== "agent"){
                                        echo'
                                            <td>'.$value['id'].'</td>
                                            <td>'.$agent_username.'</td>
                                        ';
                                    }
                                echo'
                                        <td>'.$value['txn_no'].'</td>
                                        <td>'.$value['txn_mode'].'</td>
                                        <td>'.$value['amount'].'</td>
                                        <td>'.$value['product_credit'].'</td>
                                        <td>'.$value['date'].'</td>
                                        <td>'.$value['time'].'</td>
                                        ';
                                    if($type !== "agent"){
                                    echo'
                                        <td>'.$value['refrence_no'].'</td>
                                    ';
                                }
                                echo'
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
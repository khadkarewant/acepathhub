<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");
    // echo password_hash('Eat2@Live', PASSWORD_DEFAULT);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <?php
        include("src/inc/links.php");
    ?>
    <meta property="og:image" content="https://quizmania.org/src/img/lsw.png">
    <meta name="twitter:image" content="https://quizmania.org/src/img/lsw.png">
    <style>
        .info_card{
            display:inline-block;
            margin:10px; 
            padding:10px;
            border-radius:5px;
            border:1px solid var(--primary);
            text-align:center;
            color:var(--primary);
        }

        .info_card:hover{
            color:white;
            background:var(--primary);
            box-shadow:5px 5px 10px grey;
            cursor:pointer;
        }
    </style>
    
</head>
<body>
    <?php
       include("src/inc/header.php");
    ?>
    <div class="container-fluid">
        <div class="row">
        
        <div class="row">
            <?php
            if($type == "admin" || $type == "teacher"){
            ?>
                <div class="col-md-8">
                    <h4 style="color:var(--primary);">Today's Statics:</h4>
                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT * FROM `users` WHERE `registered_on` = '".date("Y-m-d")."' ");

                            echo'
                                <h1>'.mysqli_num_rows($get_data).'</h1>
                                <div>New Users</div>
                            ';
                        ?>
                    </div>

                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `purchased_on` = '".date("Y-m-d")."' AND `product_id` != '11' ");

                            echo'
                                <h1>'.mysqli_num_rows($get_data).'</h1>
                                <div>Product Sold</div>
                            ';
                        ?>
                    </div>

                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT * FROM `exam_stats` WHERE `attempted_date` LIKE '%".date("Y-m-d")."%' ");

                            echo'
                                <h1>'.mysqli_num_rows($get_data).'</h1>
                                <div>Exam Taken</div>
                            ';
                        ?>
                    </div>

                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT SUM(amount) FROM `purchased_products` WHERE `purchased_on` = '".date("Y-m-d")."' ");

                            foreach ($get_data as $key => $value) {
                                $today_revenue = $value['SUM(amount)'];
                            }

                            echo'
                                <h1>'.$today_revenue.'/-</h1>
                                <div>Earnings</div>
                            ';
                        ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <h4 style="color:var(--primary);">Quizmania Statics:</h4>
                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT * FROM `users`");

                            echo'
                                <h1>'.mysqli_num_rows($get_data).'</h1>
                                <div>Total Users</div>
                            ';
                        ?>
                    </div>

                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT * FROM `topics`");

                            echo'
                                <h1>'.mysqli_num_rows($get_data).'</h1>
                                <div>Total Topics</div>
                            ';
                        ?>
                    </div>

                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT * FROM `mcqs`");

                            echo'
                                <h1>'.mysqli_num_rows($get_data).'</h1>
                                <div>Total MCQs</div>
                            ';
                        ?>
                    </div>

                    <div class="info_card">
                        <?php 
                            $get_data = mysqli_query($conn, "SELECT * FROM `questions`");

                            echo'
                                <h1>'.mysqli_num_rows($get_data).'</h1>
                                <div>Total Questions</div>
                            ';
                        ?>
                    </div>
                </div>

            <?php
            }
            ?>
           

            <?php
            if($type == "student"){
            ?>

                <div class="col-md-12 mt-3">
                    <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                
                        <!-- Purchased Mocks -->
                        <button class="btn btn-sm btn-outline-primary same-btn"
                                onclick="window.location.href='my-products.php'">
                            Take Exam
                        </button>
                
                        <!-- Full Course -->
                        <button class="btn btn-sm btn-outline-primary same-btn"
                                onclick="window.location.href='course.php'">
                            Full Course
                        </button>
                        
                        <!-- Downloads -->
                        <button class="btn btn-sm btn-outline-primary same-btn"
                                onclick="window.location.href='downloads.php'">
                            Downloads
                        </button>
                
                    </div>
                </div>



                
                <!--Mock Test Top Pick -->

                <div class="col-md-12 mt-2">
                <h4 style="text-align:center">Student's top pick (Mock Test)</h4>
                <hr style="width:150px;margin:10px auto;border:3px solid var(--primary)">
                <div class="row justify-content-center">
            
                    <?php
                    $sql = "
                        SELECT 
                            p.id,
                            p.name,
                            COUNT(pp.id) AS total_purchases
                        FROM purchased_products pp
                        INNER JOIN products p ON p.id = pp.product_id
                        WHERE p.is_practice = 0
                          AND p.status = 'live'
                          AND p.name NOT LIKE '%demo%'
                          AND pp.status = 'active'
                        GROUP BY p.id
                        ORDER BY total_purchases DESC
                        LIMIT 1
                    ";
            
                    $result = mysqli_query($conn, $sql);
            
                    if ($product = mysqli_fetch_assoc($result)) {
                    ?>
                        <div class="col-md-4 mb-3 p-3 border shadow rounded text-center">
                            <h5><?= htmlspecialchars($product['name']) ?></h5>
            
                            <div><strong>Product ID:</strong> <?= $product['id'] ?></div>
                            <div><strong>Price:</strong> ₹100</div>
                            <!--<div><strong>Purchased:</strong> <?= $product['total_purchases'] ?> times</div>-->
            
                            <button class="btn text-light mt-2"
                                    style="background:var(--primary);"
                                    onclick="window.location.href='product-details.php?product_id=<?= $product['id'] ?>'">
                                View
                            </button>
            
                            <button class="btn btn-success mt-2"
                                    onclick="window.open(
                                        'https://wa.me/9779700186061?text=<?= rawurlencode(
                                            'I want to buy the product: '.$product['name'].' and My username is: '.$username
                                        ) ?>',
                                        '_blank'
                                    );">
                                Purchase
                            </button>
                        </div>
                    <?php } else { ?>
                        <p class="text-center">No purchases yet.</p>
                    <?php } ?>
            
                </div>
            </div>
            </div>
            
             <!--Courses Top Pick -->
            <div class="col-md-12 mt-2">
                <h4 style="text-align:center">Student's top pick (Full Course)</h4>
                <hr style="width:150px;margin:10px auto;border:3px solid var(--primary)">
                <div class="row justify-content-center">
            
                    <?php
                    $sql = "
                        SELECT 
                            p.id,
                            p.name,
                            COUNT(pp.id) AS total_purchases
                        FROM purchased_products pp
                        INNER JOIN products p ON p.id = pp.product_id
                        WHERE p.is_practice = 1
                          AND p.status = 'live'
                          AND p.name NOT LIKE '%demo%'
                          AND pp.status = 'active'
                        GROUP BY p.id
                        ORDER BY total_purchases DESC
                        LIMIT 1
                    ";
            
                    $result = mysqli_query($conn, $sql);
            
                    if ($product = mysqli_fetch_assoc($result)) {
                    ?>
                        <div class="col-md-4 mb-3 p-3 border shadow rounded text-center">
                            <h5><?= htmlspecialchars($product['name']) ?></h5>
            
                            <div><strong>Product ID:</strong> <?= $product['id'] ?></div>
                            <div><strong>Price:</strong> ₹999</div>
                            <!--<div><strong>Purchased:</strong> <?= $product['total_purchases'] ?> times</div>-->
            
                            <button class="btn text-light mt-2"
                                    style="background:var(--primary);"
                                    onclick="window.location.href='learn.php'">
                                View Courses
                            </button>
                        </div>
                    <?php } else { ?>
                        <p class="text-center">No purchases yet.</p>
                    <?php } ?>
            
                </div>
            </div>


        
            <?php
            }
            ?>
        </div>
    </div>
    

    
    

    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
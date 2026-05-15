<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if($type !== "student"){
        header("Location: home.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products</title>
    <?php include("src/inc/links.php"); ?>

    <style>
        .product-card{
            background:#ffffff;
            border-radius:18px;
            box-shadow:0px 4px 10px rgba(0,0,0,0.08);
            padding:20px;
            transition:0.2s;
            height:100%;
        }
        .product-card:hover{
            transform:translateY(-4px);
            box-shadow:0px 8px 18px rgba(0,0,0,0.12);
        }
        .product-title{
            font-size:18px;
            font-weight:700;
            color:var(--primary);
        }
        .sets-box{
            background:#f1f5f9;
            border-radius:12px;
            padding:10px 15px;
            font-weight:600;
            display:inline-block;
            margin-top:8px;
        }
        .action-btn{
            width:100%;
            border-radius:10px;
            padding:10px;
            font-weight:600;
            margin-top:10px;
        }
        .loading-screen{
            display:none;
            text-align:center;
            margin-top:50px;
            padding:50px;
        }
    </style>

    <script>
        $(document).ready(function(){
            $(".attempt_exam").click(function(){
                $("#blank").hide();
                $(".loading-screen").show();
            });
        });
    </script>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="loading-screen">
    <progress></progress>
    <div> L O A D I N G ...</div>
</div>

<div class="container py-4" id="blank">
    <div class="row mb-3">
        <div class="col-md-12">
            <h3 style="color:var(--primary); font-weight:700;">My Products</h3>
        </div>
    </div>

    <div class="row g-4">
<?php
    $get_courses = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `user_id` = '".$user_id."' AND `status` = 'active' AND `product_id` < 12 AND `remaining_sets` > 0 ");

    if(mysqli_num_rows($get_courses) > 0){

        while($row = mysqli_fetch_assoc($get_courses)){

            $sets = $row['remaining_sets'];
            $get_product_details = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$row['product_id']."' ");

            foreach($get_product_details as $value){
                $name = $value['name'];
                $description = $value['description'];
                $price = $value['price'];
                $course_id = $value['course_id'];
            }
?>
        <div class="col-md-3 col-sm-6">
            <div class="product-card">
                <div class="product-title"><?php echo $name; ?></div>

                <div class="sets-box">Sets Remaining: <?php echo $sets; ?></div>

                <?php if($attempt_exam == "true"){ ?>

                    <button class="btn btn-primary action-btn attempt_exam" onclick="window.location.href='exam-guidelines.php?purchased_id=<?php echo $row['id']; ?>'">Attempt Exam</button>

                    <?php
                        $check_user_access = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` = '".$user_id."'");
                        foreach($check_user_access as $value){
                            if($value['is_blocked'] == "false"){ ?>
                                <button class="btn btn-danger action-btn" onclick="window.location.href='discussion.php?product_id=<?php echo $row['product_id']; ?>#last_msg'">Discussions</button>
                    <?php }} ?>

                <?php } ?>
            </div>
        </div>
<?php
        }
    } else {
?>
        <div class="col-md-12 text-center mt-5">
            <h5 class="text-danger">You do not have any products.</h5>
            <div>Purchase a product here:</div>
            <button class="btn text-light mt-2" style="background:var(--primary);" onclick="window.location.href='products.php'">Products</button>
        </div>
<?php } ?>
        <div class="col-md-12 text-primary mt-4 mb-4">
            <h3>Do you want to try our other products ?</h3>
            <h5>Try now:</h5>
            <button class="btn" onclick="window.location.href='products.php'" style="background:var(--primary); color:white;">Products &rightarrow;</button>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>

<script>
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };
</script>
</body>
</html>

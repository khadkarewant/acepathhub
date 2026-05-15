<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    
    if(isset($_GET['id']) && $_GET['id'] !== ""){

        $get_topic_details = mysqli_query($conn, "SELECT * FROM `question_patterns` WHERE `id` = '".$_GET['id']."' ");
        
        if(mysqli_num_rows($get_topic_details) == 0){
            header("Location: home.php"); exit;
        }
        
        while ($row = mysqli_fetch_assoc($get_topic_details)) {
            $pattern_id = $row['id'];

            $product_id = $row['product_id'];
            $pattern_name = $row['name'];
            $question_weight = $row['question_weight'];
        }

        $get_product_name = mysqli_query($conn, "SELECT `name` FROM `products` WHERE `id` = '".$product_id."' ");

        while ($row = mysqli_fetch_assoc($get_product_name)) {
            $product_name = $row['name'];
        }

    }else{
        header("Location: products.php"); exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Pattern Details</title>
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
                <h3><strong>Pattern Name: </strong><?php echo $pattern_name; ?> 
                <h6>Product Name: <?php echo $product_name ?>(<?php echo $product_id ?>)</h6>
                <br></h3>
                <?php
                    if($modify_product =="true" ){
                        echo'
                            <button class="bg-warning" onclick="window.location.href=\'update-question-pattern.php?pattern_id='.$_GET['id'].'\'">Update</button>
                            <br>
                            <br>
                        ';
                    }
                ?>
                <hr>
                <div>
                    Question Topics
                    <br>
                    <?php
                        if($create_product == "true" ){
                            echo'
                                <button class="float-" style="background:var(--primary);color:white" onclick="window.location.href=\'add-question-topic.php?pattern_id='.$_GET['id'].'\'">Add New</button> 
                                <br>
                                <br>
                            ';
                        }
                    ?>
                </div>
                <table class="table table-bordered table-hovered">
                    <thead>
                        <tr>
                            <th>Topic Id</th>
                            <th>Topic</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $get_question_topics = mysqli_query($conn, "SELECT * FROM `question_topics` WHERE `pattern_id` = '".$pattern_id."'");
                            if (mysqli_num_rows($get_question_topics)>0) {

                                while ($row = mysqli_fetch_assoc($get_question_topics)) {
                                    $question_topic_id = (int)$row['id'];
                                    $topic_id = (int)$row['topic_id'];

                                    $get_topic_name = mysqli_query($conn, "SELECT * FROM `topics` WHERE `id` = '".$row['topic_id']."' ");

                                    foreach ($get_topic_name as $key => $value) {
                                        $topic_name = $value['name'];
                                    }      
                                    echo'
                                        <tr>
                                            <td>'.$row['topic_id'].'</td>
                                            <td>'.$topic_name.'</td>
                                            <td>';
            
                                                if($modify_product == "true"){
                                                    ?>

                                                    <form method="POST" action="delete-question-topic.php" style="display:inline;">
                                                        <?= csrf_input(); ?>
                                                        <input type="hidden" name="question_topic_id" value="<?= $question_topic_id; ?>">
                                                        <input type="hidden" name="pattern_id" value="<?= (int)$pattern_id; ?>">
                                                        <button type="submit" class="bg-info">Delete</button>
                                                    </form>

                                                    <?php
                                                }
                                            echo'
                                            </td>
                                        </tr>
                                    ';
                                }
                            }else{
                                echo'
                                    <tr>
                                        <td colspan="4">No Question Pattern Available</td>
                                    </tr>
                                ';
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
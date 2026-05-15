<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

if(isset($_GET['product_id']) && $_GET['product_id'] !== ""){

    $get_product_details = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$_GET['product_id']."' ");

    if(mysqli_num_rows($get_product_details) == 0){
        header("Location: home.php"); exit;

    }

    while ($row = mysqli_fetch_assoc($get_product_details)) {
        $product_id = $row['id'];
        $course_id = $row['course_id'];
        $product_name = $row['name'];
        $description = $row['description'];
        $sets = $row['sets'];
        $price =  $row['price'];
        $level_1 =  $row['level_1'];
        $level_2 =  $row['level_2'];
        $created_by = $row['created_by'];
        $created_on =  $row['created_on'];
        $created_at =  $row['created_at'];
        $status = $row['status'];
        $is_practice = $row['is_practice'];

        $get_course_name = mysqli_query($conn, "SELECT * FROM `courses` WHERE `id` = '".$course_id."' ");
        foreach ($get_course_name as $key => $value) {
            $course_name = $value['name'];
        }   
    }

}else{
    header("Location: products.php"); exit;
}

// Handle Group Form Submission
if(isset($_POST['add_group'])){
    csrf_verify();
    $group_name = htmlentities($_POST['group_name']);
    $sort_order = intval($_POST['sort_order']);

    $insert_group = mysqli_query($conn, "INSERT INTO product_topic_groups (product_id, group_name, sort_order) 
                                        VALUES ('$product_id', '$group_name', '$sort_order')");
    if($insert_group){
        $msg = '<p style="color:green;">Group added successfully.</p>';
    } else {
        $msg = '<p style="color:red;">Failed to add group.</p>';
    }
}

// Handle Topic Mapping Submission
if(isset($_POST['save_group_topics'])){
    csrf_verify();
    $group_id = intval($_POST['group_id']);
    $selected_topics = $_POST['topics']; // array of topic_ids

    // Remove existing topics for this group
    mysqli_query($conn, "DELETE FROM product_topics WHERE product_id = '$product_id' AND group_id = '$group_id'");

    // Insert selected topics
    foreach($selected_topics as $topic_id){
        $topic_id = intval($topic_id);
        mysqli_query($conn, "INSERT INTO product_topics (product_id, group_id, topic_id) VALUES ('$product_id', '$group_id', '$topic_id')");
    }

    $msg_topics = '<p style="color:green;">Topics updated for group successfully.</p>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Details</title>
<?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 p-1 table-responsive">
            <table class="table">
                <tbody>
                    <tr>
                        <th>Product Name</th>
                        <td><?php echo $product_name ?></td>
                    </tr>
                    <tr>
                        <th>Course</th>
                        <td><?php echo $course_name ?></td>
                    </tr>
                    <tr>
                        <th>Level I Questions</th>
                        <td> <?php echo $level_1 ?></td>
                    </tr>
                    <tr>
                        <th>Level II Questions</th>
                        <td> <?php echo $level_2 ?></td>
                    </tr>
                    <tr>
                        <th>Negative Marking</th>
                        <td>Yes (20%)</td>
                    </tr>
                    <tr>
                        <th>Exam Sets Available</th>
                        <td> <?php echo $sets ?></td>
                    </tr>
                    <tr>
                        <th>Total Price (in NPR)</th>
                        <td> <?php echo $price ?>/-</td>
                    </tr>
                </tbody>
            </table>

            <?php
                if($type == "student"){
                    echo '
                    <button class="btn bg-success"
                            style="background:var(--primary);color:white;"
                            onclick="window.open(\'src/pdf/'.$product_id.'.pdf\', \'_blank\')">
                        View Syllabus
                    </button>
                    ';
                }

                if($modify_product == "true"){
                    echo'
                        <button class="bg-info" onclick="window.location.href=\'update-product.php?product_id='.$_GET['product_id'].'\'">Update Product</button>
                    ';
                }

                if($status == "draft" && $modify_product == "true"){
                ?>
                <form method="POST" action="product-status-change.php" style="display:inline;">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="product_id" value="<?= (int)$product_id; ?>">
                        <input type="hidden" name="status" value="live">
                        <button type="submit" class="bg-warning">Make Live</button>
                    </form>
                <?php
                }elseif($status == "live" && $modify_product == "true"){
                ?>
                    <form method="POST" action="product-status-change.php" style="display:inline;">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="product_id" value="<?= (int)$product_id; ?>">
                    <input type="hidden" name="status" value="draft">
                    <button type="submit" class="bg-success text-light">Make Offline</button>
                </form>
                <?php
                }
            ?>
        </div>

        <?php
        if($type == "admin"){
            ?>
            <div class="col-md-12 p-1 mt-4 table-responsive">
                <h6><?php echo ($is_practice == 1) ? "Topic Groups:" : "Question Pattern:"; ?></h6>

                <?php if($is_practice == 0): ?>
                    <!-- Existing Question Pattern Logic -->
                    <button class="bg-info" onclick="window.location.href='add-question-pattern.php?product_id=<?php echo $product_id ?>'">Add Question Pattern</button>
                    <hr>
                    <table class="table">
                        <thead>
                            <th>id</th>
                            <th>Pattern Name</th>
                            <th>No. of Topics</th>
                            <th>Question Weight</th>
                            <th>Action</th>
                        </thead>
                        <tbody>
                            <?php
                                $get_question_pattern = mysqli_query($conn, "SELECT * FROM `question_patterns` WHERE `product_id` = '".$product_id."' ");
                                while ($row = mysqli_fetch_assoc($get_question_pattern)) {
                                    $get_question_topics = mysqli_query($conn, "SELECT * FROM `question_topics` WHERE `pattern_id` = '".$row['id']."' ");
                                    $no_of_topics = mysqli_num_rows($get_question_topics);

                                    echo'
                                        <tr>
                                            <td>'.$row['id'].'</td>
                                            <td>'.$row['name'].'</td>
                                            <td>'.$no_of_topics.'</td>
                                            <td>'.$row['question_weight'].'</td>
                                            <td>
                                                <button style="background:var(--primary);color:white;" onclick="window.location.href=\'question-pattern-details.php?id='.$row['id'].'\'">View</button>
                                                <button class="bg-success text-light" onclick="window.location.href=\'update-question-pattern.php?pattern_id='.$row['id'].'\'">Update</button> ';
                                            ?>
                                                <form method="POST" action="delete-question-pattern.php" style="display:inline;">
                                                    <?= csrf_input(); ?>
                                                    <input type="hidden" name="pattern_id" value="<?= (int)$row['id']; ?>">
                                                    <input type="hidden" name="product_id" value="<?= (int)$product_id; ?>">
                                                    <button type="submit" style="background:red;color:white;">Delete</button>
                                                </form>
                                            <?php
                                                echo'
                                            </td>
                                        </tr>
                                    ';
                                }
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <!-- Practice Product: Add Groups Form -->
                    <?php if(isset($msg)) echo $msg; ?>
                    <form action="" method="POST" class="mb-3">
                        <?= csrf_input(); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <label>Group Name:</label>
                                <input type="text" name="group_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Sort Order:</label>
                                <input type="number" name="sort_order" class="form-control" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="add_group" class="btn btn-primary w-100">Add Group</button>
                            </div>
                        </div>
                    </form>

                    <!-- Display Existing Groups with Topics -->
                    <?php if(isset($msg_topics)) echo $msg_topics; ?>
                    <?php
                    $groups = mysqli_query($conn, "SELECT * FROM product_topic_groups WHERE product_id = '$product_id' ORDER BY sort_order");
                    while($g = mysqli_fetch_assoc($groups)){
                        $group_id = $g['id'];
                        echo '<div class="card mb-3">';
                        echo '<div class="card-header d-flex justify-content-between align-items-center">';
                        echo '<span>'.$g['group_name'].' (Sort Order: '.$g['sort_order'].')</span>';
                        echo '<button class="btn btn-sm btn-primary" onclick="document.getElementById(\'topics_form_'.$group_id.'\').classList.toggle(\'d-none\')">Add/Edit Topics</button>';
                        echo '</div>';

                        // Fetch assigned topics
                        $assigned = mysqli_query($conn, "SELECT t.name, t.id FROM product_topics pt 
                                                         JOIN topics t ON pt.topic_id = t.id 
                                                         WHERE pt.product_id = '$product_id' AND pt.group_id = '$group_id'");
                        $assigned_topics = [];
                        while($a = mysqli_fetch_assoc($assigned)){
                            $assigned_topics[$a['id']] = $a['name'];
                        }

                        // Show assigned topics
                        echo '<div class="card-body">';
                        if(count($assigned_topics) > 0){
                            echo '<p><strong>Topics:</strong> '.implode(', ', $assigned_topics).'</p>';
                        }else{
                            echo '<p><strong>Topics:</strong> None assigned</p>';
                        }

                        // Topic selection form (hidden by default)
                        echo '<form id="topics_form_'.$group_id.'" method="POST" class="d-none">';
                        echo csrf_input();
                        echo '<input type="hidden" name="group_id" value="'.$group_id.'">';
                        echo '<select name="topics[]" class="form-control mb-2" multiple required>';
                        $topics = mysqli_query($conn, "SELECT * FROM topics WHERE course_id = '$course_id' ORDER BY id");
                        while($topic = mysqli_fetch_assoc($topics)){
                            $selected = isset($assigned_topics[$topic['id']]) ? 'selected' : '';
                            echo '<option value="'.$topic['id'].'" '.$selected.'>'.$topic['name'].'</option>';
                        }
                        echo '</select>';
                        echo '<button type="submit" name="save_group_topics" class="btn btn-success btn-sm">Save Topics</button>';
                        echo '</form>';
                        echo '</div>'; // card-body
                        echo '</div>'; // card
                    }
                    ?>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>

    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

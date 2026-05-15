<?php
    require_once("src/db/db_conn.php");
    require_once("src/db/session.php");
    require_once("src/db/privileges.php");

    if(isset($_GET['set_id']) && ctype_digit($_GET['set_id'])){
        $set_id = (int) $_GET['set_id'];

        if($type !== 'admin'){
            $stmt_exam = mysqli_prepare($conn, "SELECT `product_id`, `total_question`, `attempted_date`, `user_id` FROM `exam_stats` WHERE `set_id` = ? AND `user_id` = ?");
            mysqli_stmt_bind_param($stmt_exam, "ii", $set_id, $user_id);
        } else {
            $stmt_exam = mysqli_prepare($conn, "SELECT `product_id`, `total_question`, `attempted_date`, `user_id` FROM `exam_stats` WHERE `set_id` = ?");
            mysqli_stmt_bind_param($stmt_exam, "i", $set_id);
        }
        mysqli_stmt_execute($stmt_exam);
        $get_exam_date = mysqli_stmt_get_result($stmt_exam);
        mysqli_stmt_close($stmt_exam);

        if(mysqli_num_rows($get_exam_date) === 0){
            header("Location: home.php");
            exit;
        }

        $exam_row = mysqli_fetch_assoc($get_exam_date);
        $product_id   = $exam_row['product_id'];
        $total_question = $exam_row['total_question'];
        $attempted_on = $exam_row['attempted_date'];
        $student_id   = $exam_row['user_id'];

        // Get product details
        $stmt_product = mysqli_prepare($conn, "SELECT `name`, `description`, `exam_duration` FROM `products` WHERE `id` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_product, "i", $product_id);
        mysqli_stmt_execute($stmt_product);
        $product_result = mysqli_stmt_get_result($stmt_product);
        mysqli_stmt_close($stmt_product);

        $product_row  = mysqli_fetch_assoc($product_result);
        $product_name = $product_row['name'];
        $description  = $product_row['description'];
        $exam_duration = $product_row['exam_duration'];

        // Get student username
        $stmt_student = mysqli_prepare($conn, "SELECT `username` FROM `users` WHERE `user_id` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_student, "i", $student_id);
        mysqli_stmt_execute($stmt_student);
        $student_result = mysqli_stmt_get_result($stmt_student);
        mysqli_stmt_close($stmt_student);

        $student_row = mysqli_fetch_assoc($student_result);
        $student_username = $student_row['username'];
       
    }else{
        header("Location: home.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result</title>
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
                <table class="table">
                    <thead>
                        <?php if($type !== "student"): ?>
                            <tr>
                                <th>Username</th>
                                <td><?php echo htmlspecialchars($student_username, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Set Id:</th>
                            <td><?php echo (int) $set_id; ?></td>
                        </tr>
                        <tr>
                            <th>Product Name:</th>
                            <td><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Exam Duration:</th>
                            <td><?php echo (int) $exam_duration; ?> Minutes</td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td><?php echo htmlspecialchars($attempted_on, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Total Question:</th>
                            <td><?php echo (int) $total_question; ?></td>
                        </tr>
                    </tbody>
                </table>

                <h4>Details</h4>
                <?php
                    $stmt_questions = mysqli_prepare($conn,
                        "SELECT r.attempted_answer, q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.answer
                        FROM `results` r
                        JOIN `questions` q ON q.id = r.question_id
                        WHERE r.set_id = ?"
                    );
                    mysqli_stmt_bind_param($stmt_questions, "i", $set_id);
                    mysqli_stmt_execute($stmt_questions);
                    $get_set_questions = mysqli_stmt_get_result($stmt_questions);
                    mysqli_stmt_close($stmt_questions);

                    $count = 1;
                    while($row = mysqli_fetch_assoc($get_set_questions)){
                        $attempted_answer = $row['attempted_answer'];
                        $allowed_tags = '<p><br><strong><em><span><table><tr><td><th><ul><ol><li>';
                        $question  = strip_tags($row['question'],  $allowed_tags);
                        $option_a  = strip_tags($row['option_a'],  $allowed_tags);
                        $option_b  = strip_tags($row['option_b'],  $allowed_tags);
                        $option_c  = strip_tags($row['option_c'],  $allowed_tags);
                        $option_d  = strip_tags($row['option_d'],  $allowed_tags);
                        $answer    = htmlspecialchars($row['answer'], ENT_QUOTES, 'UTF-8');

                        if($attempted_answer === "nan"){
                            echo '
                                <div><strong>('.$count.') '.$question.'</strong></div>
                                <table class="table"><tbody><tr>
                                    <td>A: '.$option_a.'</td>
                                    <td>B: '.$option_b.'</td>
                                    <td>C: '.$option_c.'</td>
                                    <td>D: '.$option_d.'</td>
                                </tr></tbody></table>
                                <div class="text-danger">Not Attempted</div>
                                <div><strong>The Correct answer is: <span style="text-transform:uppercase">'.$answer.'</span></strong></div><br>
                            ';
                        } elseif($attempted_answer === $answer){
                            $cols = [
                                'a' => ['class_a'=>'text-success','class_b'=>'','class_c'=>'','class_d'=>''],
                                'b' => ['class_a'=>'','class_b'=>'text-success','class_c'=>'','class_d'=>''],
                                'c' => ['class_a'=>'','class_b'=>'','class_c'=>'text-success','class_d'=>''],
                                'd' => ['class_a'=>'','class_b'=>'','class_c'=>'','class_d'=>'text-success'],
                            ];
                            $c = $cols[$attempted_answer] ?? ['class_a'=>'','class_b'=>'','class_c'=>'','class_d'=>''];
                            echo '
                                <div><strong>('.$count.') '.$question.'</strong></div>
                                <table class="table"><tbody><tr>
                                    <td class="'.$c['class_a'].'">A: '.$option_a.'</td>
                                    <td class="'.$c['class_b'].'">B: '.$option_b.'</td>
                                    <td class="'.$c['class_c'].'">C: '.$option_c.'</td>
                                    <td class="'.$c['class_d'].'">D: '.$option_d.'</td>
                                </tr></tbody></table><br><hr>
                            ';
                        } else {
                            $cols = [
                                'a' => ['class_a'=>'text-danger','class_b'=>'','class_c'=>'','class_d'=>''],
                                'b' => ['class_a'=>'','class_b'=>'text-danger','class_c'=>'','class_d'=>''],
                                'c' => ['class_a'=>'','class_b'=>'','class_c'=>'text-danger','class_d'=>''],
                                'd' => ['class_a'=>'','class_b'=>'','class_c'=>'','class_d'=>'text-danger'],
                            ];
                            $c = $cols[$attempted_answer] ?? ['class_a'=>'','class_b'=>'','class_c'=>'','class_d'=>''];
                            echo '
                                <div><strong>('.$count.') '.$question.'</strong></div>
                                <table class="table"><tbody><tr>
                                    <td class="'.$c['class_a'].'">A: '.$option_a.'</td>
                                    <td class="'.$c['class_b'].'">B: '.$option_b.'</td>
                                    <td class="'.$c['class_c'].'">C: '.$option_c.'</td>
                                    <td class="'.$c['class_d'].'">D: '.$option_d.'</td>
                                </tr></tbody></table>
                                <div>Correct Answer is: <strong style="text-transform:uppercase">'.$answer.'</strong></div><br><hr>
                            ';
                        }

                        $count++;
                    }
                ?>
            </div>
            <div class="col-md-12 mt-4 mb-4">
                <button class="btn text-light bordered shadow" style="background:var(--primary)" onclick="window.location.href='home.php'">Okay</button>
            </div>
        </div>
    </div>

    <?php
        include("src/inc/footer.php");
    ?>   
    
</body>
</html>
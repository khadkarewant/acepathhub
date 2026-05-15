<?php
ob_start();
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";

// Redirect if no set_id
if(!isset($_GET['set_id']) || $_GET['set_id'] === ""){
    header("Location: my-courses.php");
    exit;
}

$set_id = (int)$_GET['set_id'];

// Fetch the set for this user
$stmt = mysqli_prepare($conn,
    "SELECT id, product_id, attempted, started FROM exam_sets 
     WHERE id = ? AND user_id = ? LIMIT 1"
);
if (!$stmt) {
    header("Location: my-products.php");
    exit;
}
mysqli_stmt_bind_param($stmt, 'ii', $set_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$result || mysqli_num_rows($result) !== 1) {
    header("Location: my-products.php");
    exit;
}
$set = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
$product_id = (int)$set['product_id'];

// Fetch product info
$stmt2 = mysqli_prepare($conn,
    "SELECT name, exam_duration FROM products WHERE id = ? LIMIT 1"
);
if (!$stmt2) {
    header("Location: my-products.php");
    exit;
}
mysqli_stmt_bind_param($stmt2, 'i', $product_id);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
$product = $result2 ? mysqli_fetch_assoc($result2) : null;
mysqli_stmt_close($stmt2);

if (!$product) {
    header("Location: my-products.php");
    exit;
}

$product_name = $product['name'];
$exam_duration = $product['exam_duration'];

// Deduct one set only if exam not started
if ($set['attempted'] === 'no' && $set['started'] === 'no') {

    $stmt3 = mysqli_prepare($conn,
        "SELECT id, remaining_sets FROM purchased_products 
         WHERE user_id = ? AND product_id = ? AND remaining_sets > 0 AND status = 'active' 
         LIMIT 1"
    );
    if ($stmt3) {
        mysqli_stmt_bind_param($stmt3, 'ii', $user_id, $product_id);
        mysqli_stmt_execute($stmt3);
        $result3 = mysqli_stmt_get_result($stmt3);
        $purchased = $result3 ? mysqli_fetch_assoc($result3) : null;
        mysqli_stmt_close($stmt3);

        if ($purchased) {
            $purchased_id = (int)$purchased['id'];
            $new_available_sets = (int)$purchased['remaining_sets'] - 1;

            $stmt4 = mysqli_prepare($conn,
                "UPDATE purchased_products SET remaining_sets = ? WHERE id = ? LIMIT 1"
            );
            if ($stmt4) {
                mysqli_stmt_bind_param($stmt4, 'ii', $new_available_sets, $purchased_id);
                mysqli_stmt_execute($stmt4);
                mysqli_stmt_close($stmt4);
            }

            $stmt5 = mysqli_prepare($conn,
                "UPDATE exam_sets SET started = 'yes' WHERE id = ? LIMIT 1"
            );
            if ($stmt5) {
                mysqli_stmt_bind_param($stmt5, 'i', $set_id);
                mysqli_stmt_execute($stmt5);
                mysqli_stmt_close($stmt5);
            }
        }
    }
}

// Prevent reloading or opening after completion
if($set['attempted'] == "yes"){
    echo "<script>alert('You have already attempted this set.');window.location='my-products.php';</script>";
    exit;
}

ob_end_flush();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exam in Progress</title>
<?php include("src/inc/links.php"); ?>
<style>
.countdown{
    position: fixed;
    right: 0px;
    box-shadow: 0px 0px 10px grey;
    padding: 2px;
    background: white;
    z-index: 1;
    top: 72px;
}
.question-content {
    border: 1px solid #ccc;
    padding: 12px;
    border-radius: 6px;
    background: white;
    font-size: 17px;
    white-space: normal;
}
</style>
<script>
$(document).ready(function(){
    let second = 0;
    let minute = 0;
    $(".minute").text("0"+minute);
    $(".second").text("0"+second);

    setInterval(() => {
        second++;
        if(second == 60){
            minute++;
            second = 0;
        }
        $(".minute").text(minute < 10 ? "0"+minute : minute);
        $(".second").text(second < 10 ? "0"+second : second);
    }, 1000);

    let exam_duration = <?php echo $exam_duration ?>;
    let isSubmitting = false;

    // Auto-submit when time is up
    setTimeout(() => {
        isSubmitting = true;
        $("#exam_form_submit").click();
    }, exam_duration * 60 * 1000);

    // Warning before refresh/close
    window.onbeforeunload = function(){
        if(!isSubmitting){
            return "If you refresh or close, your answers might not be saved.";
        }
    };

    // Disable warning when form is submitted
    $("#exam_form_submit").click(function(){
        isSubmitting = true;
    });
});
</script>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h4 class="text-center"><?php echo $product_name; ?></h4>
            <div class="text-center">(Exam in progress)</div>
            <div class="text-center">Set No.: <?php echo $set_id; ?></div>
            <div class="text-center">Exam Duration: <?php echo $exam_duration; ?> Minute</div>
            <div class="countdown">Time: <span class="minute"></span>:<span class="second"></span></div>

            <form action="exam-attempt-query.php" method="POST">
                <input type="hidden" name="set_id" value="<?php echo $set_id ?>">

                <hr>
                <?php
                $stmt6 = mysqli_prepare($conn,
                    "SELECT eq.tag, eq.mcq_id, q.id, q.question,
                            q.option_a, q.option_b, q.option_c, q.option_d
                    FROM exam_questions eq
                    JOIN questions q ON q.mcq_id = eq.mcq_id
                    WHERE eq.set_id = ?
                    ORDER BY eq.tag, RAND()"
                );
                if (!$stmt6) {
                    header("Location: my-products.php");
                    exit;
                }
                mysqli_stmt_bind_param($stmt6, 'i', $set_id);
                mysqli_stmt_execute($stmt6);
                $all_result = mysqli_stmt_get_result($stmt6);
                mysqli_stmt_close($stmt6);

                // Group by tag in PHP
                $grouped = [];
                while ($row = mysqli_fetch_assoc($all_result)) {
                    $grouped[$row['tag']][] = $row;
                }

                $count = 1;
                foreach ($grouped as $tag => $questions_in_tag) {
                    foreach ($questions_in_tag as $question) {
                        echo '
                        <input type="hidden" name="question_id'.$count.'" value="'.(int)$question['id'].'">
                        <div><strong>('.$count.') <div class="question-content">'.strip_tags($question['question'], '<p><br><strong><em><span><table><tr><td><th><ul><ol><li>').'</div></strong></div><br>
                        <div>Select Answer: </div>
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <td><input type="radio" value="nan" checked hidden name="answer'.$count.'">
                                        <input type="radio" value="a" id="a'.$count.'" name="answer'.$count.'"><label for="a'.$count.'">&nbsp; (A) &nbsp;'.strip_tags($question['option_a']).'</label></td>
                                    <td><input type="radio" value="b" id="b'.$count.'" name="answer'.$count.'"><label for="b'.$count.'">&nbsp; (B) &nbsp;'.strip_tags($question['option_b']).'</label></td>
                                    <td><input type="radio" value="c" id="c'.$count.'" name="answer'.$count.'"><label for="c'.$count.'">&nbsp; (C) &nbsp;'.strip_tags($question['option_c']).'</label></td>
                                    <td><input type="radio" value="d" id="d'.$count.'" name="answer'.$count.'"><label for="d'.$count.'">&nbsp; (D) &nbsp;'.strip_tags($question['option_d']).'</label></td>
                                </tr>
                            </table>
                        </div><hr><br>
                        ';
                        $count++;
                    }
                }

                ?>
                <br>
                <button type="submit" id="exam_form_submit" class="btn" name="submit" value="submit_exam" style="background:var(--primary);color:white;">Submit Paper</button>
            </form>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

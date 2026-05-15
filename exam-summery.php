<?php
require_once("src/db/db_conn.php");
require_once("src/db/session.php");
require_once("src/db/privileges.php");

if(!isset($_GET['set_id']) || !ctype_digit($_GET['set_id'])){
    header("Location: home.php");
    exit;
}

$set_id = (int) $_GET['set_id'];

if($type !== 'admin'){
    $stmt = mysqli_prepare($conn,
        "SELECT es.product_id, es.user_id, es.net_mark, es.product_name,
                es.total_question, es.total_attempt, es.correct_attempt,
                es.wrong_attempt, es.negative_mark, p.mark
         FROM `exam_stats` es
         JOIN `products` p ON p.id = es.product_id
         WHERE es.set_id = ? AND es.user_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "ii", $set_id, $user_id);
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT es.product_id, es.user_id, es.net_mark, es.product_name,
                es.total_question, es.total_attempt, es.correct_attempt,
                es.wrong_attempt, es.negative_mark, p.mark
         FROM `exam_stats` es
         JOIN `products` p ON p.id = es.product_id
         WHERE es.set_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $set_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if(mysqli_num_rows($result) === 0){
    header("Location: home.php");
    exit;
}

$row = mysqli_fetch_assoc($result);

$net_mark           = $row['net_mark'];
$product_name       = $row['product_name'];
$total_question     = (int) $row['total_question'];
$attempted_question = (int) $row['total_attempt'];
$correct_attempt    = (int) $row['correct_attempt'];
$wrong_attempt      = (int) $row['wrong_attempt'];
$negative_mark      = $row['negative_mark'];
$mark               = $row['mark'];
$unsolved_question  = $total_question - $attempted_question;
$full_mark          = $total_question * $mark;
$mark_obtained      = $net_mark;
$obtained_percentage = $full_mark > 0 ? ($mark_obtained / $full_mark) * 100 : 0;
if($obtained_percentage < 0){
    $obtained_percentage = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Summary</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 mx-auto p-2 border shadow">
            <?php if($type === "student"): ?>
                <h6 style="text-align:center;">You scored: <?php echo htmlspecialchars($net_mark, ENT_QUOTES, 'UTF-8'); ?>/<?php echo (int) $full_mark; ?></h6>
            <?php else: ?>
                <h6 style="text-align:center;">Student Scored: <?php echo htmlspecialchars($net_mark, ENT_QUOTES, 'UTF-8'); ?>/<?php echo (int) $full_mark; ?></h6>
            <?php endif; ?>

            <h2 style="text-align:center;"><?php echo number_format($obtained_percentage, 2); ?>%</h2>

            <table class="table">
                <tbody>
                    <tr><th colspan="2">Summary:</th></tr>
                    <tr>
                        <th>Product Name</th>
                        <td><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <th>Set ID</th>
                        <td><?php echo (int) $set_id; ?></td>
                    </tr>
                    <tr>
                        <th>Total Question</th>
                        <td><?php echo $total_question; ?></td>
                    </tr>
                    <tr>
                        <th>Unsolved Question</th>
                        <td><?php echo $unsolved_question; ?></td>
                    </tr>
                    <tr>
                        <th>Solved Question</th>
                        <td><?php echo $total_question - $unsolved_question; ?></td>
                    </tr>
                    <tr>
                        <th>Correct Answer</th>
                        <td><?php echo $correct_attempt; ?></td>
                    </tr>
                    <tr>
                        <th>Wrong Answer</th>
                        <td><?php echo $wrong_attempt; ?></td>
                    </tr>
                    <tr>
                        <th>Negative Mark</th>
                        <td><?php echo htmlspecialchars($negative_mark, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                </tbody>
            </table>

            <button onclick="window.location.href='user-stats.php'" class="btn text-light bg-success">Okay</button>
            <button onclick="window.location.href='exam-result.php?set_id=<?php echo (int) $set_id; ?>'" class="btn text-light bg-info">View Details</button>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
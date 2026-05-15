<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";

// Group ID
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if ($group_id <= 0) {
    header("Location: home.php");
    exit;
}

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page-1) * $per_page;

// Fetch group info including course
$stmt = mysqli_prepare($conn,
    "SELECT g.group_name, p.id AS product_id, p.course_id, c.name AS course_name
     FROM product_topic_groups g
     JOIN products p ON p.id = g.product_id
     JOIN courses c ON c.id = p.course_id
     WHERE g.id = ?
     LIMIT 1"
);
if (!$stmt) {
    header("Location: home.php");
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $group_id);
mysqli_stmt_execute($stmt);
$group_result = mysqli_stmt_get_result($stmt);
$group = $group_result ? mysqli_fetch_assoc($group_result) : null;
mysqli_stmt_close($stmt);

if (!$group) {
    header("Location: home.php");
    exit;
}

$product_id = (int)$group['product_id'];

// Leaderboard query (all for rank calculation)
$stmt2 = mysqli_prepare($conn,
    "SELECT
        u.user_id,
        CONCAT(u.first_name,' ',u.last_name) AS student_name,
        COUNT(pa.id) AS attempted,
        SUM(pa.is_correct=1) AS correct,
        SUM(pa.is_correct=0) AS wrong,
        ROUND((SUM(pa.is_correct=1)/COUNT(pa.id))*100,2) AS accuracy
     FROM practice_answers pa
     JOIN users u ON u.user_id = pa.user_id
     JOIN mcqs m ON m.id = pa.mcq_id
     JOIN product_topics pt ON pt.topic_id = m.topic_id
     WHERE pt.group_id = ?
       AND m.verified = 'true'
       AND m.status = 'live'
       AND pa.product_id = ?
     GROUP BY pa.user_id
     HAVING attempted > 0
     ORDER BY correct DESC, accuracy DESC"
);
if (!$stmt2) {
    header("Location: home.php");
    exit;
}
mysqli_stmt_bind_param($stmt2, 'ii', $group_id, $product_id);
mysqli_stmt_execute($stmt2);
$leaderboard_res_all = mysqli_stmt_get_result($stmt2);


// Process all rows to get ranks
$leaderboard_all = [];
$user_rank_info = null;
$rank_counter = 1;
while ($row = mysqli_fetch_assoc($leaderboard_res_all)) {
    $row['rank'] = $rank_counter;
    if ($row['user_id'] == $user_id) $user_rank_info = $row;
    $leaderboard_all[] = $row;
    $rank_counter++;
}
mysqli_stmt_close($stmt2);

// Pagination slice
$total_users = count($leaderboard_all);
$total_pages = ceil($total_users / $per_page);
$leaderboard = array_slice($leaderboard_all, $offset, $per_page);

// Determine page where user rank exists
$my_rank_page = $user_rank_info ? ceil($user_rank_info['rank'] / $per_page) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard - <?php echo htmlspecialchars($group['group_name']); ?></title>
<?php include("src/inc/links.php"); ?>
<style>
/* Rank Highlights */
.rank-1{background:#ffd700;font-weight:bold;}
.rank-2{background:#c0c0c0;font-weight:bold;}
.rank-3{background:#cd7f32;font-weight:bold;}

/* Table styling */
.table td, .table th { vertical-align: middle; text-align: center; padding:0.75rem; }

/* Mobile card view */
@media (max-width: 767px) {
    .table thead { display: none; }
    .table tr { display: block; margin-bottom: 10px; border:1px solid #ddd; border-radius:5px; padding:10px; }
    .table td { display: flex; justify-content: space-between; padding:5px 0; border-bottom:1px dashed #ccc; }
    .table td:last-child { border-bottom:none; }
    .table td:before { content: attr(data-label); font-weight:bold; }
}

/* User Rank Card */
.user-rank-card{
    border:2px solid var(--primary);
    padding:15px;
    border-radius:10px;
    margin-bottom:20px;
    text-align:center;
    background:#f8f9fa;
}
</style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container-fluid mt-4">

<div class="text-center mb-3">
    <h4>🏆 Leaderboard</h4>
    <p class="text-muted">
        Course: <?php echo htmlspecialchars($group['course_name']); ?><br>
        Topic: <?php echo htmlspecialchars($group['group_name']); ?>
    </p>
</div>

<!-- Back to Topics -->
<div class="mb-3">
    <a href="topics.php?product_id=<?php echo $group['product_id']; ?>" 
       class="btn btn-outline-primary btn-sm">
        ← Back to Topics
    </a>
</div>

<!-- User Rank Card -->
<?php if($user_rank_info): ?>
<div class="user-rank-card">
    <h5>Your Rank: #<?php echo $user_rank_info['rank']; ?></h5>
    <p>
        Name: <?php echo htmlspecialchars($user_rank_info['student_name']); ?><br>
        Attempted: <?php echo $user_rank_info['attempted']; ?> | 
        Correct: <?php echo $user_rank_info['correct']; ?> | 
        Wrong: <?php echo $user_rank_info['wrong']; ?> | 
        Accuracy: <?php echo $user_rank_info['accuracy']; ?>%
    </p>
</div>

<div class="text-center mb-3">
    <a href="?group_id=<?php echo $group_id; ?>&page=<?php echo $my_rank_page; ?>#my-row" 
       class="btn btn-primary btn-sm">Go to My Rank</a>
</div>
<?php else: ?>
<div class="user-rank-card">
    <p class="text-muted">You haven't attempted any questions yet.</p>
</div>
<?php endif; ?>

<!-- Leaderboard Table -->
<div class="table-responsive">
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Rank</th>
            <th>Student</th>
            <th>Attempted</th>
            <th>Correct</th>
            <th>Wrong</th>
            <th>Accuracy %</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if(empty($leaderboard)){
        echo '<tr><td colspan="6" class="text-center text-muted">No data yet</td></tr>';
    } else {
        foreach($leaderboard as $row){
            $rank_class = ($row['rank']==1?'rank-1':($row['rank']==2?'rank-2':($row['rank']==3?'rank-3':'')));
            $rank_icon = ($row['rank']==1?'🏆':($row['rank']==2?'🥈':($row['rank']==3?'🥉':'')));
            echo '<tr id="'.($row['user_id']==$user_id?'my-row':'').'" class="'.$rank_class.'">';
            echo '<td data-label="Rank">'.$rank_icon.' '.$row['rank'].'</td>';
            echo '<td data-label="Student">'.htmlspecialchars($row['student_name']).'</td>';
            echo '<td data-label="Attempted">'.$row['attempted'].'</td>';
            echo '<td data-label="Correct">'.$row['correct'].'</td>';
            echo '<td data-label="Wrong">'.$row['wrong'].'</td>';
            echo '<td data-label="Accuracy %">'.$row['accuracy'].'%</td>';
            echo '</tr>';
        }
    }
    ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<?php if($total_pages > 1): ?>
<nav aria-label="Leaderboard pagination">
    <ul class="pagination justify-content-center mt-3">
        <?php if($page>1): ?>
        <li class="page-item">
            <a class="page-link" href="?group_id=<?php echo $group_id; ?>&page=<?php echo $page-1; ?>">Previous</a>
        </li>
        <?php endif; ?>
        <?php for($p=1;$p<=$total_pages;$p++): ?>
        <li class="page-item <?php echo ($p==$page)?'active':''; ?>">
            <a class="page-link" href="?group_id=<?php echo $group_id; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
        </li>
        <?php endfor; ?>
        <?php if($page<$total_pages): ?>
        <li class="page-item">
            <a class="page-link" href="?group_id=<?php echo $group_id; ?>&page=<?php echo $page+1; ?>">Next</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

</div>

<!-- Smooth Scroll Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    if(window.location.hash === "#my-row"){
        const myRow = document.getElementById("my-row");
        if(myRow){
            myRow.scrollIntoView({behavior: "smooth", block: "center"});
        }
    }
});
</script>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

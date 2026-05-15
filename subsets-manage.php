<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// ===================== ADMIN CHECK =====================
// Only admin can access
if(!isset($type) || $type !== "admin"){
    header("Location: home.php");
    exit();
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Fetch course info
$course_res = mysqli_query($conn, "SELECT * FROM courses WHERE id='$course_id' LIMIT 1");
if(mysqli_num_rows($course_res)==0){
    die("Course not found.");
}
$course = mysqli_fetch_assoc($course_res);

// Fetch all topics for this course
$topics_res = mysqli_query($conn, "SELECT * FROM topics WHERE course_id='$course_id' ORDER BY id ASC");
$topics = [];
while($row = mysqli_fetch_assoc($topics_res)){
    // Fetch all subsets and their MCQ counts
    $subsets_res = mysqli_query($conn, "
    SELECT sub_set_number, COUNT(*) AS mcq_count
    FROM mcqs
    WHERE topic_id='{$row['id']}' 
    AND LOWER(TRIM(verified))='true' 
    AND status='live'
    GROUP BY sub_set_number
    ORDER BY sub_set_number ASC
");

    $subsets = [];
    while($sub = mysqli_fetch_assoc($subsets_res)){
        $subsets[] = $sub;
    }
    $row['subsets'] = $subsets;
    $topics[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Subsets - <?php echo htmlspecialchars($course['name']); ?></title>
<?php include("src/inc/links.php"); ?>
<style>
.topic-card{
    border:1px solid #ddd;
    border-left:6px solid var(--primary);
    padding:15px;
    border-radius:6px;
    margin-bottom:20px;
    background:#fff;
    transition:0.3s;
}
.topic-card:hover{
    box-shadow:0 6px 15px rgba(0,0,0,0.1);
    transform:translateY(-3px);
}
.subset-badge{
    margin:2px;
}
</style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container-fluid mt-4">

<div class="text-center">
    <h3 style="color:var(--primary)">Manage Subsets</h3>
    <p class="text-muted">Course: <?php echo htmlspecialchars($course['name']); ?></p>
</div>

<!-- Back to Courses -->
<div class="mb-3">
    <a href="courses.php" class="btn btn-secondary btn-sm">← Back to Courses</a>
</div>

<div class="row">
<?php if(count($topics)==0): ?>
    <div class="col-md-12 text-center">
        <p class="text-muted">No topics found for this course.</p>
    </div>
<?php else: ?>
    <?php foreach($topics as $topic): ?>
        <div class="col-md-6">
            <div class="topic-card">
                <h5><?php echo htmlspecialchars($topic['name']); ?></h5>
                <div class="mb-2">
                    <?php if(count($topic['subsets'])>0): ?>
                        <?php foreach($topic['subsets'] as $sub): ?>
                            <span class="badge bg-primary subset-badge">
                                Subset <?php echo $sub['sub_set_number']; ?> (<?php echo $sub['mcq_count']; ?> MCQs)
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">No subsets created yet.</span>
                    <?php endif; ?>
                </div>
                <div>
                    <button class="btn btn-success btn-sm add-subset-btn" data-topic="<?php echo $topic['id']; ?>">
                        Split into Subsets
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

</div>

<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<script>
$('.add-subset-btn').on('click', function(){
    let topic_id = $(this).data('topic');
    let sub_set_number = parseInt(prompt("Enter number of subsets to create:"), 10);
    if(!sub_set_number || sub_set_number < 1) return;

    $.post('ajax-split-subsets.php', {topic_id:topic_id, sub_set_number:sub_set_number}, function(res){
        if(res.status=='ok'){
            alert('Subsets created successfully!');
            location.reload();
        } else {
            alert('Error: '+res.error);
        }
    }, 'json');
});
</script>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

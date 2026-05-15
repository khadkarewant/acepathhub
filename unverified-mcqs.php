<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

if($type == "student" || $type == "biller"){
    header("Location: home.php");
    exit;
}

// Get selected course_id from GET (default to first course)
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Fetch all courses for dropdown
$courses_result = mysqli_query($conn, "SELECT id, name FROM courses ORDER BY id ASC");
$courses = mysqli_fetch_all($courses_result, MYSQLI_ASSOC);

// If no course selected, default to first
if($selected_course_id === 0 && count($courses) > 0){
    $selected_course_id = $courses[0]['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unverified MCQs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

            <h3>Unverified MCQs</h3>

            <!-- Course selection dropdown -->
            <form method="GET" id="courseForm" style="margin-bottom:20px;">
                <label><strong>Select Course:</strong></label>
                <select name="course_id" class="form-control" onchange="document.getElementById('courseForm').submit();">
                    <?php foreach($courses as $course): ?>
                        <option value="<?= $course['id'] ?>" <?= ($course['id'] == $selected_course_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php
            // Fetch unverified topics for selected course
            $get_topic_order = mysqli_query($conn, "
                SELECT DISTINCT m.topic_id 
                FROM mcqs m
                INNER JOIN topics t ON m.topic_id = t.id
                WHERE m.verified = 'false' AND t.course_id = '$selected_course_id'
                ORDER BY m.topic_id ASC
            ");

            if(mysqli_num_rows($get_topic_order) > 0){
                while($row = mysqli_fetch_assoc($get_topic_order)){
                    $topic_id = $row['topic_id'];
                    $get_topic = mysqli_query($conn, "SELECT * FROM topics WHERE id='$topic_id'");
                    $topic = mysqli_fetch_assoc($get_topic);

                    if($type == "admin" || $type == "teacher"){
                        $count_unverified_questions = mysqli_query($conn, "
                            SELECT * FROM mcqs WHERE topic_id='$topic_id' AND verified='false'
                        ");
                    } else {
                        $count_unverified_questions = mysqli_query($conn, "
                            SELECT * FROM mcqs WHERE topic_id='$topic_id' AND verified='false' AND created_by='$user_id'
                        ");
                    }

                    $unverified_count = mysqli_num_rows($count_unverified_questions);

                    if($unverified_count > 0){
                        echo '<div onclick="window.location.href=\'unverified-questions.php?topic_id='.$topic_id.'&course_id='.$selected_course_id.'\'" style="padding:5px;cursor:pointer;font-weight:700;">
                                (Topic Id: '.$topic['id'].') '.htmlspecialchars($topic['name']).' ('.$unverified_count.' unverified question/s)
                              </div><hr>';
                    }
                }
            } else {
                echo '<div class="text-danger">No Unverified Questions Available for this course.</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

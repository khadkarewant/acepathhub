<?php
ob_start();

/* ---------- TEMP: enable errors while testing ---------- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

/* ---------- Validate topic (GET) ---------- */
if (!isset($_GET['topic_id']) || $_GET['topic_id'] === "") {
    header("Location: courses.php");
    exit;
}

$topic_id = $_GET['topic_id'];

/* ---------- Fetch topic and course info ---------- */
$get_topic = mysqli_query($conn, "
    SELECT t.*, c.is_practice 
    FROM topics t 
    JOIN courses c ON t.course_id = c.id 
    WHERE t.id='$topic_id'
");
if (mysqli_num_rows($get_topic) === 0) {
    header("Location: home.php");
    exit;
}
$topic = mysqli_fetch_assoc($get_topic);
$topic_name  = $topic['name'];
$course_id   = $topic['course_id'];
$is_practice = $topic['is_practice']; // <- now correct



/* ---------- Handle POST insert ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $topic_id = $_POST['topic_id']; // CRITICAL

    $question = $_POST['question'];
    $option_a = htmlentities($_POST['option_a']);
    $option_b = htmlentities($_POST['option_b']);
    $option_c = htmlentities($_POST['option_c']);
    $option_d = htmlentities($_POST['option_d']);
    $answer   = htmlentities($_POST['answer']);

    /* ---------- Insert into mcqs table ---------- */
    $insert_mcq = mysqli_query($conn, "
        INSERT INTO mcqs
        (question_weight, image, topic_id, created_by, created_on, created_at, verified, status, is_practice)
        VALUES
        ('1', 'N/A', '$topic_id', '$user_id', '".date('Y-m-d')."', '".date('H:i:s')."', 'false', 'draft', '$is_practice')
    ");

    if (!$insert_mcq) {
        die("MCQ insert failed: " . mysqli_error($conn));
    }

    $mcq_id = mysqli_insert_id($conn);

    /* ---------- Insert into questions table ---------- */
    $insert_question = mysqli_query($conn, "
        INSERT INTO questions
        (mcq_id, question, option_a, option_b, option_c, option_d, answer, need_upgrade)
        VALUES
        ('$mcq_id', '$question', '$option_a', '$option_b', '$option_c', '$option_d', '$answer', NULL)
    ");

    if (!$insert_question) {
        die("Question insert failed: " . mysqli_error($conn));
    }

    // Redirect with success flag
    header("Location: add-mcq.php?topic_id=$topic_id&success=1");
    exit;
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add MCQ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php include("src/inc/links.php"); ?>
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

    <style>
        label { font-weight: 700; }
    </style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">

            <h2>Add New MCQ</h2>

            <form id="mcqForm" method="POST">

                <!-- REQUIRED hidden topic_id -->
                <input type="hidden" name="topic_id" value="<?= $topic_id ?>">

                <label>Topic Name:</label>
                <input type="text" class="form-control" value="<?= $topic_name ?>" disabled>
                <br>

                <label>Question:</label>
                <textarea name="question" id="editor" class="form-control" required></textarea>

                <label>Option A:</label>
                <input type="text" name="option_a" class="form-control" required>

                <label>Option B:</label>
                <input type="text" name="option_b" class="form-control" required>

                <label>Option C:</label>
                <input type="text" name="option_c" class="form-control" required>

                <label>Option D:</label>
                <input type="text" name="option_d" class="form-control" required>

                <label>Correct Answer:</label>
                <table class="table">
                    <tr>
                        <td><input type="radio" name="answer" value="a"> Option A</td>
                        <td><input type="radio" name="answer" value="b"> Option B</td>
                        <td><input type="radio" name="answer" value="c"> Option C</td>
                        <td><input type="radio" name="answer" value="d"> Option D</td>
                    </tr>
                </table>

                <button type="submit"
                        class="btn"
                        style="background:var(--primary);color:white;">
                    Add MCQ
                </button>

            </form>
        </div>
    </div>
</div>

<!-- CKEditor + Auto Focus + Success Alert + Answer Validation -->
<script>
    CKEDITOR.replace('editor');

    // Focus question editor on load
    CKEDITOR.on('instanceReady', function(evt) {
        evt.editor.focus();
    });

    // Show success alert
    <?php if (isset($_GET['success'])): ?>
        alert("MCQ added successfully!");
    <?php endif; ?>

    // Validate answer selection before submitting
    document.getElementById('mcqForm').addEventListener('submit', function(e) {
        // Update CKEditor textarea before validation
        for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }

        const checked = document.querySelector('input[name="answer"]:checked');
        if (!checked) {
            alert('Please select the correct answer!');
            e.preventDefault(); // stop submission
            return false;
        }

        // Disable button after validation
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'Saving...';
    });
</script>

<?php include("src/inc/footer.php"); ?>

</body>
</html>

<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

if ($view_topic !== "true") {
    header("Location: courses.php");
    exit;
}

// Validate topic_id (stop SQL injection + bad input)
$topic_id_in = filter_input(INPUT_GET, 'topic_id', FILTER_VALIDATE_INT);
if (!$topic_id_in) {
    header("Location: courses.php");
    exit;
}

// Fetch topic details safely
$stmt = mysqli_prepare($conn, "SELECT id, name, description, created_by, created_on, created_at, modified_by, modified_on, modified_at FROM topics WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $topic_id_in);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: home.php");
    exit;
}

$row = mysqli_fetch_assoc($res);
$topic_id = (int)$row['id'];
$topic_name = $row['name'];
$description = $row['description'];
$created_by = $row['created_by'];
$created_on = $row['created_on'];
$created_at = $row['created_at'];
$modified_by = $row['modified_by'];
$modified_on = $row['modified_on'];
$modified_at = $row['modified_at'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft MCQ Details</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 table-responsive">
            <h3><strong>Topic Name: </strong><?php echo htmlspecialchars($topic_name); ?><br></h3>
            <hr>
            <h3>Drafted MCQs:</h3>

            <table class="table table-bordered table-hovered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Main Question</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    // Fetch drafted mcqs safely
                    $stmtM = mysqli_prepare($conn, "SELECT id FROM mcqs WHERE topic_id = ? AND verified = 'true' AND status = 'draft'");
                    mysqli_stmt_bind_param($stmtM, "i", $topic_id);
                    mysqli_stmt_execute($stmtM);
                    $mcqRes = mysqli_stmt_get_result($stmtM);

                    if ($mcqRes && mysqli_num_rows($mcqRes) > 0) {
                        while ($mcq = mysqli_fetch_assoc($mcqRes)) {
                            $mcq_id = (int)$mcq['id'];

                            // Fetch first question safely
                            $stmtQ = mysqli_prepare($conn, "SELECT question FROM questions WHERE mcq_id = ? LIMIT 1");
                            mysqli_stmt_bind_param($stmtQ, "i", $mcq_id);
                            mysqli_stmt_execute($stmtQ);
                            $qRes = mysqli_stmt_get_result($stmtQ);
                            $qRow = $qRes ? mysqli_fetch_assoc($qRes) : null;
                            $question = $qRow['question'] ?? '';

                            ?>
                            <tr>
                                <td><?php echo $mcq_id; ?></td>
                                <?php
                                $question_plain = trim(strip_tags($question ?? ''));
                                ?>
                                <td><?php echo htmlspecialchars($question_plain); ?></td>
                                <td style="white-space:nowrap;">

                                    <!-- Details (GET is fine) -->
                                    <button class="btn bg-success"
                                            onclick="window.location.href='mcq-details.php?mcq_id=<?php echo $mcq_id; ?>'">
                                        Details
                                    </button>

                                    <!-- Make Live (POST + CSRF) -->
                                    <form action="mcq-status-change.php" method="POST" style="display:inline;">
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="status" value="live">
                                        <input type="hidden" name="mcq_id" value="<?php echo $mcq_id; ?>">
                                        <button type="submit" class="btn bg-warning"
                                                onclick="return confirm('Make this MCQ live?');">
                                            Make Live
                                        </button>
                                    </form>

                                    <!-- Delete (POST + CSRF) -->
                                    <form action="delete-mcq.php" method="POST" style="display:inline;">
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="mcq_id" value="<?php echo $mcq_id; ?>">
                                        <button type="submit" class="btn bg-danger"
                                                onclick="return confirm('Delete this MCQ permanently?');">
                                            Delete
                                        </button>
                                    </form>

                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '
                            <tr>
                                <td colspan="3">No MCQ Available</td>
                            </tr>
                        ';
                    }
                ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
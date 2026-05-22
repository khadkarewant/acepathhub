<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

if (!has_role(ROLE_ADMIN) && !has_role(ROLE_DATA_ENTRY)) {
    header("Location: home.php");
    exit;
}

$subject_id = (int)($_GET['subject_id'] ?? 0);

if ($subject_id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, name, exam_body_id FROM subjects WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $subject_id);
mysqli_stmt_execute($stmt);
$sub_result = mysqli_stmt_get_result($stmt);
$subject = mysqli_fetch_assoc($sub_result);
mysqli_stmt_close($stmt);

if (!$subject) {
    header("Location: exam-body-list.php");
    exit;
}

$created = isset($_GET['created']);
$updated = isset($_GET['updated']);
$deleted = isset($_GET['deleted']);
$error   = $_GET['error'] ?? '';

$stmt = mysqli_prepare($conn,
    "SELECT id, year, duration_minutes, total_questions, status
     FROM past_papers WHERE subject_id = ? ORDER BY year DESC");
mysqli_stmt_bind_param($stmt, "i", $subject_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$past_papers = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Papers — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 table-responsive">
            <div class="d-flex justify-content-between align-items-center my-3">
                <h3 style="color:var(--accent);">
                    <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?> — Past Papers
                </h3>
                <div>
                    <a href="subject-list.php?exam_body_id=<?= (int)$subject['exam_body_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-2">Back</a>
                    <?php if (has_role(ROLE_ADMIN)): ?>
                        <a href="past-paper-add.php?subject_id=<?= $subject_id ?>" class="btn btn-sm"
                           style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                            + Add Past Paper
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($created): ?>
                <div class="alert alert-success">Past paper added successfully.</div>
            <?php elseif ($updated): ?>
                <div class="alert alert-success">Past paper updated successfully.</div>
            <?php elseif ($deleted): ?>
                <div class="alert alert-success">Past paper deleted.</div>
            <?php elseif ($error === 'has_questions'): ?>
                <div class="alert alert-danger">Cannot delete — remove all MCQs first.</div>
            <?php elseif ($error === 'delete_failed'): ?>
                <div class="alert alert-danger">Delete failed. Please try again.</div>
            <?php endif; ?>

            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Year</th>
                        <th>Duration (min)</th>
                        <th>Total Questions</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($past_papers) > 0):
                    $sn = 1;
                    foreach ($past_papers as $row): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td><?= (int)$row['year'] ?></td>
                        <td><?= (int)$row['duration_minutes'] ?></td>
                        <td><?= (int)$row['total_questions'] ?></td>
                        <td><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (has_role(ROLE_ADMIN)): ?>
                                <a href="past-paper-edit.php?id=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="past-paper-delete.php" class="d-inline ms-1"
                                      onsubmit="return confirm('Delete this past paper?');">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                            <?php if (has_role(ROLE_DATA_ENTRY)): ?>
                                <a href="mcq-add.php?past_paper_id=<?= (int)$row['id'] ?>"
                                   class="btn btn-sm btn-outline-warning ms-1">Add MCQ</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
                else: ?>
                    <tr><td colspan="6">No past papers found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include("inc/footer.php"); ?>
</body>
</html>
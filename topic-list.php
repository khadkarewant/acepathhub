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

// Verify subject exists
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

$created = isset($_GET['created']) && $_GET['created'] === '1';
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
$error   = $_GET['error'] ?? '';

$stmt = mysqli_prepare($conn,
    "SELECT t.id, t.name,
            COUNT(DISTINCT q.id) AS total_questions,
            SUM(CASE WHEN qs.verified = 1 AND qs.status = 'published' THEN 1 ELSE 0 END) AS published_questions
     FROM topics t
     LEFT JOIN question_sets qs ON qs.topic_id = t.id
     LEFT JOIN questions q ON q.question_set_id = qs.id
     WHERE t.subject_id = ?
     GROUP BY t.id, t.name
     ORDER BY t.id ASC"
);

mysqli_stmt_bind_param($stmt, "i", $subject_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$topics = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topics — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title"><?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?> — Topics</div>
        <?php if (has_role(ROLE_ADMIN)): ?>
            <a href="topic-add.php?subject_id=<?= $subject_id ?>" class="btn btn-sm" style="background:var(--accent);color:#000;font-weight:600;">+ Add Topic</a>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <a href="subject-list.php?exam_body_id=<?= (int)$subject['exam_body_id'] ?>" class="btn-qs-sm">← Back</a>
    </div>

    <?php if ($created): ?>
        <div class="alert alert-success">Topic added successfully.</div>
    <?php elseif ($updated): ?>
        <div class="alert alert-success">Topic updated successfully.</div>
    <?php elseif ($deleted): ?>
        <div class="alert alert-success">Topic deleted.</div>
    <?php elseif ($error === 'has_questions'): ?>
        <div class="alert alert-danger">Cannot delete — remove all questions first.</div>
    <?php elseif ($error === 'in_use'): ?>
        <div class="alert alert-danger">Cannot delete — topic is assigned to an exam or practice group.</div>
    <?php elseif ($error === 'delete_failed'): ?>
        <div class="alert alert-danger">Delete failed. Please try again.</div>
    <?php endif; ?>

    <table class="table table-hover" id="datatable">
        <thead>
            <tr>
                <th>S.N</th>
                <th>Name</th>
                <th>MCQs (Published/Total)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($topics)):
            $sn = 1;
            foreach ($topics as $row): ?>
            <tr>
                <td><?= $sn++ ?></td>
                <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$row['published_questions'] ?>/<?= (int)$row['total_questions'] ?></td>
                <td>
                    <?php if (has_role(ROLE_ADMIN)): ?>
                        <a href="question-set-list.php?topic_id=<?= (int)$row['id'] ?>" class="btn-qs-sm">View MCQs</a>
                        <a href="practice-subsets-manage.php?topic_id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Subsets</a>
                        <a href="topic-edit.php?id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Edit</a>
                        <form method="POST" action="topic-delete.php" style="display:inline;"
                              onsubmit="return confirm('Delete this topic?');">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button type="submit" class="btn-qs-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                    <?php if (has_role(ROLE_DATA_ENTRY)): ?>
                        <a href="mcq-add.php?topic_id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Add MCQ</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach;
        else: ?>
            <tr><td colspan="4">No topics found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<?php include "inc/footer.php"; ?>
</body>
</html>
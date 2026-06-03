<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

if (!has_role(ROLE_ADMIN) && !has_role(ROLE_DATA_ENTRY)) {
    header("Location: home.php");
    exit;
}

$exam_body_id = (int)($_GET['exam_body_id'] ?? 0);

if ($exam_body_id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

// Verify exam body exists
$stmt = mysqli_prepare($conn, "SELECT id, name FROM exam_bodies WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $exam_body_id);
mysqli_stmt_execute($stmt);
$eb_result = mysqli_stmt_get_result($stmt);
$exam_body = mysqli_fetch_assoc($eb_result);
mysqli_stmt_close($stmt);

if (!$exam_body) {
    header("Location: exam-body-list.php");
    exit;
}

$created = isset($_GET['created']) && $_GET['created'] === '1';
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
$error   = $_GET['error'] ?? '';

$stmt = mysqli_prepare($conn,
    "SELECT s.id, s.name,
            COUNT(DISTINCT q.id) AS total_questions,
            SUM(CASE WHEN qs.verified = 1 AND qs.status = 'published' THEN 1 ELSE 0 END) AS published_questions
     FROM subjects s
     LEFT JOIN topics t ON t.subject_id = s.id
     LEFT JOIN question_sets qs ON qs.topic_id = t.id
     LEFT JOIN questions q ON q.question_set_id = qs.id
     WHERE s.exam_body_id = ?
     GROUP BY s.id, s.name
     ORDER BY s.id ASC"
);

mysqli_stmt_bind_param($stmt, "i", $exam_body_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subjects = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title"><?= htmlspecialchars($exam_body['name'], ENT_QUOTES, 'UTF-8') ?> — Subjects</div>
        <?php if (has_role(ROLE_ADMIN)): ?>
            <a href="subject-add.php?exam_body_id=<?= $exam_body_id ?>" class="btn btn-sm" style="background:var(--accent);color:#000;font-weight:600;">+ Add Subject</a>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <a href="exam-body-list.php" class="btn-qs-sm">← Back</a>
    </div>

    <?php if ($created): ?>
        <div class="alert alert-success">Subject added successfully.</div>
    <?php elseif ($updated): ?>
        <div class="alert alert-success">Subject updated successfully.</div>
    <?php elseif ($deleted): ?>
        <div class="alert alert-success">Subject deleted.</div>
    <?php elseif ($error === 'has_topics'): ?>
        <div class="alert alert-danger">Cannot delete — remove all topics first.</div>
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
        <?php if (!empty($subjects)):
            $sn = 1;
            foreach ($subjects as $row): ?>
            <tr>
                <td><?= $sn++ ?></td>
                <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$row['published_questions'] ?>/<?= (int)$row['total_questions'] ?></td>
                <td>
                    <a href="topic-list.php?subject_id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Topics</a>

                    <a href="past-paper-list.php?subject_id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Past Papers</a>
                    
                    <?php if (has_role(ROLE_ADMIN)): ?>
                        <a href="subject-detail.php?id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Detail</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach;
        else: ?>
            <tr><td colspan="4">No subjects found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<?php include "inc/footer.php"; ?>
</body>
</html>
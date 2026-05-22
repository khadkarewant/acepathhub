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

$created = isset($_GET['created']);
$updated = isset($_GET['updated']);
$deleted = isset($_GET['deleted']);
$error   = $_GET['error'] ?? '';

$stmt = mysqli_prepare($conn, "SELECT id, name FROM subjects WHERE exam_body_id = ? ORDER BY id ASC");
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
    <div class="row">
        <div class="col-md-12 table-responsive">
            <div class="d-flex justify-content-between align-items-center my-3">
                <h3 style="color:var(--accent);">
                    <?= htmlspecialchars($exam_body['name'], ENT_QUOTES, 'UTF-8') ?> — Subjects
                </h3>
                <div>
                    <a href="exam-body-list.php" class="btn btn-sm btn-outline-secondary me-2">Back</a>
                    <?php if (has_role(ROLE_ADMIN)): ?>
                        <a href="subject-add.php?exam_body_id=<?= $exam_body_id ?>" class="btn btn-sm"
                        style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                            + Add Subject
                        </a>
                    <?php endif; ?>
                </div>
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

            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($subjects) > 0):
                    $sn = 1;
                    foreach ($subjects as $row): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="topic-list.php?subject_id=<?= (int)$row['id'] ?>"
                            class="btn btn-sm btn-outline-warning">Topics</a>
                            <?php if (has_role(ROLE_ADMIN)): ?>
                                <a href="subject-edit.php?id=<?= (int)$row['id'] ?>"
                                class="btn btn-sm btn-outline-primary ms-1">Edit</a>
                                <form method="POST" action="subject-delete.php" class="d-inline ms-1"
                                onsubmit="return confirm('Delete this subject?');">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            <a href="past-paper-list.php?subject_id=<?= (int)$row['id'] ?>"
                            class="btn btn-sm btn-outline-info ms-1">Past Papers</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
                else: ?>
                    <tr><td colspan="3">No subjects found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include("inc/footer.php"); ?>
</body>
</html>
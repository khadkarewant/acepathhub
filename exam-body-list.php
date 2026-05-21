<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

if (!has_role(ROLE_ADMIN) && !has_role(ROLE_DATA_ENTRY)) {
    header("Location: home.php");
    exit;
}

$created = isset($_GET['created']);
$updated = isset($_GET['updated']);
$deleted = isset($_GET['deleted']);
$error   = $_GET['error'] ?? '';

$result = mysqli_query($conn, "SELECT id, name FROM exam_bodies ORDER BY id ASC");
$exam_bodies = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Bodies — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 table-responsive">
            <div class="d-flex justify-content-between align-items-center my-3">
                <h3 style="color:var(--accent);">Exam Bodies</h3>
                <?php if (has_role(ROLE_ADMIN)): ?>
                    <a href="exam-body-add.php" class="btn"
                    style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                        + Add Exam Body
                    </a>
                <?php endif; ?>
            </div>
            <?php if ($created): ?>
                <div class="alert alert-success">Exam body added successfully.</div>
            <?php elseif ($updated): ?>
                <div class="alert alert-success">Exam body updated successfully.</div>
            <?php elseif ($deleted): ?>
                <div class="alert alert-success">Exam body deleted.</div>
            <?php elseif ($error === 'has_subjects'): ?>
                <div class="alert alert-danger">Cannot delete — remove all subjects first.</div>
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
                <?php if (count($exam_bodies) > 0):
                    $sn = 1;
                    foreach ($exam_bodies as $row): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="subject-list.php?exam_body_id=<?= (int)$row['id'] ?>"
                            class="btn btn-sm btn-outline-warning">Subjects</a>
                            <?php if (has_role(ROLE_ADMIN)): ?>
                                <a href="exam-body-edit.php?id=<?= (int)$row['id'] ?>"
                                class="btn btn-sm btn-outline-primary ms-1">Edit</a>
                                <form method="POST" action="exam-body-delete.php" class="d-inline ms-1"
                                    onsubmit="return confirm('Delete this exam body?');">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="3">No exam bodies found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include("inc/footer.php"); ?>
</body>
</html>
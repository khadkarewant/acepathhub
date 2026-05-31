<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_ADMIN, ROLE_DATA_ENTRY);

$created = isset($_GET['created']) && $_GET['created'] === '1';
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
$error   = $_GET['error'] ?? '';

$result      = mysqli_query($conn, "SELECT id, name FROM exam_bodies ORDER BY id ASC");
$exam_bodies = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Bodies — AcePath Hub</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title">Exam Bodies</div>
        <?php if (has_role(ROLE_ADMIN)): ?>
            <a href="exam-body-add.php" class="btn-qs-gold">+ Add Exam Body</a>
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
        <?php if (!empty($exam_bodies)):
            $sn = 1;
            foreach ($exam_bodies as $row): ?>
            <tr>
                <td><?= $sn++ ?></td>
                <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="subject-list.php?exam_body_id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Subjects</a>
                    <?php if (has_role(ROLE_ADMIN)): ?>
                        <a href="exam-body-edit.php?id=<?= (int)$row['id'] ?>" class="btn-qs-sm">Edit</a>
                        <form method="POST" action="exam-body-delete.php" style="display:inline;"
                              onsubmit="return confirm('Delete this exam body?');">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button type="submit" class="btn-qs-danger">Delete</button>
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

<?php include "inc/footer.php"; ?>
</body>
</html>
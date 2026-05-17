<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

$created = isset($_GET['created']);

$result = mysqli_query($conn, "SELECT id, name FROM exam_bodies ORDER BY id ASC");
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
                <a href="exam-body-add.php" class="btn"
                   style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                    + Add Exam Body
                </a>
            </div>

            <?php if ($created): ?>
                <div class="alert alert-success">Exam body added successfully.</div>
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
                <?php if (mysqli_num_rows($result) > 0):
                    $sn = 1;
                    while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="subject-list.php?exam_body_id=<?= (int)$row['id'] ?>"
                               class="btn btn-sm btn-outline-warning">Subjects</a>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else: ?>
                    <tr><td colspan="4">No exam bodies found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
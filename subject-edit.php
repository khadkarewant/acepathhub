<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, exam_body_id, name, slug FROM subjects WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subject = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$subject) {
    header("Location: exam-body-list.php");
    exit;
}

$exam_body_id = (int)$subject['exam_body_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = "Name must be 2–100 characters.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM subjects WHERE slug = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $slug, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Another subject with this name already exists.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "UPDATE subjects SET name = ?, slug = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $name, $slug, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: subject-list.php?exam_body_id={$exam_body_id}&updated=1");
            exit;
        }
        $errors[] = "Update failed. Please try again.";
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 p-2 m-1">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 style="color:var(--accent);">Edit Subject</h2>
                <a href="subject-list.php?exam_body_id=<?= $exam_body_id ?>" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_input(); ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($_POST['name'] ?? $subject['name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="btn"
                        style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                    Update Subject
                </button>
            </form>
        </div>
    </div>
</div>
<?php include("inc/footer.php"); ?>
</body>
</html>
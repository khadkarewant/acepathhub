<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $slug = trim($slug, '-');

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = "Name must be 2–100 characters.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM subjects WHERE slug = ? AND exam_body_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $slug, $exam_body_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Subject already exists.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO subjects (exam_body_id, name, slug) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $exam_body_id, $name, $slug);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: subject-list.php?exam_body_id=" . $exam_body_id . "&created=1");
            exit;
        } else {
            $errors[] = "Failed to add subject. Please try again.";
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subject — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 p-2 m-1">
            <h2 style="color:var(--accent);">Add Subject</h2>
            <p style="color:var(--text);">Exam Body: <strong><?= htmlspecialchars($exam_body['name'], ENT_QUOTES, 'UTF-8') ?></strong></p>

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
                    <label class="form-label">Subject Name</label>
                    <input type="text" name="name" class="form-control" required
                           placeholder="e.g. Biology"
                           value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" class="btn"
                        style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                    Add Subject
                </button>
            </form>
        </div>
    </div>
</div>
<?php include("inc/footer.php"); ?>
</body>
</html>
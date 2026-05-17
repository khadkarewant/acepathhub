<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

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
        $stmt = mysqli_prepare($conn, "SELECT id FROM exam_bodies WHERE slug = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $slug);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Exam body already exists.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO exam_bodies (name, slug) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $name, $slug);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: exam-body-list.php?created=1");
            exit;
        } else {
            $errors[] = "Failed to add exam body. Please try again.";
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
    <title>Add Exam Body — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 p-2 m-1">
            <h2 style="color:var(--accent);">Add Exam Body</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_input(); ?>

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required
                           placeholder="e.g. WAEC"
                           value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <button type="submit" class="btn"
                        style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                    Add Exam Body
                </button>
            </form>
        </div>
    </div>
</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
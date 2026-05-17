<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

$subject_id = (int)($_GET['subject_id'] ?? 0);

if ($subject_id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

// Verify subject exists
$stmt = mysqli_prepare($conn, "SELECT id, name FROM subjects WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $subject_id);
mysqli_stmt_execute($stmt);
$sub_result = mysqli_stmt_get_result($stmt);
$subject = mysqli_fetch_assoc($sub_result);
mysqli_stmt_close($stmt);

if (!$subject) {
    header("Location: exam-body-list.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = "Topic name must be 2–120 characters.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM topics WHERE name = ? AND subject_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $name, $subject_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Topic already exists under this subject.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO topics (subject_id, name) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "is", $subject_id, $name);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: topic-list.php?subject_id=" . $subject_id . "&created=1");
            exit;
        } else {
            $errors[] = "Failed to add topic. Please try again.";
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
    <title>Add Topic — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 p-2 m-1">
            <h2 style="color:var(--accent);">Add Topic</h2>
            <p style="color:var(--text);">Subject: <strong><?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?></strong></p>

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
                    <label class="form-label">Topic Name</label>
                    <input type="text" name="name" class="form-control" required
                           placeholder="e.g. Photosynthesis"
                           value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" class="btn"
                        style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                    Add Topic
                </button>
            </form>
        </div>
    </div>
</div>
<?php include("inc/footer.php"); ?>
</body>
</html>
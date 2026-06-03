<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_ADMIN, ROLE_STUDENT);

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

// Fetch subject + exam body
$stmt = mysqli_prepare($conn,
    "SELECT s.id, s.name, s.syllabus_path, s.exam_body_id,
            eb.name AS exam_body_name
     FROM subjects s
     JOIN exam_bodies eb ON eb.id = s.exam_body_id
     WHERE s.id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res     = mysqli_stmt_get_result($stmt);
$subject = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$subject) {
    header("Location: exam-body-list.php");
    exit;
}

$exam_body_id = (int)$subject['exam_body_id'];
$upload_dir   = "assets/uploads/syllabus/";
$errors       = [];
$uploaded     = isset($_GET['uploaded']) && $_GET['uploaded'] === '1';
$removed      = isset($_GET['removed'])  && $_GET['removed']  === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_role(ROLE_ADMIN)) {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // ── Remove syllabus ───────────────────────────────────────────────────
    if ($action === 'remove') {
        if (!empty($subject['syllabus_path']) && file_exists($subject['syllabus_path'])) {
            unlink($subject['syllabus_path']);
        }
        $stmt = mysqli_prepare($conn, "UPDATE subjects SET syllabus_path = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: subject-detail.php?id={$id}&removed=1");
        exit;
    }

    // ── Upload syllabus ───────────────────────────────────────────────────
    if ($action === 'upload') {
        if (!isset($_FILES['syllabus']) || $_FILES['syllabus']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "No file uploaded or upload error.";
        } else {
            $file     = $_FILES['syllabus'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $max_size = 5 * 1024 * 1024;

            if ($ext !== 'pdf') {
                $errors[] = "Only PDF files are allowed.";
            } elseif ($file['size'] > $max_size) {
                $errors[] = "File size must not exceed 5MB.";
            } else {
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (!empty($subject['syllabus_path']) && file_exists($subject['syllabus_path'])) {
                    unlink($subject['syllabus_path']);
                }

                $filename = "subject_{$id}.pdf";
                $dest     = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $stmt = mysqli_prepare($conn, "UPDATE subjects SET syllabus_path = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "si", $dest, $id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    header("Location: subject-detail.php?id={$id}&uploaded=1");
                    exit;
                } else {
                    $errors[] = "Failed to save file. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?> — AcePath Hub</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title">
            <?= htmlspecialchars($subject['exam_body_name'], ENT_QUOTES, 'UTF-8') ?>
            — <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php if (has_role(ROLE_ADMIN)): ?>
            <a href="subject-edit.php?id=<?= $id ?>" class="btn-qs-sm">Edit</a>
            <form method="POST" action="subject-delete.php" style="display:inline;"
                  onsubmit="return confirm('Delete this subject?');">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn-qs-danger">Delete</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <a href="subject-list.php?exam_body_id=<?= $exam_body_id ?>" class="btn-qs-sm">← Back</a>
    </div>

    <?php if ($uploaded): ?>
        <div class="alert alert-success">Syllabus uploaded successfully.</div>
    <?php elseif ($removed): ?>
        <div class="alert alert-success">Syllabus removed.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="qs-card mb-4">
        <div class="qs-card-head">Syllabus</div>
        <div class="qs-card-body">
            <?php if (!empty($subject['syllabus_path']) && file_exists($subject['syllabus_path'])): ?>
                <a href="<?= htmlspecialchars($subject['syllabus_path'], ENT_QUOTES, 'UTF-8') ?>"
                   target="_blank" class="btn-qs-gold">View Syllabus (PDF)</a>
                <?php if (has_role(ROLE_ADMIN)): ?>
                    <form method="POST" style="display:inline;"
                          onsubmit="return confirm('Remove this syllabus?');">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn-qs-danger ms-2">Remove</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="qs-empty">No syllabus uploaded yet.</p>
                <?php if (has_role(ROLE_ADMIN)): ?>
                    <form method="POST" enctype="multipart/form-data" class="mt-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="upload">
                        <input type="file" name="syllabus" accept=".pdf" class="form-control mb-2" required>
                        <button type="submit" class="btn-qs-gold">Upload Syllabus</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include "inc/footer.php"; ?>
</body>
</html>
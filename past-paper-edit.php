<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

$stmt = mysqli_prepare($conn,
    "SELECT pp.id, pp.subject_id, pp.year, pp.duration_minutes, pp.total_questions, pp.status,
            s.name AS subject_name, s.exam_body_id
     FROM past_papers pp
     JOIN subjects s ON s.id = pp.subject_id
     WHERE pp.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$paper = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$paper) {
    header("Location: exam-body-list.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $year             = (int)($_POST['year'] ?? 0);
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 0);
    $total_questions  = (int)($_POST['total_questions'] ?? 0);
    $status           = $_POST['status'] ?? '';

    if ($year < 1990 || $year > (int)date('Y'))   $errors[] = "Invalid year.";
    if ($duration_minutes <= 0)                    $errors[] = "Duration must be greater than 0.";
    if ($total_questions <= 0)                     $errors[] = "Total questions must be greater than 0.";
    if (!in_array($status, ['active','inactive']))  $errors[] = "Invalid status.";

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn,
            "UPDATE past_papers SET year=?, duration_minutes=?, total_questions=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "iiisi", $year, $duration_minutes, $total_questions, $status, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: past-paper-list.php?subject_id={$paper['subject_id']}&updated=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Past Paper — AcePath Hub</title>
    <?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-2 mb-3">
                <a href="past-paper-list.php?subject_id=<?= (int)$paper['subject_id'] ?>"
                   class="btn btn-sm btn-outline-secondary">&larr; Back</a>
                <h5 class="mb-0">Edit Past Paper — <?= htmlspecialchars($paper['subject_name'], ENT_QUOTES, 'UTF-8') ?></h5>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_input() ?>

                <div class="mb-3">
                    <label class="form-label">Year <span class="text-danger">*</span></label>
                    <input type="number" name="year" class="form-control"
                           value="<?= (int)($_POST['year'] ?? $paper['year']) ?>"
                           min="1990" max="<?= date('Y') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                    <input type="number" name="duration_minutes" class="form-control"
                           value="<?= (int)($_POST['duration_minutes'] ?? $paper['duration_minutes']) ?>"
                           min="1" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Questions <span class="text-danger">*</span></label>
                    <input type="number" name="total_questions" class="form-control"
                           value="<?= (int)($_POST['total_questions'] ?? $paper['total_questions']) ?>"
                           min="1" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= $paper['status']==='active'  ?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $paper['status']==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn"
                        style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                    Update Past Paper
                </button>
            </form>
        </div>
    </div>
</div>
<?php include("inc/footer.php"); ?>
</body>
</html>
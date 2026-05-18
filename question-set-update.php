<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';
require_once 'src/util/mcq_image.php';

require_role(ROLE_ADMIN);

if (!isset($_GET['question_set_id']) || !ctype_digit($_GET['question_set_id'])) {
    header('Location: home.php');
    exit;
}
$question_set_id = (int)$_GET['question_set_id'];

// 1. Fetch current set data
$stmt = mysqli_prepare($conn,
    'SELECT qs.id, qs.topic_id, qs.image_path, qs.passage_text, qs.source,
            t.subject_id
     FROM question_sets qs
     JOIN topics t ON t.id = qs.topic_id
     WHERE qs.id = ?
     LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $question_set_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$set = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$set) {
    header('Location: home.php');
    exit;
}

// 2. Topics in same subject for dropdown
$stmt = mysqli_prepare($conn,
    'SELECT id, name FROM topics WHERE subject_id = ? ORDER BY name ASC');
mysqli_stmt_bind_param($stmt, 'i', $set['subject_id']);
mysqli_stmt_execute($stmt);
$res    = mysqli_stmt_get_result($stmt);
$topics = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $new_topic_id   = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
    $new_passage    = trim($_POST['passage_text'] ?? '');
    $new_source     = $_POST['source'] ?? '';
    $delete_image   = isset($_POST['delete_image']);

    // Validate
    if (!$new_topic_id) {
        $errors[] = 'Invalid topic selected.';
    } else {
        // Confirm topic belongs to same subject
        $stmt = mysqli_prepare($conn,
            'SELECT id FROM topics WHERE id = ? AND subject_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $new_topic_id, $set['subject_id']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $valid_topic = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);
        if (!$valid_topic) $errors[] = 'Invalid topic selected.';
    }

    if (!in_array($new_source, ['practice', 'past_paper'], true)) {
        $errors[] = 'Invalid source selected.';
    }

    // Handle image
    $new_image_path = $set['image_path'];

    if ($delete_image && $set['image_path']) {
        delete_mcq_image($set['image_path']);
        $new_image_path = null;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = upload_mcq_image($_FILES['image']);
        if ($uploaded === false) {
            $errors[] = 'Invalid image. Must be JPG, PNG or WebP under 2MB.';
        } else {
            // Delete old image if exists and not already deleted
            if ($set['image_path'] && !$delete_image) {
                delete_mcq_image($set['image_path']);
            }
            $new_image_path = $uploaded;
        }
    }

    if (empty($errors)) {
        $passage_val = $new_passage !== '' ? $new_passage : null;

        $stmt = mysqli_prepare($conn,
            'UPDATE question_sets
             SET topic_id = ?, image_path = ?, passage_text = ?, source = ?
             WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'isssi',
            $new_topic_id,
            $new_image_path,
            $passage_val,
            $new_source,
            $question_set_id
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header('Location: mcq-details.php?question_set_id=' . $question_set_id . '&ok=1');
            exit;
        }

        mysqli_stmt_close($stmt);
        $errors[] = 'Update failed. Try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Question Set #<?= $question_set_id ?></title>
    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title">Update Question Set #<?= $question_set_id ?></div>
        <a href="mcq-details.php?question_set_id=<?= $question_set_id ?>" class="btn-qs-sm">
            &larr; Back
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="user-form">
        <?= csrf_input() ?>

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Topic</label>
                <select name="topic_id" class="form-select" required>
                    <?php foreach ($topics as $t): ?>
                        <option value="<?= $t['id'] ?>"
                            <?= $t['id'] == $set['topic_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Source</label>
                <select name="source" class="form-select" required>
                    <option value="practice"
                        <?= $set['source'] === 'practice' ? 'selected' : '' ?>>
                        Practice
                    </option>
                    <option value="past_paper"
                        <?= $set['source'] === 'past_paper' ? 'selected' : '' ?>>
                        Past Paper
                    </option>
                </select>
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Passage Text</label>
                <textarea name="passage_text" class="form-control" rows="4"
                          placeholder="Leave empty to clear passage"><?= htmlspecialchars($set['passage_text'] ?? '') ?></textarea>
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Image</label>
                <?php if ($set['image_path']): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars($set['image_path']) ?>"
                             alt="Current image" class="mcqd-image-thumb">
                        <label class="form-check-label ms-2">
                            <input type="checkbox" name="delete_image" value="1">
                            Delete current image
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control"
                       accept="image/jpeg,image/png,image/webp">
                <small class="text-muted">Upload new image to replace current. Max 2MB.</small>
            </div>

            <div class="col-md-12">
                <button type="submit" class="btn-qs-gold">Update Set</button>
            </div>

        </div>
    </form>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
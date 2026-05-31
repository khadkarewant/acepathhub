<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

$question_id     = isset($_GET['question_id'])     && ctype_digit($_GET['question_id'])     ? (int)$_GET['question_id']     : 0;
$question_set_id = isset($_GET['question_set_id']) && ctype_digit($_GET['question_set_id']) ? (int)$_GET['question_set_id'] : 0;

if ($question_id === 0 || $question_set_id === 0) {
    header('Location: home.php');
    exit;
}

// Fetch question
$stmt = mysqli_prepare($conn,
    'SELECT id, question_set_id, question, option_a, option_b, option_c, option_d, answer, explanation
     FROM questions
     WHERE id = ? AND question_set_id = ?
     LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ii', $question_id, $question_set_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$q   = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$q) {
    header('Location: home.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $new_question    = trim($_POST['question']    ?? '');
    $new_option_a    = trim($_POST['option_a']    ?? '');
    $new_option_b    = trim($_POST['option_b']    ?? '');
    $new_option_c    = trim($_POST['option_c']    ?? '');
    $new_option_d    = trim($_POST['option_d']    ?? '');
    $new_answer      = strtoupper(trim($_POST['answer'] ?? ''));
    $new_explanation = trim($_POST['explanation'] ?? '');

    if ($new_question === '')  $errors[] = 'Question text is required.';
    if ($new_option_a === '')  $errors[] = 'Option A is required.';
    if ($new_option_b === '')  $errors[] = 'Option B is required.';
    if ($new_option_c === '')  $errors[] = 'Option C is required.';
    if ($new_option_d === '')  $errors[] = 'Option D is required.';
    if (!in_array($new_answer, ['A','B','C','D'], true)) $errors[] = 'Invalid answer.';

    if (empty($errors)) {
        $explanation_val = $new_explanation !== '' ? $new_explanation : null;

        $stmt = mysqli_prepare($conn,
            'UPDATE questions
             SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?,
                 answer = ?, explanation = ?
             WHERE id = ? AND question_set_id = ?
             LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'sssssssii',
            $new_question,
            $new_option_a,
            $new_option_b,
            $new_option_c,
            $new_option_d,
            $new_answer,
            $explanation_val,
            $question_id,
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

    // Re-populate on error
    $q = array_merge($q, [
        'question'    => $new_question,
        'option_a'    => $new_option_a,
        'option_b'    => $new_option_b,
        'option_c'    => $new_option_c,
        'option_d'    => $new_option_d,
        'answer'      => $new_answer,
        'explanation' => $new_explanation,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question #<?= $question_id ?></title>
    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title">Edit Question #<?= $question_id ?></div>
        <a href="mcq-details.php?question_set_id=<?= $question_set_id ?>" class="btn-qs-sm">← Back</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="ep-form">
        <?= csrf_input() ?>

        <div class="ep-field">
            <label class="ep-label">Question <span class="ep-required">*</span></label>
            <textarea name="question" class="form-control ep-input" rows="4" required><?= htmlspecialchars($q['question']) ?></textarea>
        </div>

        <div class="ep-field">
            <label class="ep-label">Option A <span class="ep-required">*</span></label>
            <input type="text" name="option_a" class="form-control ep-input" required
                   value="<?= htmlspecialchars($q['option_a']) ?>">
        </div>

        <div class="ep-field">
            <label class="ep-label">Option B <span class="ep-required">*</span></label>
            <input type="text" name="option_b" class="form-control ep-input" required
                   value="<?= htmlspecialchars($q['option_b']) ?>">
        </div>

        <div class="ep-field">
            <label class="ep-label">Option C <span class="ep-required">*</span></label>
            <input type="text" name="option_c" class="form-control ep-input" required
                   value="<?= htmlspecialchars($q['option_c']) ?>">
        </div>

        <div class="ep-field">
            <label class="ep-label">Option D <span class="ep-required">*</span></label>
            <input type="text" name="option_d" class="form-control ep-input" required
                   value="<?= htmlspecialchars($q['option_d']) ?>">
        </div>

        <div class="ep-field">
            <label class="ep-label">Answer <span class="ep-required">*</span></label>
            <select name="answer" class="form-control ep-input" required>
                <?php foreach (['A','B','C','D'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $q['answer'] === $opt ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ep-field">
            <label class="ep-label">Explanation</label>
            <textarea name="explanation" class="form-control ep-input" rows="3"><?= htmlspecialchars($q['explanation'] ?? '') ?></textarea>
        </div>

        <div class="ep-submit">
            <button type="submit" class="btn-qs-gold">Save Changes</button>
            <a href="mcq-details.php?question_set_id=<?= $question_set_id ?>" class="btn-qs-sm">Cancel</a>
        </div>

    </form>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
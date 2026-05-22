<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/util/mcq_image.php";
require_role(ROLE_DATA_ENTRY);

$topic_id = (int)($_GET['topic_id'] ?? 0);

$past_paper_id = (int)($_GET['past_paper_id'] ?? 0);

if ($topic_id === 0 && $past_paper_id === 0) {
    header("Location: home.php");
    exit;
}

$errors = [];
$source = 'practice';
$past_paper = null;

if ($past_paper_id > 0) {
    $stmt = mysqli_prepare($conn,
        "SELECT pp.id, pp.year, s.name AS subject_name, eb.name AS exam_body_name
         FROM past_papers pp
         JOIN subjects s ON s.id = pp.subject_id
         JOIN exam_bodies eb ON eb.id = s.exam_body_id
         WHERE pp.id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $past_paper_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $past_paper = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$past_paper) {
        header("Location: home.php");
        exit;
    }

    $source = 'past_paper';

} else {
    $stmt = mysqli_prepare($conn, "SELECT id, name FROM topics WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $topic_id);
    mysqli_stmt_execute($stmt);
    $topic_result = mysqli_stmt_get_result($stmt);
    $topic = mysqli_fetch_assoc($topic_result);
    mysqli_stmt_close($stmt);

    if (!$topic) {
        header("Location: home.php");
        exit;
    }

    $topic_name = $topic['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $topic_id     = (int)($_POST['topic_id'] ?? 0);
    $past_paper_id = (int)($_POST['past_paper_id'] ?? $past_paper_id);
    $source = $past_paper_id > 0 ? 'past_paper' : 'practice';
    $passage_text = trim($_POST['passage_text'] ?? '');
    $questions    = $_POST['questions'] ?? [];

    // Image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $image_path = upload_mcq_image($_FILES['image']);
        if ($image_path === false) {
            $errors[] = "Invalid image. Must be JPG, PNG or WebP under 2MB.";
        }
    }

    if (empty($questions)) {
        $errors[] = "At least one question is required.";
    }

    foreach ($questions as $i => $q) {
        $num = $i + 1;
        if (trim($q['question'] ?? '') === '') $errors[] = "Question {$num}: question text is required.";
        if (trim($q['option_a'] ?? '') === '')  $errors[] = "Question {$num}: Option A is required.";
        if (trim($q['option_b'] ?? '') === '')  $errors[] = "Question {$num}: Option B is required.";
        if (trim($q['option_c'] ?? '') === '')  $errors[] = "Question {$num}: Option C is required.";
        if (trim($q['option_d'] ?? '') === '')  $errors[] = "Question {$num}: Option D is required.";
        if (!in_array($q['answer'] ?? '', ['A','B','C','D'], true)) {
            $errors[] = "Question {$num}: select a valid answer.";
        }
    }
    if ($past_paper_id > 0 && (int)($_POST['question_no'] ?? 0) <= 0) {
        $errors[] = "Question No is required and must be greater than 0.";
    }

    if (empty($errors)) {
        $created_on  = date('Y-m-d');
        $created_at  = date('H:i:s');
        $passage_val = $passage_text !== '' ? $passage_text : null;

        $question_no  = $past_paper_id > 0 ? (int)($_POST['question_no'] ?? 0) : null;
        $topic_id_val = $past_paper_id > 0 ? null : $topic_id;
        $pp_id_val    = $past_paper_id > 0 ? $past_paper_id : null;

        $stmt = mysqli_prepare($conn,
            "INSERT INTO question_sets (topic_id, image_path, passage_text, source, past_paper_id, question_no, verified, status, created_by, created_on, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 'draft', ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "isssiiiss",
            $topic_id_val, $image_path, $passage_val, $source, $pp_id_val, $question_no, $user_id, $created_on, $created_at
        );
        mysqli_stmt_execute($stmt);
        $set_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO questions (question_set_id, question, option_a, option_b, option_c, option_d, answer, explanation)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($questions as $q) {
            $explanation = trim($q['explanation'] ?? '') !== '' ? trim($q['explanation']) : null;
            mysqli_stmt_bind_param($stmt, "isssssss",
                $set_id,
                $q['question'],
                $q['option_a'],
                $q['option_b'],
                $q['option_c'],
                $q['option_d'],
                $q['answer'],
                $explanation
            );
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);

        if ($past_paper_id > 0) {
            header("Location: mcq-add.php?past_paper_id={$past_paper_id}&success=1");
        } else {
            header("Location: mcq-add.php?topic_id={$topic_id}&success=1");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add MCQ — AcePath Hub</title>
    <?php include("inc/links.php"); ?>

</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 p-3">

            <h2 style="color:var(--accent);">Add MCQ</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="mcqForm" method="POST" enctype="multipart/form-data">
                <?= csrf_input(); ?>

                <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                <?php if ($past_paper_id > 0): ?>
                    <input type="hidden" name="past_paper_id" value="<?= $past_paper_id ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <?php if ($past_paper_id > 0): ?>
                        <label class="form-label">Past Paper</label>
                        <input type="text" class="form-control"
                               value="<?= htmlspecialchars($past_paper['exam_body_name'] . ' — ' . $past_paper['subject_name'] . ' — ' . $past_paper['year'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    <?php else: ?>
                        <label class="form-label">Topic</label>
                        <input type="text" class="form-control"
                               value="<?= htmlspecialchars($topic_name, ENT_QUOTES, 'UTF-8') ?>" disabled>
                    <?php endif; ?>
                </div>

                <?php if ($past_paper_id > 0): ?>
                <div class="mb-3">
                    <label class="form-label">Question No <span class="text-danger">*</span></label>
                    <input type="number" name="question_no" class="form-control"
                           value="<?= (int)($_POST['question_no'] ?? '') ?>" min="1" required>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Image <small class="text-muted">(optional, max 2MB)</small></label>
                    <input type="file" name="image" class="form-control"
                           accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="mb-3">
                    <label class="form-label">Passage Text <small class="text-muted">(optional)</small></label>
                    <textarea name="passage_text" class="form-control" rows="3"
                              placeholder="Shared passage for all questions below..."><?= htmlspecialchars($_POST['passage_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div id="questions-container">
                    <div class="question-block" id="question-block-0">
                        <h6 style="color:var(--accent);">Question 1</h6>

                        <div class="mb-2">
                            <label class="form-label">Question</label>
                            <textarea name="questions[0][question]" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Option A</label>
                            <input type="text" name="questions[0][option_a]" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Option B</label>
                            <input type="text" name="questions[0][option_b]" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Option C</label>
                            <input type="text" name="questions[0][option_c]" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Option D</label>
                            <input type="text" name="questions[0][option_d]" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Correct Answer</label>
                            <select name="questions[0][answer]" class="form-select" required>
                                <option value="">— Select —</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Explanation <small class="text-muted">(optional)</small></label>
                            <textarea name="questions[0][explanation]" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <button type="button" id="add-question-btn" class="btn btn-outline-secondary mb-3">
                    + Add Another Question
                </button>

                <br>
                <button type="submit" class="btn"
                        style="background:var(--accent);color:#0a0a0f;font-weight:600;">
                    Save MCQ
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if (isset($_GET['success'])): ?>
        alert("MCQ added successfully!");
    <?php endif; ?>

    let questionCount = 1;

    document.getElementById('add-question-btn').addEventListener('click', function () {
        const index = questionCount;
        const container = document.getElementById('questions-container');
        const block = document.createElement('div');
        block.className = 'question-block';
        block.id = 'question-block-' + index;
        block.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 style="color:var(--accent);">Question ${index + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="removeQuestion(${index})">Remove</button>
            </div>
            <div class="mb-2">
                <label class="form-label">Question</label>
                <textarea name="questions[${index}][question]" class="form-control" rows="3" required></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Option A</label>
                <input type="text" name="questions[${index}][option_a]" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Option B</label>
                <input type="text" name="questions[${index}][option_b]" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Option C</label>
                <input type="text" name="questions[${index}][option_c]" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Option D</label>
                <input type="text" name="questions[${index}][option_d]" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Correct Answer</label>
                <select name="questions[${index}][answer]" class="form-select" required>
                    <option value="">— Select —</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Explanation <small class="text-muted">(optional)</small></label>
                <textarea name="questions[${index}][explanation]" class="form-control" rows="2"></textarea>
            </div>
        `;
        container.appendChild(block);
        questionCount++;
    });

    function removeQuestion(index) {
        const block = document.getElementById('question-block-' + index);
        if (block) block.remove();
    }

    document.getElementById('mcqForm').addEventListener('submit', function (e) {
        const selects = document.querySelectorAll('select[name$="[answer]"]');
        for (let s of selects) {
            if (s.value === '') {
                alert('Please select the correct answer for all questions.');
                e.preventDefault();
                return false;
            }
        }
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'Saving...';
    });
</script>

<?php include("inc/footer.php"); ?>
</body>
</html>
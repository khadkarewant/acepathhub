<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

$topic_id      = isset($_GET['topic_id'])      && ctype_digit($_GET['topic_id'])      ? (int)$_GET['topic_id']      : 0;
$past_paper_id = isset($_GET['past_paper_id']) && ctype_digit($_GET['past_paper_id']) ? (int)$_GET['past_paper_id'] : 0;

if ($topic_id === 0 && $past_paper_id === 0) {
    header('Location: draft-mcqs.php');
    exit;
}

// 1. Validate + breadcrumb
$topic      = null;
$past_paper = null;

if ($past_paper_id > 0) {
    $stmt = mysqli_prepare($conn,
        'SELECT pp.id, pp.year, s.name AS subject_name, eb.name AS exam_body_name
         FROM past_papers pp
         JOIN subjects s     ON s.id  = pp.subject_id
         JOIN exam_bodies eb ON eb.id = s.exam_body_id
         WHERE pp.id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $past_paper_id);
    mysqli_stmt_execute($stmt);
    $res        = mysqli_stmt_get_result($stmt);
    $past_paper = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);

    if (!$past_paper) {
        header('Location: draft-mcqs.php');
        exit;
    }
} else {
    $stmt = mysqli_prepare($conn,
        'SELECT t.name AS topic_name, s.name AS subject_name, eb.name AS exam_body_name
         FROM topics t
         JOIN subjects s     ON s.id  = t.subject_id
         JOIN exam_bodies eb ON eb.id = s.exam_body_id
         WHERE t.id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $topic_id);
    mysqli_stmt_execute($stmt);
    $res   = mysqli_stmt_get_result($stmt);
    $topic = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);

    if (!$topic) {
        header('Location: draft-mcqs.php');
        exit;
    }
}

// 2. Draft sets
$bind_id = $past_paper_id > 0 ? $past_paper_id : $topic_id;
$where   = $past_paper_id > 0 ? 'qs.past_paper_id = ?' : 'qs.topic_id = ?';

$stmt = mysqli_prepare($conn,
    "SELECT qs.id, qs.source, qs.image_path, qs.passage_text, qs.question_no, qs.created_by,
            COUNT(q.id) AS total_questions,
            MIN(q.id)   AS first_question_id
     FROM question_sets qs
     LEFT JOIN questions q ON q.question_set_id = qs.id
     WHERE {$where} AND qs.verified = 1 AND qs.status = 'draft'
     GROUP BY qs.id
     ORDER BY qs.id DESC");
mysqli_stmt_bind_param($stmt, 'i', $bind_id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$sets = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// 3. First questions — single IN() query
$first_questions = [];
if (!empty($sets)) {
    $first_ids = array_values(array_filter(array_column($sets, 'first_question_id')));
    if (!empty($first_ids)) {
        $placeholders = implode(',', array_fill(0, count($first_ids), '?'));
        $types        = str_repeat('i', count($first_ids));
        $stmt = mysqli_prepare($conn,
            "SELECT id, question_set_id, question, option_a, option_b, option_c, option_d, answer
             FROM questions
             WHERE id IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $types, ...$first_ids);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $first_questions[$row['question_set_id']] = $row;
        }
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft — <?= $past_paper ? htmlspecialchars($past_paper['subject_name'] . ' ' . $past_paper['year']) : htmlspecialchars($topic['topic_name']) ?></title>

    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div>
            <div class="qs-breadcrumb">
                <?php if ($past_paper): ?>
                    <?= htmlspecialchars($past_paper['exam_body_name']) ?>
                    &rsaquo; <?= htmlspecialchars($past_paper['subject_name']) ?>
                    &rsaquo; <?= (int)$past_paper['year'] ?>
                <?php else: ?>
                    <?= htmlspecialchars($topic['exam_body_name']) ?>
                    &rsaquo; <?= htmlspecialchars($topic['subject_name']) ?>
                    &rsaquo; <?= htmlspecialchars($topic['topic_name']) ?>
                <?php endif; ?>
                </div>
            <div class="qs-list-title">Verified question sets</div>
        </div>
        <span class="qs-total-label"><?= count($sets) ?> set<?= count($sets) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($sets)): ?>
        <div class="qs-empty">No verified question sets for this <?= $past_paper ? 'past paper' : 'topic' ?>.</div>
    <?php else: ?>

        <?php foreach ($sets as $set):
            $fq   = $first_questions[$set['id']] ?? null;
            $ans  = $fq ? strtolower(trim($fq['answer'])) : null;
            $more = (int)$set['total_questions'] - 1;
        ?>
        <div class="qs-card">

            <div class="qs-card-head">
                <span class="qs-card-id">#<?= $set['id'] ?></span>
                <?php if ($past_paper && $set['question_no']): ?>
                    <span class="badge badge-meta">Q<?= (int)$set['question_no'] ?></span>
                <?php endif; ?>

                <span class="badge badge-source-<?= $set['source'] === 'past_paper' ? 'past' : 'practice' ?>">
                    <?= $set['source'] === 'past_paper' ? 'past paper' : 'practice' ?>
                </span>
                <span class="badge badge-meta">
                    <?= (int)$set['total_questions'] ?> question<?= $set['total_questions'] != 1 ? 's' : '' ?>
                </span>
                <?php if ($set['image_path']): ?>
                    <span class="badge badge-meta">image</span>
                <?php endif; ?>
                <?php if ($set['passage_text']): ?>
                    <span class="badge badge-meta">passage</span>
                <?php endif; ?>
                <span class="badge badge-draft qs-card-status">draft</span>
            </div>

            <div class="qs-card-body">
                <?php if ($fq): ?>
                    <div class="qs-q-row">
                        <span class="qs-q-num">Q1</span>
                        <div class="qs-q-content">
                            <div class="qs-q-text"><?= htmlspecialchars($fq['question']) ?></div>
                            <div class="qs-opts">
                                <div class="qs-opt <?= $ans === 'a' ? 'qs-opt-correct' : '' ?>">
                                    A. <?= htmlspecialchars($fq['option_a']) ?>
                                </div>
                                <div class="qs-opt <?= $ans === 'b' ? 'qs-opt-correct' : '' ?>">
                                    B. <?= htmlspecialchars($fq['option_b']) ?>
                                </div>
                                <div class="qs-opt <?= $ans === 'c' ? 'qs-opt-correct' : '' ?>">
                                    C. <?= htmlspecialchars($fq['option_c']) ?>
                                </div>
                                <div class="qs-opt <?= $ans === 'd' ? 'qs-opt-correct' : '' ?>">
                                    D. <?= htmlspecialchars($fq['option_d']) ?>
                                </div>
                            </div>
                            <?php if ($more > 0): ?>
                                <div class="qs-more-hint">
                                    + <?= $more ?> more question<?= $more > 1 ? 's' : '' ?> in this set.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="qs-no-questions">No questions added yet.</div>
                <?php endif; ?>
            </div>

            <div class="qs-card-footer">
                <a href="mcq-details.php?question_set_id=<?= $set['id'] ?>&ref=draft<?= $past_paper ? '&past_paper_id=' . $past_paper_id : '&topic_id=' . $topic_id ?>" class="btn-qs-sm">
                    Details
                </a>
                <form method="POST" action="mcq-status-change.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="question_set_id" value="<?= $set['id'] ?>">
                    <input type="hidden" name="status" value="published">
                    <button type="submit" class="btn-qs-gold"
                            onclick="return confirm('Publish set #<?= $set['id'] ?>?')">
                        Publish
                    </button>
                </form>
                <form method="POST" action="mcq-delete.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="question_set_id" value="<?= $set['id'] ?>">
                    <button type="submit" class="btn-qs-danger"
                            onclick="return confirm('Delete set #<?= $set['id'] ?> permanently?')">
                        Delete
                    </button>
                </form>
                <span class="qs-creator">user #<?= (int)$set['created_by'] ?></span>
            </div>

        </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
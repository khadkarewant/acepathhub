<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';
require_once 'src/config/roles.php';

require_role(ROLE_ADMIN);

if (!isset($_GET['question_set_id']) || !ctype_digit($_GET['question_set_id'])) {
    header('Location: home.php');
    exit;
}
$question_set_id = (int)$_GET['question_set_id'];

$ref               = $_GET['ref'] ?? '';
$ref_topic_id      = isset($_GET['topic_id'])      && ctype_digit($_GET['topic_id'])      ? (int)$_GET['topic_id']      : 0;
$ref_past_paper_id = isset($_GET['past_paper_id']) && ctype_digit($_GET['past_paper_id']) ? (int)$_GET['past_paper_id'] : 0;

if ($ref === 'unverified') {
    if ($ref_past_paper_id > 0) {
        $back_url = 'unverified-mcq-details.php?past_paper_id=' . $ref_past_paper_id;
    } elseif ($ref_topic_id > 0) {
        $back_url = 'unverified-mcq-details.php?topic_id=' . $ref_topic_id;
    } else {
        $back_url = '';
    }
} elseif ($ref === 'draft') {
    if ($ref_past_paper_id > 0) {
        $back_url = 'draft-mcq-details.php?past_paper_id=' . $ref_past_paper_id;
    } elseif ($ref_topic_id > 0) {
        $back_url = 'draft-mcq-details.php?topic_id=' . $ref_topic_id;
    } else {
        $back_url = '';
    }
} elseif ($ref === 'qsl') {
    $back_url = $ref_topic_id > 0 ? 'question-set-list.php?topic_id=' . $ref_topic_id : '';
} else {
    $back_url = '';
}

// 1. Fetch question_set + breadcrumb in one query
$stmt = mysqli_prepare($conn,
    'SELECT qs.id, qs.source, qs.image_path, qs.passage_text,
            qs.verified, qs.status, qs.created_by, qs.created_on,
            qs.past_paper_id, qs.question_no,
            t.name  AS topic_name,  t.id AS topic_id,
            s.name  AS subject_name,
            eb.name AS exam_body_name,
            pp.year AS past_paper_year
     FROM question_sets qs
     LEFT JOIN topics      t  ON t.id  = qs.topic_id
     LEFT JOIN past_papers pp ON pp.id = qs.past_paper_id
     LEFT JOIN subjects    s  ON s.id  = COALESCE(t.subject_id, pp.subject_id)
     LEFT JOIN exam_bodies eb ON eb.id = s.exam_body_id
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

// 2. Fetch all questions for this set
$stmt = mysqli_prepare($conn,
    'SELECT id, question, option_a, option_b, option_c, option_d, answer, explanation
     FROM questions
     WHERE question_set_id = ?
     ORDER BY id ASC');
mysqli_stmt_bind_param($stmt, 'i', $question_set_id);
mysqli_stmt_execute($stmt);
$res       = mysqli_stmt_get_result($stmt);
$questions = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set #<?= $question_set_id ?> — <?= $set['source'] === 'past_paper' ? htmlspecialchars($set['subject_name'] . ' ' . $set['past_paper_year']) : htmlspecialchars($set['topic_name']) ?></title>    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div>
            <div class="qs-breadcrumb">
                <?= htmlspecialchars($set['exam_body_name']) ?>
                &rsaquo; <?= htmlspecialchars($set['subject_name']) ?>
                <?php if ($set['source'] === 'past_paper'): ?>
                    &rsaquo; <?= (int)$set['past_paper_year'] ?>
                <?php else: ?>
                    &rsaquo; <?= htmlspecialchars($set['topic_name']) ?>
                <?php endif; ?>
                &rsaquo; Set #<?= $question_set_id ?>
            </div>
            <div class="d-flex align-items-center gap-2 mt-1">
                <?php if ($back_url !== ''): ?>
                    <a href="<?= htmlspecialchars($back_url) ?>" class="btn-qs-sm">&larr; Back</a>
                <?php endif; ?>
                <div class="qs-list-title">Question set details</div>
            </div>
        </div>

        <div class="mcqd-actions">
            <?php if ($set['verified'] == 0): ?>
                <form method="POST" action="mcq-verify.php">
                    <?= csrf_input() ?>
                    <input type="hidden" name="question_set_id" value="<?= $question_set_id ?>">
                    <button type="submit" class="btn-qs-gold"
                            onclick="return confirm('Verify this set ?')">
                        Verify
                    </button>
                </form>
            <?php elseif ($set['verified'] == 1 && $set['status'] === 'draft'): ?>
            <form method="POST" action="mcq-status-change.php">
                <?= csrf_input() ?>
                <input type="hidden" name="question_set_id" value="<?= $question_set_id ?>">
                <input type="hidden" name="status" value="published">
                <button type="submit" class="btn-qs-gold"
                        onclick="return confirm('Publish this set?')">
                    Publish
                </button>
            </form>
            <?php elseif ($set['verified'] == 1 && $set['status'] === 'published'): ?>
            <form method="POST" action="mcq-status-change.php">
                <?= csrf_input() ?>
                <input type="hidden" name="question_set_id" value="<?= $question_set_id ?>">
                <input type="hidden" name="status" value="draft">
                <button type="submit" class="btn-qs-gold"
                        onclick="return confirm('Unpublish this set?')">
                    Unpublish
                </button>
            </form>
        <?php endif; ?>
        </div>
    </div>

    <!-- Set meta -->
    <div class="mcqd-meta">
        <span class="badge badge-source-<?= $set['source'] === 'past_paper' ? 'past' : 'practice' ?>">
            <?= $set['source'] === 'past_paper' ? 'past paper' : 'practice' ?>
        </span>
        <span class="badge <?= $set['verified'] ? 'badge-published' : 'badge-unverified' ?>">
            <?= $set['verified'] ? 'verified' : 'unverified' ?>
        </span>
        <span class="badge <?= $set['status'] === 'published' ? 'badge-published' : 'badge-draft' ?>">
            <?= htmlspecialchars($set['status']) ?>
        </span>
        <span class="badge badge-meta"><?= count($questions) ?> question<?= count($questions) != 1 ? 's' : '' ?></span>
        <?php if ($set['image_path']): ?>
            <span class="badge badge-meta">image</span>
        <?php endif; ?>
        <span class="mcqd-meta-date">
            added <?= htmlspecialchars($set['created_on']) ?> &middot; user #<?= (int)$set['created_by'] ?>
        </span>
    </div>

    <!-- Passage -->
    <?php if ($set['passage_text']): ?>
    <div class="mcqd-passage">
        <div class="mcqd-passage-label">Passage</div>
        <?= nl2br(htmlspecialchars($set['passage_text'])) ?>
    </div>
    <?php endif; ?>

    <!-- Image -->
    <?php if ($set['image_path']): ?>
    <div class="mcqd-image">
        <img src="<?= htmlspecialchars($set['image_path']) ?>" alt="Set image">
    </div>
    <?php endif; ?>

    <!-- Questions -->
    <?php if (empty($questions)): ?>
        <div class="qs-empty">No questions in this set.</div>
    <?php else: ?>
        <?php foreach ($questions as $i => $q):
            $ans = strtolower(trim($q['answer']));
        ?>
        <div class="mcqd-q-card">
            <div class="mcqd-q-head">
                <span class="qs-q-num">Q<?= $i + 1 ?></span>
                <span class="mcqd-q-id">#<?= $q['id'] ?></span>
            </div>
            <div class="mcqd-q-body">
                <div class="mcqd-q-text"><?= htmlspecialchars($q['question']) ?></div>
                <div class="mcqd-opts">
                    <?php foreach (['a','b','c','d'] as $opt): ?>
                    <div class="mcqd-opt <?= $ans === $opt ? 'mcqd-opt-correct' : '' ?>">
                        <?= strtoupper($opt) ?>. <?= htmlspecialchars($q['option_' . $opt]) ?>
                        <?= $ans === $opt ? '<span class="mcqd-tick">&#10003;</span>' : '' ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($q['explanation']): ?>
                <div class="mcqd-expl">
                    <span class="mcqd-expl-label">Explanation</span>
                    <?= nl2br(htmlspecialchars($q['explanation'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
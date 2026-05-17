<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

require_role(ROLE_ADMIN);

if (!isset($_GET['topic_id']) || !ctype_digit($_GET['topic_id'])) {
    header('Location: home.php');
    exit;
}
$topic_id = (int)$_GET['topic_id'];

$limit = 10;
$page  = (isset($_GET['page']) && ctype_digit($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;

// 1. Topic breadcrumb — validate topic exists
$stmt = mysqli_prepare($conn,
    'SELECT t.name AS topic_name, s.name AS subject_name, eb.name AS exam_body_name
     FROM topics t
     JOIN subjects s  ON s.id  = t.subject_id
     JOIN exam_bodies eb ON eb.id = s.exam_body_id
     WHERE t.id = ?');
mysqli_stmt_bind_param($stmt, 'i', $topic_id);
mysqli_stmt_execute($stmt);
$res   = mysqli_stmt_get_result($stmt);
$topic = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$topic) {
    header('Location: home.php');
    exit;
}

// 2. Total count for pagination
$stmt = mysqli_prepare($conn,
    'SELECT COUNT(*) AS total
     FROM question_sets
     WHERE topic_id = ? AND verified = 1 AND status = \'published\'');
mysqli_stmt_bind_param($stmt, 'i', $topic_id);
mysqli_stmt_execute($stmt);
$res   = mysqli_stmt_get_result($stmt);
$total = (int)mysqli_fetch_assoc($res)['total'];
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$total_pages = max(1, (int)ceil($total / $limit));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $limit;

// 3. Paginated sets with question count + first question id
$stmt = mysqli_prepare($conn,
    'SELECT qs.id, qs.source, qs.image_path, qs.passage_text, qs.created_by,
            COUNT(q.id) AS total_questions,
            MIN(q.id)   AS first_question_id
     FROM question_sets qs
     LEFT JOIN questions q ON q.question_set_id = qs.id
     WHERE qs.topic_id = ? AND qs.verified = 1 AND qs.status = \'published\'
     GROUP BY qs.id
     ORDER BY qs.id DESC
     LIMIT ? OFFSET ?');
mysqli_stmt_bind_param($stmt, 'iii', $topic_id, $limit, $offset);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$sets = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// 4. First questions — single IN() query
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
    <title>Question Sets — <?= htmlspecialchars($topic['topic_name']) ?></title>
    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div>
            <div class="qs-breadcrumb">
                <?= htmlspecialchars($topic['exam_body_name']) ?>
                &rsaquo; <?= htmlspecialchars($topic['subject_name']) ?>
                &rsaquo; <?= htmlspecialchars($topic['topic_name']) ?>
            </div>
            <div class="qs-list-title">Published question sets</div>
        </div>
        <span class="qs-total-label">
            <?= $total ?> set<?= $total !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($sets)): ?>
        <div class="qs-empty">No published question sets for this topic.</div>

    <?php else: ?>

        <?php foreach ($sets as $set):
            $fq   = $first_questions[$set['id']] ?? null;
            $ans  = $fq ? strtolower(trim($fq['answer'])) : null;
            $more = (int)$set['total_questions'] - 1;
        ?>
        <div class="qs-card">

            <div class="qs-card-head">
                <span class="qs-card-id">#<?= $set['id'] ?></span>
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
                <span class="badge badge-published qs-card-status">published</span>
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
                                    + <?= $more ?> more question<?= $more > 1 ? 's' : '' ?> &mdash; view details
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="qs-no-questions">No questions added yet.</div>
                <?php endif; ?>
            </div>

            <div class="qs-card-footer">
                <a href="mcq-details.php?question_set_id=<?= $set['id'] ?>" class="btn-qs-gold">
                    Details
                </a>
                <span class="qs-creator">user #<?= (int)$set['created_by'] ?></span>
            </div>

        </div>
        <?php endforeach; ?>

        <?php if ($total_pages > 1): ?>
        <div class="qs-pagination">
            <span class="qs-pag-info">
                Showing <?= $offset + 1 ?>&ndash;<?= min($offset + $limit, $total) ?> of <?= $total ?> sets
            </span>
            <div class="qs-pag-controls">
                <?php if ($page > 1): ?>
                    <a href="?topic_id=<?= $topic_id ?>&page=<?= $page - 1 ?>" class="pag-btn">&lsaquo;</a>
                <?php else: ?>
                    <span class="pag-btn pag-disabled">&lsaquo;</span>
                <?php endif; ?>

                <?php
                $range_start = max(1, $page - 2);
                $range_end   = min($total_pages, $page + 2);
                for ($p = $range_start; $p <= $range_end; $p++):
                ?>
                    <?php if ($p === $page): ?>
                        <span class="pag-btn pag-active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?topic_id=<?= $topic_id ?>&page=<?= $p ?>" class="pag-btn"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?topic_id=<?= $topic_id ?>&page=<?= $page + 1 ?>" class="pag-btn">&rsaquo;</a>
                <?php else: ?>
                    <span class="pag-btn pag-disabled">&rsaquo;</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
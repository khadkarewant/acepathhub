<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_STUDENT);

// ── AJAX RESET (subset level) ─────────────────────────────────────────────
if (isset($_POST['ajax_reset'])) {
    header('Content-Type: application/json');
    csrf_verify();

    $purchased_id = isset($_POST['purchased_id']) && ctype_digit($_POST['purchased_id']) ? (int)$_POST['purchased_id'] : 0;
    $topic_id     = isset($_POST['topic_id'])     && ctype_digit($_POST['topic_id'])     ? (int)$_POST['topic_id']     : 0;
    $subset_no    = isset($_POST['subset_no'])    && ctype_digit($_POST['subset_no'])    ? (int)$_POST['subset_no']    : 0;

    if ($purchased_id === 0 || $topic_id === 0 || $subset_no === 0) {
        echo json_encode(['status' => 'error', 'error' => 'Invalid request']);
        exit;
    }

    // Verify ownership
    $stmt = mysqli_prepare($conn,
        "SELECT id FROM purchased_products WHERE id = ? AND user_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $purchased_id, $user_id);
    mysqli_stmt_execute($stmt);
    $r    = mysqli_stmt_get_result($stmt);
    $owns = mysqli_fetch_assoc($r);
    mysqli_free_result($r);
    mysqli_stmt_close($stmt);

    if (!$owns) {
        echo json_encode(['status' => 'error', 'error' => 'Access denied']);
        exit;
    }

    $stmt = mysqli_prepare($conn,
        "DELETE pa FROM practice_answers pa
         JOIN questions q      ON q.id  = pa.question_id
         JOIN question_sets qs ON qs.id = q.question_set_id
         WHERE pa.user_id = ? AND pa.purchased_id = ?
           AND qs.topic_id = ? AND qs.subset_no = ?"
    );
    mysqli_stmt_bind_param($stmt, 'iiii', $user_id, $purchased_id, $topic_id, $subset_no);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'ok']);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'error', 'error' => 'Reset failed']);
    }
    exit;
}

// ── Validate params ───────────────────────────────────────────────────────
$purchased_id = isset($_GET['purchased_id']) && ctype_digit($_GET['purchased_id']) ? (int)$_GET['purchased_id'] : 0;
$topic_id     = isset($_GET['topic_id'])     && ctype_digit($_GET['topic_id'])     ? (int)$_GET['topic_id']     : 0;

if ($purchased_id === 0 || $topic_id === 0) {
    header("Location: product-mine.php");
    exit;
}

// ── Validate purchase ─────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT pp.id, p.exam_body_id, p.name AS product_name, pp.expires_at
     FROM purchased_products pp
     JOIN products p ON p.id = pp.product_id
     WHERE pp.id = ? AND pp.user_id = ? AND pp.status = 'active'
       AND p.product_type = 'practice' AND pp.expires_at >= CURDATE()
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ii', $purchased_id, $user_id);
mysqli_stmt_execute($stmt);
$r        = mysqli_stmt_get_result($stmt);
$purchase = mysqli_fetch_assoc($r);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if (!$purchase) {
    header("Location: product-mine.php");
    exit;
}

// ── Validate topic belongs to this product's exam body ────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT t.id, t.name AS topic_name, t.subject_id,
            s.name AS subject_name,
            eb.name AS exam_body_name
     FROM topics t
     JOIN subjects s     ON s.id  = t.subject_id
     JOIN exam_bodies eb ON eb.id = s.exam_body_id
     WHERE t.id = ? AND s.exam_body_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ii', $topic_id, $purchase['exam_body_id']);
mysqli_stmt_execute($stmt);
$r     = mysqli_stmt_get_result($stmt);
$topic = mysqli_fetch_assoc($r);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if (!$topic) {
    header("Location: product-mine.php");
    exit;
}

// ── Subsets with progress ─────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT qs.subset_no,
            COUNT(DISTINCT q.id)           AS total_questions,
            COUNT(DISTINCT pa.question_id) AS answered,
            COALESCE(SUM(CASE WHEN pa.is_correct = 1 THEN 1 ELSE 0 END), 0) AS correct
     FROM question_sets qs
     LEFT JOIN questions q
            ON q.question_set_id = qs.id
     LEFT JOIN practice_answers pa
            ON pa.question_id = q.id
           AND pa.user_id = ? AND pa.purchased_id = ?
     WHERE qs.topic_id = ? AND qs.verified = 1 AND qs.status = 'published'
       AND qs.source = 'practice' AND qs.subset_no IS NOT NULL
     GROUP BY qs.subset_no
     ORDER BY qs.subset_no ASC"
);
mysqli_stmt_bind_param($stmt, 'iii', $user_id, $purchased_id, $topic_id);
mysqli_stmt_execute($stmt);
$r       = mysqli_stmt_get_result($stmt);
$subsets = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

// ── Topic-level stats ─────────────────────────────────────────────────────
$total_questions = array_sum(array_column($subsets, 'total_questions'));
$total_answered  = array_sum(array_column($subsets, 'answered'));
$total_correct   = array_sum(array_column($subsets, 'correct'));
$topic_accuracy  = $total_answered > 0 ? round(($total_correct / $total_answered) * 100) : 0;
$topic_progress  = $total_questions > 0 ? round(($total_answered / $total_questions) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($topic['topic_name'], ENT_QUOTES, 'UTF-8') ?> — Subsets</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">

    <!-- Breadcrumb + back -->
    <div class="qs-list-header mb-4">
        <div>
            <div class="qs-breadcrumb">
                <?= htmlspecialchars($topic['exam_body_name'], ENT_QUOTES, 'UTF-8') ?>
                &rsaquo; <?= htmlspecialchars($purchase['product_name'], ENT_QUOTES, 'UTF-8') ?>
                &rsaquo; <?= htmlspecialchars($topic['subject_name'], ENT_QUOTES, 'UTF-8') ?>
                &rsaquo; <?= htmlspecialchars($topic['topic_name'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="d-flex align-items-center gap-2 mt-1">
                <a href="practice-topics.php?purchased_id=<?= $purchased_id ?>&subject_id=<?= $topic['subject_id'] ?>"
                   class="btn-qs-sm">&larr; Back</a>
                <div class="qs-list-title">Subsets</div>
            </div>
        </div>
    </div>

    <!-- Topic stats header -->
    <div class="ps-header mb-4">
        <div class="ps-product-name"><?= htmlspecialchars($topic['topic_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="ps-stat-row mt-3">
            <div class="ps-stat-item">
                <div class="ps-stat-num"><?= $total_answered ?>/<?= $total_questions ?></div>
                <div class="ps-stat-lbl">Questions Answered</div>
            </div>
            <div class="ps-stat-item">
                <div class="ps-stat-num"><?= $topic_accuracy ?>%</div>
                <div class="ps-stat-lbl">Accuracy</div>
            </div>
            <div class="ps-stat-item">
                <div class="ps-stat-num"><?= $topic_progress ?>%</div>
                <div class="ps-stat-lbl">Progress</div>
            </div>
        </div>
        <div class="ps-progress-wrap mt-2">
            <div class="ps-progress-fill" style="width:<?= $topic_progress ?>%"></div>
        </div>
    </div>

    <!-- Subset Cards -->
    <?php if (empty($subsets)): ?>
        <div class="mp-empty">No subsets available for this topic yet.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($subsets as $row):
                $answered = (int)$row['answered'];
                $total    = (int)$row['total_questions'];
                $correct  = (int)$row['correct'];
                $progress = $total > 0 ? round(($answered / $total) * 100) : 0;
                $accuracy = $answered > 0 ? round(($correct / $answered) * 100) : 0;
                $acc_cls  = $accuracy >= 70 ? 'ps-accuracy-ok' : ($accuracy >= 40 ? 'ps-accuracy-warn' : 'ps-accuracy-bad');

                if ($answered === 0)        $btn_label = 'Start';
                elseif ($answered < $total) $btn_label = 'Continue';
                else                        $btn_label = 'Revise';
            ?>
            <div class="col-md-3 col-sm-6">
                <div class="ps-subject-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="ps-subject-name">Set <?= (int)$row['subset_no'] ?></div>
                        <button class="btn-reset-sm reset-subset-btn"
                                data-subset="<?= (int)$row['subset_no'] ?>">
                            Reset
                        </button>
                    </div>
                    <div class="mp-meta mb-1"><?= $answered ?>/<?= $total ?> questions answered</div>
                    <div class="ps-progress-wrap mb-1">
                        <div class="ps-progress-fill" style="width:<?= $progress ?>%"></div>
                    </div>
                    <div class="<?= $acc_cls ?> mb-2">Accuracy: <?= $accuracy ?>%</div>
                    <div class="mt-auto">
                        <a href="practice.php?purchased_id=<?= $purchased_id ?>&topic_id=<?= $topic_id ?>&subset_no=<?= (int)$row['subset_no'] ?>"
                           class="btn-qs-gold d-block text-center"><?= $btn_label ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<div id="csrf-holder" style="display:none;"><?= csrf_input() ?></div>

<script>
$('.reset-subset-btn').on('click', function () {
    if (!confirm('Reset progress for this subset? This cannot be undone.')) return;

    const btn       = $(this);
    const subset_no = btn.data('subset');
    btn.prop('disabled', true);

    const csrfInput = document.querySelector('#csrf-holder input[type="hidden"]');
    const payload   = {
        ajax_reset:   1,
        purchased_id: <?= $purchased_id ?>,
        topic_id:     <?= $topic_id ?>,
        subset_no:    subset_no
    };
    if (csrfInput) payload[csrfInput.name] = csrfInput.value;

    $.post('practice-sets.php', payload, function (res) {
        if (res.status === 'ok') {
            location.reload();
        } else {
            alert(res.error || 'Reset failed.');
            btn.prop('disabled', false);
        }
    }, 'json').fail(function () {
        alert('Request failed.');
        btn.prop('disabled', false);
    });
});
</script>

<?php include "inc/footer.php"; ?>
</body>
</html>
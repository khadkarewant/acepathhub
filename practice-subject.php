<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_STUDENT);

// ── AJAX RESET ────────────────────────────────────────────────────────────
if (isset($_POST['ajax_reset'])) {
    header('Content-Type: application/json');
    csrf_verify();

    $purchased_id = isset($_POST['purchased_id']) && ctype_digit($_POST['purchased_id']) ? (int)$_POST['purchased_id'] : 0;
    $level        = $_POST['level'] ?? '';
    $subject_id   = isset($_POST['subject_id']) && ctype_digit($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;

    if ($purchased_id === 0 || !in_array($level, ['exam_body', 'subject'], true)) {
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

    if ($level === 'exam_body') {
        $stmt = mysqli_prepare($conn,
            "DELETE FROM practice_answers WHERE user_id = ? AND purchased_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $purchased_id);
    } else {
        if ($subject_id === 0) {
            echo json_encode(['status' => 'error', 'error' => 'Invalid subject']);
            exit;
        }
        $stmt = mysqli_prepare($conn,
            "DELETE pa FROM practice_answers pa
             JOIN questions q      ON q.id  = pa.question_id
             JOIN question_sets qs ON qs.id = q.question_set_id
             JOIN topics t         ON t.id  = qs.topic_id
             WHERE pa.user_id = ? AND pa.purchased_id = ? AND t.subject_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $purchased_id, $subject_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'ok']);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'error', 'error' => 'Reset failed']);
    }
    exit;
}

// ── Validate purchased_id ─────────────────────────────────────────────────
$purchased_id = isset($_GET['purchased_id']) && ctype_digit($_GET['purchased_id']) ? (int)$_GET['purchased_id'] : 0;
if ($purchased_id === 0) {
    header("Location: product-mine.php");
    exit;
}

// ── Validate purchase + product info ─────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT pp.id, pp.expires_at, pp.purchased_on,
            p.id AS product_id, p.name AS product_name, p.exam_body_id,
            eb.name AS exam_body_name
     FROM purchased_products pp
     JOIN products p     ON p.id  = pp.product_id
     JOIN exam_bodies eb ON eb.id = p.exam_body_id
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

// ── Subjects with progress ────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT s.id, s.name AS subject_name,
            COUNT(DISTINCT q.id)  AS total_questions,
            COUNT(DISTINCT pa.question_id) AS answered,
            COALESCE(SUM(CASE WHEN pa.is_correct = 1 THEN 1 ELSE 0 END), 0) AS correct
     FROM subjects s
     LEFT JOIN topics t
            ON t.subject_id = s.id
     LEFT JOIN question_sets qs
            ON qs.topic_id = t.id
           AND qs.verified = 1 AND qs.status = 'published' AND qs.source = 'practice'
     LEFT JOIN questions q
            ON q.question_set_id = qs.id
     LEFT JOIN practice_answers pa
            ON pa.question_id = q.id
           AND pa.user_id = ? AND pa.purchased_id = ?
     WHERE s.exam_body_id = ?
     GROUP BY s.id, s.name
     ORDER BY s.id ASC"
);
mysqli_stmt_bind_param($stmt, 'iii', $user_id, $purchased_id, $purchase['exam_body_id']);
mysqli_stmt_execute($stmt);
$r        = mysqli_stmt_get_result($stmt);
$subjects = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

// ── Overall stats ─────────────────────────────────────────────────────────
$total_questions  = array_sum(array_column($subjects, 'total_questions'));
$total_answered   = array_sum(array_column($subjects, 'answered'));
$total_correct    = array_sum(array_column($subjects, 'correct'));
$overall_accuracy = $total_answered > 0 ? round(($total_correct / $total_answered) * 100) : 0;
$overall_progress = $total_questions > 0  ? round(($total_answered / $total_questions) * 100) : 0;

// ── Expiry ────────────────────────────────────────────────────────────────
$today    = new DateTime('today');
$expiry   = new DateTime($purchase['expires_at']);
$days     = (int)$today->diff($expiry)->days;
$exp_cls  = $days <= 7 ? 'mp-expires-warn' : 'mp-expires-ok';

// ── Overall rank for this product ─────────────────────────────────────────
$product_id = (int)$purchase['product_id'];

$rank_stmt = mysqli_prepare($conn,
    "SELECT pa.user_id, SUM(is_correct = 1) AS correct,
            ROUND((SUM(is_correct = 1) / COUNT(pa.id)) * 100, 2) AS accuracy
     FROM practice_answers pa
     JOIN purchased_products pp ON pp.user_id = pa.user_id AND pp.product_id = ?
     WHERE pp.status = 'active'
     GROUP BY user_id
     ORDER BY correct DESC, accuracy DESC"
);
mysqli_stmt_bind_param($rank_stmt, 'i', $product_id);
mysqli_stmt_execute($rank_stmt);
$rank_res  = mysqli_stmt_get_result($rank_stmt);
$rank_rows = mysqli_fetch_all($rank_res, MYSQLI_ASSOC);
mysqli_free_result($rank_res);
mysqli_stmt_close($rank_stmt);

$my_rank       = 0;
$total_rankers = count($rank_rows);
foreach ($rank_rows as $idx => $rr) {
    if ((int)$rr['user_id'] === $user_id) {
        $my_rank = $idx + 1;
        break;
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($purchase['product_name'], ENT_QUOTES, 'UTF-8') ?> — Practice</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">

    <!-- Product Header -->
    <div class="ps-header mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <div class="ps-product-name"><?= htmlspecialchars($purchase['product_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="ps-exam-body"><?= htmlspecialchars($purchase['exam_body_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="<?= $exp_cls ?> mt-1" style="font-size:0.82rem;">
                    Expires: <?= date('d M Y', strtotime($purchase['expires_at'])) ?>
                    <?= $days <= 7 ? " &bull; {$days}d left" : '' ?>
                </div>
            </div>
            <button class="btn-reset-all"
                    data-purchased="<?= $purchased_id ?>"
                    data-level="exam_body">
                Reset All Progress
            </button>
        </div>

        <!-- Overall stats -->
        <div class="ps-stat-row mt-3">
            <div class="ps-stat-item">
                <div class="ps-stat-num"><?= $total_answered ?>/<?= $total_questions ?></div>
                <div class="ps-stat-lbl">Questions Answered</div>
            </div>
            <div class="ps-stat-item">
                <div class="ps-stat-num"><?= $overall_accuracy ?>%</div>
                <div class="ps-stat-lbl">Accuracy</div>
            </div>
            <div class="ps-stat-item">
                <div class="ps-stat-num"><?= $overall_progress ?>%</div>
                <div class="ps-stat-lbl">Progress</div>
            </div>

            <?php if ($my_rank > 0): ?>
            <div class="ps-stat-item">
                <div class="ps-stat-num">
                    <?php
                    if ($my_rank === 1) echo '🏆';
                    elseif ($my_rank === 2) echo '🥈';
                    elseif ($my_rank === 3) echo '🥉';
                    ?>
                    <span class="lb-rank-badge lb-rank-<?= $my_rank <= 3 ? $my_rank : ($my_rank <= 10 ? 'top10' : 'default') ?>">
                        #<?= $my_rank ?>
                    </span>
                </div>
                <div class="ps-stat-lbl">Your Rank</div>
            </div>
            <?php endif; ?>

        </div>
        <div class="ps-progress-wrap mt-2">
            <div class="ps-progress-fill" style="width:<?= $overall_progress ?>%"></div>
        </div>
    </div>

    <!-- Subject Cards -->
    <?php if (empty($subjects)): ?>
        <div class="mp-empty">No subjects found for this product.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($subjects as $row):
                $answered  = (int)$row['answered'];
                $total     = (int)$row['total_questions'];
                $correct   = (int)$row['correct'];
                $progress  = $total > 0 ? round(($answered / $total) * 100) : 0;
                $accuracy  = $answered > 0 ? round(($correct / $answered) * 100) : 0;
                $acc_cls   = $accuracy >= 70 ? 'ps-accuracy-ok' : ($accuracy >= 40 ? 'ps-accuracy-warn' : 'ps-accuracy-bad');
            ?>
            <div class="col-md-4 col-sm-6">
                <div class="ps-subject-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="ps-subject-name"><?= htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <button class="btn-reset-sm"
                                data-purchased="<?= $purchased_id ?>"
                                data-level="subject"
                                data-subject="<?= (int)$row['id'] ?>">
                            Reset
                        </button>
                    </div>
                    <div class="mp-meta mb-1"><?= $answered ?>/<?= $total ?> questions answered</div>
                    <div class="ps-progress-wrap mb-1">
                        <div class="ps-progress-fill" style="width:<?= $progress ?>%"></div>
                    </div>
                    <div class="<?= $acc_cls ?> mb-2">Accuracy: <?= $accuracy ?>%</div>
                    <div class="mt-auto">
                        <a href="practice-topics.php?purchased_id=<?= $purchased_id ?>&subject_id=<?= (int)$row['id'] ?>"
                        class="btn-qs-gold d-block text-center mb-2">
                            <?= $answered > 0 ? 'Continue' : 'Start' ?>
                        </a>
                        <a href="leaderboard.php?purchased_id=<?= $purchased_id ?>&subject_id=<?= (int)$row['id'] ?>"
                        class="btn-qs-sm d-block text-center">
                            🏆 Leaderboard
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<div id="csrf-holder" style="display:none;"><?= csrf_input() ?></div>

<script>
function resetProgress(btn, purchased_id, level, subject_id) {
    const msg = level === 'exam_body'
        ? 'Reset ALL progress for this product? This cannot be undone.'
        : 'Reset progress for this subject? This cannot be undone.';
    if (!confirm(msg)) return;

    $(btn).prop('disabled', true);

    const csrfInput = document.querySelector('#csrf-holder input[type="hidden"]');
    const payload   = { ajax_reset: 1, purchased_id: purchased_id, level: level };
    if (subject_id) payload.subject_id = subject_id;
    if (csrfInput)  payload[csrfInput.name] = csrfInput.value;

    $.post('practice-subject.php', payload, function (res) {
        if (res.status === 'ok') {
            location.reload();
        } else {
            alert(res.error || 'Reset failed.');
            $(btn).prop('disabled', false);
        }
    }, 'json').fail(function () {
        alert('Request failed.');
        $(btn).prop('disabled', false);
    });
}

$('.btn-reset-all').on('click', function () {
    resetProgress(this, <?= $purchased_id ?>, 'exam_body', null);
});

$('.btn-reset-sm').on('click', function () {
    const subject_id = $(this).data('subject');
    resetProgress(this, <?= $purchased_id ?>, 'subject', subject_id);
});
</script>

<?php include "inc/footer.php"; ?>
</body>
</html>
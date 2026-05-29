<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_ADMIN);

$topic_id = isset($_GET['topic_id']) && ctype_digit($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
if ($topic_id === 0) {
    header("Location: exam-body-list.php");
    exit;
}

// ── Validate topic + breadcrumb ───────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT t.id, t.name AS topic_name, t.subject_id,
            s.name AS subject_name,
            eb.name AS exam_body_name
     FROM topics t
     JOIN subjects s    ON s.id  = t.subject_id
     JOIN exam_bodies eb ON eb.id = s.exam_body_id
     WHERE t.id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'i', $topic_id);
mysqli_stmt_execute($stmt);
$r     = mysqli_stmt_get_result($stmt);
$topic = mysqli_fetch_assoc($r);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if (!$topic) {
    header("Location: exam-body-list.php");
    exit;
}

// ── Total published sets + questions ──────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT COUNT(qs.id) AS total_sets,
            COALESCE(SUM(qc.q_count), 0) AS total_questions
     FROM question_sets qs
     LEFT JOIN (
         SELECT question_set_id, COUNT(*) AS q_count
         FROM questions
         GROUP BY question_set_id
     ) qc ON qc.question_set_id = qs.id
     WHERE qs.topic_id = ? AND qs.verified = 1 AND qs.status = 'published'"
);
mysqli_stmt_bind_param($stmt, 'i', $topic_id);
mysqli_stmt_execute($stmt);
$r      = mysqli_stmt_get_result($stmt);
$totals = mysqli_fetch_assoc($r);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

// ── Current subset distribution ───────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT qs.subset_no,
            COUNT(qs.id) AS set_count,
            COALESCE(SUM(qc.q_count), 0) AS question_count
     FROM question_sets qs
     LEFT JOIN (
         SELECT question_set_id, COUNT(*) AS q_count
         FROM questions
         GROUP BY question_set_id
     ) qc ON qc.question_set_id = qs.id
     WHERE qs.topic_id = ? AND qs.verified = 1 AND qs.status = 'published'
     GROUP BY qs.subset_no
     ORDER BY qs.subset_no ASC"
);
mysqli_stmt_bind_param($stmt, 'i', $topic_id);
mysqli_stmt_execute($stmt);
$r       = mysqli_stmt_get_result($stmt);
$subsets = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

include "inc/header.php";
?>

<div class="container py-4">

    <div class="qs-list-header mb-4">
        <div>
            <div class="qs-breadcrumb">
                <?= htmlspecialchars($topic['exam_body_name'], ENT_QUOTES, 'UTF-8') ?>
                &rsaquo; <?= htmlspecialchars($topic['subject_name'], ENT_QUOTES, 'UTF-8') ?>
                &rsaquo; <?= htmlspecialchars($topic['topic_name'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="d-flex align-items-center gap-2 mt-1">
                <a href="topic-list.php?subject_id=<?= $topic['subject_id'] ?>" class="btn-qs-sm">&larr; Back</a>
                <div class="qs-list-title">Manage Practice Subsets</div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="psm-stat-card">
                <div class="psm-stat-value"><?= (int)$totals['total_sets'] ?></div>
                <div class="psm-stat-label">Question Sets</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="psm-stat-card">
                <div class="psm-stat-value"><?= (int)$totals['total_questions'] ?></div>
                <div class="psm-stat-label">Total Questions</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="psm-stat-card">
                <div class="psm-stat-value"><?= count($subsets) ?></div>
                <div class="psm-stat-label">Current Subsets</div>
            </div>
        </div>
    </div>

    <!-- Current distribution -->
    <?php if (!empty($subsets)): ?>
    <div class="mb-4">
        <div class="psm-section-title">Current Distribution</div>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Subset</th>
                    <th>Question Sets</th>
                    <th>Questions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subsets as $sub): ?>
                <tr>
                    <td><?= $sub['subset_no'] !== null ? 'Subset ' . (int)$sub['subset_no'] : 'Unassigned' ?></td>
                    <td><?= (int)$sub['set_count'] ?></td>
                    <td><?= (int)$sub['question_count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Redistribute -->
    <?php if ((int)$totals['total_sets'] > 0): ?>
    <div class="psm-form-card">
        <div class="psm-section-title mb-2">Redistribute Subsets</div>
        <p class="mp-meta mb-3">
            Enter target questions per subset. Passage sets will not be split — slight overflow is accepted.
            Remainder goes into the last subset.
        </p>
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="form-label mp-meta mb-1">Questions per subset</label>
                <input type="number" id="questions_per_subset" class="form-control psm-input"
                       min="1" max="<?= (int)$totals['total_questions'] ?>"
                       placeholder="e.g. 50">
            </div>
            <button id="split-btn" class="btn-qs-gold">Split into Subsets</button>
        </div>
        <div id="split-result" class="mt-3"></div>
    </div>
    <?php else: ?>
        <div class="mp-empty">No published question sets found for this topic.</div>
    <?php endif; ?>

</div>

<div id="csrf-holder" style="display:none;"><?= csrf_input() ?></div>

<script>
$('#split-btn').on('click', function () {
    const val = parseInt($('#questions_per_subset').val(), 10);
    if (!val || val < 1) {
        alert('Enter a valid number of questions per subset.');
        return;
    }
    if (!confirm('This will redistribute all question sets for this topic. Continue?')) return;

    const btn = $(this);
    btn.prop('disabled', true).text('Processing...');

    const csrfInput = document.querySelector('#csrf-holder input[type="hidden"]');
    const payload   = { topic_id: <?= $topic_id ?>, questions_per_subset: val };
    if (csrfInput) payload[csrfInput.name] = csrfInput.value;

    $.post('practice-subsets-split-ajax.php', payload, function (res) {
        if (res.status === 'ok') {
            $('#split-result').html(
                '<div class="alert alert-success">Done. ' + res.subsets + ' subset(s) created across ' + res.sets + ' question sets.</div>'
            );
            setTimeout(() => location.reload(), 1500);
        } else {
            $('#split-result').html('<div class="alert alert-danger">' + (res.error || 'Error occurred.') + '</div>');
            btn.prop('disabled', false).text('Split into Subsets');
        }
    }, 'json').fail(function () {
        $('#split-result').html('<div class="alert alert-danger">Request failed.</div>');
        btn.prop('disabled', false).text('Split into Subsets');
    });
});
</script>

<?php include "inc/footer.php"; ?>
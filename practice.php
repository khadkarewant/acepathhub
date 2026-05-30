<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_STUDENT);

// ── AJAX SAVE ANSWER ──────────────────────────────────────────────────────
if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    csrf_verify();

    $purchased_id    = isset($_POST['purchased_id'])    && ctype_digit($_POST['purchased_id'])    ? (int)$_POST['purchased_id']    : 0;
    $question_set_id = isset($_POST['question_set_id']) && ctype_digit($_POST['question_set_id']) ? (int)$_POST['question_set_id'] : 0;
    $question_id     = isset($_POST['question_id'])     && ctype_digit($_POST['question_id'])     ? (int)$_POST['question_id']     : 0;
    $selected        = strtoupper(trim($_POST['selected'] ?? ''));

    if ($purchased_id === 0 || $question_set_id === 0 || $question_id === 0
        || !in_array($selected, ['A','B','C','D'], true)) {
        echo json_encode(['error' => 'Invalid input']);
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
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Check not already answered
    $stmt = mysqli_prepare($conn,
        "SELECT id FROM practice_answers
         WHERE user_id = ? AND purchased_id = ? AND question_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $purchased_id, $question_id);
    mysqli_stmt_execute($stmt);
    $r       = mysqli_stmt_get_result($stmt);
    $already = mysqli_fetch_assoc($r);
    mysqli_free_result($r);
    mysqli_stmt_close($stmt);

    if ($already) {
        echo json_encode(['error' => 'Already answered']);
        exit;
    }

    // Fetch correct answer + explanation
    $stmt = mysqli_prepare($conn,
        "SELECT answer, explanation FROM questions
         WHERE id = ? AND question_set_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $question_id, $question_set_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $q = mysqli_fetch_assoc($r);
    mysqli_free_result($r);
    mysqli_stmt_close($stmt);

    if (!$q) {
        echo json_encode(['error' => 'Question not found']);
        exit;
    }

    $correct    = strtoupper(trim($q['answer']));
    $is_correct = ($selected === $correct) ? 1 : 0;

    $stmt = mysqli_prepare($conn,
        "INSERT INTO practice_answers
             (user_id, purchased_id, question_set_id, question_id, selected_option, is_correct)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'iiiisi', $user_id, $purchased_id, $question_set_id, $question_id, $selected, $is_correct);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode([
            'correct'     => $correct,
            'is_correct'  => $is_correct,
            'explanation' => $q['explanation'] ?? ''
        ]);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['error' => 'Save failed']);
    }
    exit;
}

// ── Validate GET params ───────────────────────────────────────────────────
$purchased_id = isset($_GET['purchased_id']) && ctype_digit($_GET['purchased_id']) ? (int)$_GET['purchased_id'] : 0;
$topic_id     = isset($_GET['topic_id'])     && ctype_digit($_GET['topic_id'])     ? (int)$_GET['topic_id']     : 0;
$subset_no    = isset($_GET['subset_no'])    && ctype_digit($_GET['subset_no'])    ? (int)$_GET['subset_no']    : 0;

if ($purchased_id === 0 || $topic_id === 0 || $subset_no === 0) {
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

// ── Validate topic ────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT t.id, t.name AS topic_name, t.subject_id,
            s.name AS subject_name
     FROM topics t
     JOIN subjects s ON s.id = t.subject_id
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

// ── Fetch all questions for this subset ───────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT qs.id AS question_set_id, qs.passage_text, qs.image_path,
            q.id  AS question_id, q.question,
            q.option_a, q.option_b, q.option_c, q.option_d,
            q.answer, q.explanation,
            pa.selected_option, pa.is_correct
     FROM question_sets qs
     JOIN questions q        ON q.question_set_id = qs.id
     LEFT JOIN practice_answers pa
            ON pa.question_id = q.id
           AND pa.user_id = ? AND pa.purchased_id = ?
     WHERE qs.topic_id = ? AND qs.subset_no = ?
       AND qs.verified = 1 AND qs.status = 'published' AND qs.source = 'practice'
     ORDER BY qs.id ASC, q.id ASC"
);
mysqli_stmt_bind_param($stmt, 'iiii', $user_id, $purchased_id, $topic_id, $subset_no);
mysqli_stmt_execute($stmt);
$r   = mysqli_stmt_get_result($stmt);
$raw = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if (empty($raw)) {
    header("Location: practice-sets.php?purchased_id={$purchased_id}&topic_id={$topic_id}");
    exit;
}

// ── Group by question_set ─────────────────────────────────────────────────
$sets = [];
foreach ($raw as $row) {
    $qs_id = (int)$row['question_set_id'];
    if (!isset($sets[$qs_id])) {
        $sets[$qs_id] = [
            'id'           => $qs_id,
            'passage_text' => $row['passage_text'],
            'image_path'   => $row['image_path'],
            'questions'    => []
        ];
    }
    $answered = $row['selected_option'] !== null;
    $sets[$qs_id]['questions'][] = [
        'id'          => (int)$row['question_id'],
        'question'    => $row['question'],
        'option_a'    => $row['option_a'],
        'option_b'    => $row['option_b'],
        'option_c'    => $row['option_c'],
        'option_d'    => $row['option_d'],
        'selected'    => $row['selected_option'],
        'answer'      => $answered ? strtoupper($row['answer'])  : null,
        'explanation' => $answered ? ($row['explanation'] ?? '') : null,
        'is_correct'  => $answered ? (int)$row['is_correct']     : null
    ];
}
$sets       = array_values($sets);
$total_sets = count($sets);

// ── Resume: first set with unanswered questions ───────────────────────────
$resume_index = $total_sets - 1;
foreach ($sets as $idx => $set) {
    foreach ($set['questions'] as $q) {
        if ($q['selected'] === null) {
            $resume_index = $idx;
            break 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($topic['topic_name'], ENT_QUOTES, 'UTF-8') ?> — Set <?= $subset_no ?></title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">
    <div class="pq-wrapper">

        <!-- Breadcrumb + back -->
        <div class="qs-breadcrumb mb-2">
            <?= htmlspecialchars($purchase['product_name'], ENT_QUOTES, 'UTF-8') ?>
            &rsaquo; <?= htmlspecialchars($topic['subject_name'], ENT_QUOTES, 'UTF-8') ?>
            &rsaquo; <?= htmlspecialchars($topic['topic_name'], ENT_QUOTES, 'UTF-8') ?>
            &rsaquo; Set <?= $subset_no ?>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="practice-sets.php?purchased_id=<?= $purchased_id ?>&topic_id=<?= $topic_id ?>"
               class="btn-qs-sm">&larr; Back</a>
            <div id="pq-set-label" class="pq-set-label">Set <span id="pq-current">1</span> of <?= $total_sets ?></div>
            <button id="report-btn" class="btn-reset-sm">Report</button>
        </div>

        <!-- Question container -->
        <div id="question-container" class="pq-container">
            <!-- Rendered by JS -->
        </div>

        <!-- Navigation -->
        <div class="pq-nav mt-3">
            <button id="prev-btn" class="btn-qs-sm" disabled>&larr; Prev</button>
            <span id="pq-answered-count" class="mp-meta"></span>
            <button id="next-btn" class="btn-qs-sm">Next &rarr;</button>
        </div>

        <!-- Palette -->
        <div class="pq-palette mt-3" id="q-palette">
            <?php foreach ($sets as $idx => $set): ?>
                <div class="pq-badge pq-badge-unanswered" data-index="<?= $idx ?>"><?= $idx + 1 ?></div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#13131a;border:1px solid #2a2a3a;">
            <div class="modal-header" style="border-color:#2a2a3a;">
                <h5 class="modal-title" style="color:var(--accent);">Report Issue</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="reportText" class="form-control"
                          style="background:#0a0a0f;color:#ccc;border-color:#2a2a3a;"
                          rows="4" placeholder="Describe the issue with this question set"></textarea>
            </div>
            <div class="modal-footer" style="border-color:#2a2a3a;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitReport" class="btn-qs-gold">Submit</button>
            </div>
        </div>
    </div>
</div>

<div id="csrf-holder" style="display:none;"><?= csrf_input() ?></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
<script>
const sets         = <?= json_encode($sets) ?>;
const purchasedId  = <?= $purchased_id ?>;
const topicId      = <?= $topic_id ?>;
const totalSets    = <?= $total_sets ?>;
let   currentIndex = <?= $resume_index ?>;
let   isAnimating  = false;

function getCsrf() {
    const el = document.querySelector('#csrf-holder input[type="hidden"]');
    return el ? { name: el.name, value: el.value } : null;
}

function getSetStatus(set) {
    let answered = 0, correct = 0;
    set.questions.forEach(q => {
        if (q.selected) {
            answered++;
            if (q.is_correct === 1) correct++;
        }
    });
    const total = set.questions.length;
    if (answered === 0)             return 'unanswered';
    if (answered < total)           return 'partial';
    if (correct === total)          return 'correct';
    return 'wrong';
}

function updatePalette() {
    $('#q-palette .pq-badge').each(function(i) {
        const status = getSetStatus(sets[i]);
        $(this).removeClass('pq-badge-current pq-badge-correct pq-badge-wrong pq-badge-partial pq-badge-unanswered');
        if (i === currentIndex) {
            $(this).addClass('pq-badge-current');
        } else {
            const cls = {
                correct:    'pq-badge-correct',
                wrong:      'pq-badge-wrong',
                partial:    'pq-badge-partial',
                unanswered: 'pq-badge-unanswered'
            }[status] || 'pq-badge-unanswered';
            $(this).addClass(cls);
        }
    });
}

function updateAnsweredCount() {
    let total = 0, answered = 0;
    sets.forEach(set => {
        set.questions.forEach(q => {
            total++;
            if (q.selected) answered++;
        });
    });
    $('#pq-answered-count').text(answered + '/' + total + ' answered');
}

function renderSet(index) {
    const set = sets[index];
    let html  = '';

    // Passage
    if (set.passage_text && set.passage_text.trim() !== '') {
        html += `<div class="pq-passage">${set.passage_text}</div>`;
    }

    // Image
    if (set.image_path && set.image_path.trim() !== '') {
        html += `<img src="${set.image_path}" class="pq-image" alt="Question image">`;
    }

    // Questions
    set.questions.forEach((q, qi) => {
        const multi = set.questions.length > 1;
        html += `<div class="pq-question-block" data-qid="${q.id}" data-qsid="${set.id}">`;

        if (multi) {
            html += `<div class="pq-q-num">Q${qi + 1}</div>`;
        }

        html += `<div class="pq-q-text">${q.question}</div>`;
        html += `<div class="pq-options">`;

        ['A','B','C','D'].forEach(key => {
            const val       = q['option_' + key.toLowerCase()];
            const answered  = q.selected !== null;
            const isCorrect = q.answer === key;
            const isSelected = q.selected === key;

            let cls = 'pq-option';
            if (answered) {
                cls += ' pq-answered';
                if (isCorrect)                       cls += ' pq-correct';
                else if (isSelected && !isCorrect)   cls += ' pq-wrong';
            }

            const disabled = answered ? 'disabled' : '';
            html += `<label class="${cls}">
                        <input type="radio" class="pq-answer" name="ans_${q.id}"
                               value="${key}" data-qid="${q.id}" data-qsid="${set.id}"
                               ${isSelected ? 'checked' : ''} ${disabled}>
                        <span class="pq-opt-letter">${key}.</span> ${val}
                     </label>`;
        });

        html += `</div>`;

        // Explanation
        if (q.selected && q.explanation && q.explanation.trim() !== '') {
            html += `<div class="pq-explanation"><strong>Explanation:</strong> ${q.explanation}</div>`;
        } else if (q.selected) {
            html += `<div class="pq-explanation pq-no-explanation"></div>`;
        }

        html += `</div>`;
    });

    return html;
}

function loadSet(index) {
    currentIndex = index;
    $('#pq-current').text(index + 1);
    $('#question-container').html(renderSet(index));
    $('#prev-btn').prop('disabled', index === 0);
    $('#next-btn').prop('disabled', index === totalSets - 1);
    updatePalette();
    updateAnsweredCount();
    window.scrollTo(0, 0);
}

function animateSwipe(nextIndex, direction) {
    if (nextIndex < 0 || nextIndex >= totalSets || isAnimating) return;
    isAnimating = true;

    const $c = $('#question-container');
    $c.css({ transition: 'transform 0.25s ease, opacity 0.25s ease',
             transform: direction === 'left' ? 'translateX(-100%)' : 'translateX(100%)',
             opacity: 0 });

    $c.one('transitionend', function () {
        loadSet(nextIndex);
        $c.css({ transition: 'none', transform: direction === 'left' ? 'translateX(100%)' : 'translateX(-100%)', opacity: 1 });
        $c[0].offsetHeight; // reflow
        $c.css({ transition: 'transform 0.25s ease, opacity 0.25s ease', transform: 'translateX(0)', opacity: 1 });
        $c.one('transitionend', () => { isAnimating = false; });
    });
}

// Answer click
$(document).on('change', '.pq-answer', function () {
    const qid   = parseInt($(this).data('qid'));
    const qsid  = parseInt($(this).data('qsid'));
    const selected = $(this).val();

    // Disable all options for this question immediately
    $(this).closest('.pq-question-block').find('.pq-answer').prop('disabled', true);

    const csrf  = getCsrf();
    const payload = { ajax: 1, purchased_id: purchasedId, question_set_id: qsid, question_id: qid, selected: selected };
    if (csrf) payload[csrf.name] = csrf.value;

    $.post('practice.php', payload, function (res) {
        if (res.error) {
            alert(res.error);
            return;
        }

        // Update JS set data
        const set = sets.find(s => s.id === qsid);
        if (set) {
            const q = set.questions.find(q => q.id === qid);
            if (q) {
                q.selected    = selected;
                q.answer      = res.correct;
                q.explanation = res.explanation;
                q.is_correct  = res.is_correct;
            }
        }

        // Update UI
        const $block = $(`.pq-question-block[data-qid="${qid}"]`);
        $block.find('.pq-option').each(function () {
            const val = $(this).find('.pq-answer').val();
            $(this).addClass('pq-answered');
            if (val === res.correct)                          $(this).addClass('pq-correct');
            else if (val === selected && val !== res.correct) $(this).addClass('pq-wrong');
        });

        if (res.explanation && res.explanation.trim() !== '') {
            $block.find('.pq-explanation')
                  .removeClass('pq-no-explanation')
                  .html('<strong>Explanation:</strong> ' + res.explanation);
        }

        updatePalette();
        updateAnsweredCount();
    }, 'json');
});

// Palette click
$(document).on('click', '.pq-badge', function () {
    const idx = parseInt($(this).data('index'));
    if (idx === currentIndex) return;
    animateSwipe(idx, idx > currentIndex ? 'left' : 'right');
});

// Prev / Next
$('#prev-btn').on('click', () => animateSwipe(currentIndex - 1, 'right'));
$('#next-btn').on('click', () => animateSwipe(currentIndex + 1, 'left'));

// Swipe
const hammer = new Hammer(document.getElementById('question-container'));
hammer.on('swipeleft',  () => animateSwipe(currentIndex + 1, 'left'));
hammer.on('swiperight', () => animateSwipe(currentIndex - 1, 'right'));

// Report
$('#report-btn').on('click', function () {
    $('#reportText').val('');
    new bootstrap.Modal(document.getElementById('reportModal')).show();
});

$('#submitReport').on('click', function () {
    const reason = $('#reportText').val().trim();
    if (!reason) { alert('Please describe the issue.'); return; }

    const csrf    = getCsrf();
    const payload = { question_set_id: sets[currentIndex].id, reason: reason };
    if (csrf) payload[csrf.name] = csrf.value;

    $.post('report-mcq.php', payload, function (res) {
        if (res.status === 'ok') {
            alert('Reported. Thank you.');
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
        } else {
            alert('Error: ' + (res.message || 'Failed'));
        }
    }, 'json');
});

// Init
loadSet(currentIndex);
</script>

<?php include "inc/footer.php"; ?>
</body>
</html>
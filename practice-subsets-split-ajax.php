<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

header('Content-Type: application/json');

if (!has_role(ROLE_ADMIN)) {
    echo json_encode(['status' => 'error', 'error' => 'Access denied']);
    exit;
}

csrf_verify();

$topic_id             = isset($_POST['topic_id'])             && ctype_digit($_POST['topic_id'])             ? (int)$_POST['topic_id']             : 0;
$questions_per_subset = isset($_POST['questions_per_subset']) && ctype_digit($_POST['questions_per_subset']) ? (int)$_POST['questions_per_subset'] : 0;

if ($topic_id === 0 || $questions_per_subset < 1) {
    echo json_encode(['status' => 'error', 'error' => 'Invalid input']);
    exit;
}

// Validate topic
$stmt = mysqli_prepare($conn, "SELECT id FROM topics WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $topic_id);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
$exists = mysqli_fetch_assoc($r);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if (!$exists) {
    echo json_encode(['status' => 'error', 'error' => 'Topic not found']);
    exit;
}

// Fetch all published+verified question_sets with question counts
$stmt = mysqli_prepare($conn,
    "SELECT qs.id, COALESCE(COUNT(q.id), 0) AS q_count
     FROM question_sets qs
     LEFT JOIN questions q ON q.question_set_id = qs.id
     WHERE qs.topic_id = ? AND qs.verified = 1 AND qs.status = 'published'
     GROUP BY qs.id
     ORDER BY qs.id ASC"
);
mysqli_stmt_bind_param($stmt, 'i', $topic_id);
mysqli_stmt_execute($stmt);
$r    = mysqli_stmt_get_result($stmt);
$sets = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if (empty($sets)) {
    echo json_encode(['status' => 'error', 'error' => 'No published question sets found']);
    exit;
}

// ── Redistribution algorithm ──────────────────────────────────────────────
// Unit: question_sets row (atomic — passages stay intact)
// Rule: fill subset to target, overflow accepted, remainder to last subset
$subset_no     = 1;
$current_count = 0;
$assignments   = [];

foreach ($sets as $set) {
    if ($current_count >= $questions_per_subset && $current_count > 0) {
        $subset_no++;
        $current_count = 0;
    }
    $assignments[$set['id']] = $subset_no;
    $current_count += (int)$set['q_count'];
}

// ── UPDATE in transaction ─────────────────────────────────────────────────
mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, "UPDATE question_sets SET subset_no = ? WHERE id = ?");
    foreach ($assignments as $qs_id => $sno) {
        mysqli_stmt_bind_param($stmt, 'ii', $sno, $qs_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_error($conn));
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_commit($conn);

    echo json_encode([
        'status'  => 'ok',
        'subsets' => $subset_no,
        'sets'    => count($assignments)
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}
exit;
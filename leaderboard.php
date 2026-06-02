<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_STUDENT);

// ── Parameters ────────────────────────────────────────────────────────────
$purchased_id = isset($_GET['purchased_id']) && ctype_digit($_GET['purchased_id']) ? (int)$_GET['purchased_id'] : 0;
$subject_id   = isset($_GET['subject_id'])   && ctype_digit($_GET['subject_id'])   ? (int)$_GET['subject_id']   : 0;
$topic_id     = isset($_GET['topic_id'])     && ctype_digit($_GET['topic_id'])     ? (int)$_GET['topic_id']     : 0;
$time_filter  = in_array($_GET['time'] ?? '', ['month', 'week']) ? $_GET['time'] : 'all';

if ($purchased_id === 0 || $subject_id === 0) {
    header("Location: product-mine.php");
    exit;
}

// ── Validate purchase + get product_id ───────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT pp.id, pp.product_id, p.name AS product_name, p.exam_body_id,
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

$product_id = (int)$purchase['product_id'];

// ── Validate subject ──────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT id, name FROM subjects WHERE id = ? AND exam_body_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ii', $subject_id, $purchase['exam_body_id']);
mysqli_stmt_execute($stmt);
$r       = mysqli_stmt_get_result($stmt);
$subject = mysqli_fetch_assoc($r);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

if (!$subject) {
    header("Location: product-mine.php");
    exit;
}

// ── Validate topic (if provided) ─────────────────────────────────────────
$topic = null;
if ($topic_id > 0) {
    $stmt = mysqli_prepare($conn,
        "SELECT id, name FROM topics WHERE id = ? AND subject_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $topic_id, $subject_id);
    mysqli_stmt_execute($stmt);
    $r     = mysqli_stmt_get_result($stmt);
    $topic = mysqli_fetch_assoc($r);
    mysqli_free_result($r);
    mysqli_stmt_close($stmt);

    if (!$topic) {
        header("Location: leaderboard.php?purchased_id={$purchased_id}&subject_id={$subject_id}");
        exit;
    }
}

// ── Topics list for topic-wise tab ────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT id, name FROM topics WHERE subject_id = ? ORDER BY id ASC"
);
mysqli_stmt_bind_param($stmt, 'i', $subject_id);
mysqli_stmt_execute($stmt);
$r      = mysqli_stmt_get_result($stmt);
$topics = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

// ── Time filter clause ────────────────────────────────────────────────────
$time_clause = '';
if ($time_filter === 'week') {
    $time_clause = "AND pa.answered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($time_filter === 'month') {
    $time_clause = "AND pa.answered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// ── Leaderboard query ─────────────────────────────────────────────────────
if ($topic_id > 0) {
    // Topic-wise
    $sql = "SELECT
                u.user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                COUNT(pa.id)                            AS attempted,
                SUM(pa.is_correct = 1)                  AS correct,
                SUM(pa.is_correct = 0)                  AS wrong,
                ROUND((SUM(pa.is_correct = 1) / COUNT(pa.id)) * 100, 2) AS accuracy
            FROM practice_answers pa
            JOIN users u           ON u.user_id        = pa.user_id
            JOIN questions q       ON q.id             = pa.question_id
            JOIN question_sets qs  ON qs.id            = q.question_set_id
            JOIN purchased_products pp ON pp.user_id   = pa.user_id
                                      AND pp.product_id = ?
                                      AND pp.status     = 'active'
            WHERE qs.topic_id = ?
              AND qs.verified  = 1
              AND qs.status    = 'published'
              AND qs.source    = 'practice'
              {$time_clause}
            GROUP BY pa.user_id
            HAVING attempted > 0
            ORDER BY correct DESC, accuracy DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $product_id, $topic_id);
} else {
    // Subject-wise
    $sql = "SELECT
                u.user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                COUNT(pa.id)                            AS attempted,
                SUM(pa.is_correct = 1)                  AS correct,
                SUM(pa.is_correct = 0)                  AS wrong,
                ROUND((SUM(pa.is_correct = 1) / COUNT(pa.id)) * 100, 2) AS accuracy
            FROM practice_answers pa
            JOIN users u           ON u.user_id        = pa.user_id
            JOIN questions q       ON q.id             = pa.question_id
            JOIN question_sets qs  ON qs.id            = q.question_set_id
            JOIN topics t          ON t.id             = qs.topic_id
            JOIN purchased_products pp ON pp.user_id   = pa.user_id
                                      AND pp.product_id = ?
                                      AND pp.status     = 'active'
            WHERE t.subject_id   = ?
              AND qs.verified     = 1
              AND qs.status       = 'published'
              AND qs.source       = 'practice'
              {$time_clause}
            GROUP BY pa.user_id
            HAVING attempted > 0
            ORDER BY correct DESC, accuracy DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $product_id, $subject_id);
}

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$all_rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// ── Assign ranks + find current user ─────────────────────────────────────
$leaderboard_all = [];
$user_rank_info  = null;
$rank_counter    = 1;
foreach ($all_rows as $row) {
    $row['rank'] = $rank_counter;
    if ((int)$row['user_id'] === $user_id) $user_rank_info = $row;
    $leaderboard_all[] = $row;
    $rank_counter++;
}

$total_users = count($leaderboard_all);

// ── Pagination ────────────────────────────────────────────────────────────
$per_page    = 50;
$page        = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset      = ($page - 1) * $per_page;
$total_pages = (int)ceil($total_users / $per_page);
$leaderboard = array_slice($leaderboard_all, $offset, $per_page);
$my_rank_page = $user_rank_info ? (int)ceil($user_rank_info['rank'] / $per_page) : 1;

// ── Active tab ────────────────────────────────────────────────────────────
$active_tab = $topic_id > 0 ? 'topic' : 'subject';

// ── Build base URL for filters ────────────────────────────────────────────
$base_url = "leaderboard.php?purchased_id={$purchased_id}&subject_id={$subject_id}";
if ($topic_id > 0) $base_url .= "&topic_id={$topic_id}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">

    <div class="qs-list-header">
        <div>
            <div class="qs-breadcrumb">
                <?= htmlspecialchars($purchase['exam_body_name'], ENT_QUOTES, 'UTF-8') ?>
                &rsaquo; <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($topic): ?>
                    &rsaquo; <?= htmlspecialchars($topic['name'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <div class="qs-list-title">🏆 Leaderboard</div>
        </div>
        <a href="practice-subject.php?purchased_id=<?= $purchased_id ?>" class="btn-qs-sm">← Back</a>
    </div>

    <!-- Tabs -->
    <div class="qs-tabs mb-3">
        <a href="leaderboard.php?purchased_id=<?= $purchased_id ?>&subject_id=<?= $subject_id ?>&time=<?= $time_filter ?>"
           class="qs-tab <?= $active_tab === 'subject' ? 'qs-tab-active' : '' ?>">
            Subject-wise
        </a>
        <a href="leaderboard.php?purchased_id=<?= $purchased_id ?>&subject_id=<?= $subject_id ?>&topic_id=<?= $topic_id > 0 ? $topic_id : ($topics[0]['id'] ?? 0) ?>&time=<?= $time_filter ?>"
           class="qs-tab <?= $active_tab === 'topic' ? 'qs-tab-active' : '' ?>">
            Topic-wise
        </a>
    </div>

    <!-- Topic selector (topic-wise tab only) -->
    <?php if ($active_tab === 'topic' && !empty($topics)): ?>
    <div class="mb-3">
        <select class="form-select qs-eb-select"
                onchange="window.location.href='leaderboard.php?purchased_id=<?= $purchased_id ?>&subject_id=<?= $subject_id ?>&topic_id='+this.value+'&time=<?= $time_filter ?>'">
            <?php foreach ($topics as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $t['id'] == $topic_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- Time filter -->
    <div class="lb-time-filter mb-3">
        <?php foreach (['all' => 'All Time', 'month' => 'This Month', 'week' => 'This Week'] as $val => $label): ?>
            <a href="<?= $base_url ?>&time=<?= $val ?>"
               class="lb-time-btn <?= $time_filter === $val ? 'lb-time-active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Total participants -->
    <div class="lb-total mb-3">
        <?= $total_users ?> student<?= $total_users !== 1 ? 's' : '' ?> on the board
    </div>

    <!-- User rank card -->
    <?php if ($user_rank_info): ?>
    <div class="lb-my-rank-card mb-3">
        <div class="lb-my-rank-title">Your Rank</div>
        <div class="lb-my-rank-num"><?= $user_rank_info['rank'] === 1 ? '🏆' : ($user_rank_info['rank'] === 2 ? '🥈' : ($user_rank_info['rank'] === 3 ? '🥉' : '')) ?> #<?= $user_rank_info['rank'] ?> of <?= $total_users ?></div>
        <div class="lb-my-rank-stats">
            <span><?= $user_rank_info['attempted'] ?> attempted</span>
            <span><?= $user_rank_info['correct'] ?> correct</span>
            <span><?= $user_rank_info['wrong'] ?> wrong</span>
            <span><?= $user_rank_info['accuracy'] ?>% accuracy</span>
        </div>
        <?php if ($total_pages > 1 && $my_rank_page !== $page): ?>
            <a href="<?= $base_url ?>&time=<?= $time_filter ?>&page=<?= $my_rank_page ?>#my-row"
               class="btn-qs-sm mt-2 d-inline-block">Jump to my rank</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="lb-my-rank-card lb-no-rank mb-3">
        You haven't attempted any questions yet.
    </div>
    <?php endif; ?>

    <!-- Top 3 podium -->
    <?php if (count($leaderboard_all) >= 1 && $page === 1): ?>
    <div class="lb-podium mb-4">
        <?php
        $podium = array_slice($leaderboard_all, 0, 3);
        $podium_order = [];
        if (isset($podium[1])) $podium_order[] = $podium[1]; // silver left
        if (isset($podium[0])) $podium_order[] = $podium[0]; // gold center
        if (isset($podium[2])) $podium_order[] = $podium[2]; // bronze right
        foreach ($podium_order as $p):
            $cls = $p['rank'] === 1 ? 'lb-gold' : ($p['rank'] === 2 ? 'lb-silver' : 'lb-bronze');
            $icon = $p['rank'] === 1 ? '🏆' : ($p['rank'] === 2 ? '🥈' : '🥉');
        ?>
        <div class="lb-podium-item <?= $cls ?>">
            <div class="lb-podium-icon"><?= $icon ?></div>
            <div class="lb-podium-name"><?= htmlspecialchars($p['student_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="lb-podium-correct"><?= $p['correct'] ?> correct</div>
            <div class="lb-podium-accuracy"><?= $p['accuracy'] ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Leaderboard table -->
    <?php if (empty($leaderboard)): ?>
        <div class="qs-empty">No data yet for this <?= $topic_id > 0 ? 'topic' : 'subject' ?>.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Student</th>
                    <th>Attempted</th>
                    <th>Correct</th>
                    <th>Wrong</th>
                    <th>Accuracy %</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($leaderboard as $row):
                $rank_cls = $row['rank'] === 1 ? 'lb-row-gold' : ($row['rank'] === 2 ? 'lb-row-silver' : ($row['rank'] === 3 ? 'lb-row-bronze' : ''));
                $rank_icon = $row['rank'] === 1 ? '🏆 ' : ($row['rank'] === 2 ? '🥈 ' : ($row['rank'] === 3 ? '🥉 ' : ''));
                $is_me = (int)$row['user_id'] === $user_id;
            ?>
            <tr id="<?= $is_me ? 'my-row' : '' ?>" class="<?= $rank_cls ?> <?= $is_me ? 'lb-my-row' : '' ?>">
                <td><?= $rank_icon ?><?= $row['rank'] ?></td>
                <td><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?><?= $is_me ? ' <span class="lb-you-badge">You</span>' : '' ?></td>
                <td><?= $row['attempted'] ?></td>
                <td><?= $row['correct'] ?></td>
                <td><?= $row['wrong'] ?></td>
                <td><?= $row['accuracy'] ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="lb-pagination">
        <?php if ($page > 1): ?>
            <a href="<?= $base_url ?>&time=<?= $time_filter ?>&page=<?= $page - 1 ?>" class="btn-qs-sm">← Prev</a>
        <?php endif; ?>
        <span class="lb-page-info">Page <?= $page ?> of <?= $total_pages ?></span>
        <?php if ($page < $total_pages): ?>
            <a href="<?= $base_url ?>&time=<?= $time_filter ?>&page=<?= $page + 1 ?>" class="btn-qs-sm">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (window.location.hash === "#my-row") {
        const myRow = document.getElementById("my-row");
        if (myRow) myRow.scrollIntoView({ behavior: "smooth", block: "center" });
    }
});
</script>

<?php include "inc/footer.php"; ?>
</body>
</html>
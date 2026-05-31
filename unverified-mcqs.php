<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';

if (!has_role(ROLE_ADMIN) && !has_role(ROLE_DATA_ENTRY)) {
    header('Location: home.php');
    exit;
}

// Selected exam_body_id from GET, default 0
$selected_eb_id = isset($_GET['exam_body_id']) && ctype_digit($_GET['exam_body_id'])
    ? (int)$_GET['exam_body_id']
    : 0;

$active_tab = in_array($_GET['tab'] ?? '', ['practice', 'past_paper'])
    ? $_GET['tab']
    : 'practice';

// 1. All exam bodies for dropdown
$res         = mysqli_query($conn, 'SELECT id, name FROM exam_bodies ORDER BY id ASC');
$exam_bodies = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);

if ($selected_eb_id === 0 && !empty($exam_bodies)) {
    $selected_eb_id = (int)$exam_bodies[0]['id'];
}

// 2. Subjects with unverified topics — admin sees all, data entry sees own
$subjects = [];
if ($active_tab === 'practice') {
    $sql = 'SELECT s.id AS subject_id, s.name AS subject_name,
                   t.id AS topic_id, t.name AS topic_name,
                   COUNT(qs.id) AS unverified_count
            FROM subjects s
            JOIN topics t         ON t.subject_id = s.id
            JOIN exam_bodies eb   ON eb.id = s.exam_body_id
            JOIN question_sets qs ON qs.topic_id = t.id
            WHERE eb.id = ? AND qs.verified = 0';

    if (has_role(ROLE_DATA_ENTRY)) {
        $sql .= ' AND qs.created_by = ?';
    }

    $sql .= ' GROUP BY s.id, t.id ORDER BY s.name ASC, t.name ASC';

    $stmt = mysqli_prepare($conn, $sql);

    if (has_role(ROLE_DATA_ENTRY)) {
        mysqli_stmt_bind_param($stmt, 'ii', $selected_eb_id, $user_id);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $selected_eb_id);
    }

    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);

    // Group by subject
    foreach ($rows as $row) {
        $sid = $row['subject_id'];
        if (!isset($subjects[$sid])) {
            $subjects[$sid] = [
                'subject_name' => $row['subject_name'],
                'topics'       => [],
            ];
        }
        $subjects[$sid]['topics'][] = [
            'topic_id'        => $row['topic_id'],
            'topic_name'      => $row['topic_name'],
            'unverified_count' => $row['unverified_count'],
        ];
    }
}

// 3. Past papers with unverified count
$past_papers = [];

if ($active_tab === 'past_paper') {

    $pp_sql = 'SELECT pp.id AS past_paper_id, pp.year,
                    s.name AS subject_name,
                    COUNT(qs.id) AS unverified_count
            FROM past_papers pp
            JOIN subjects s     ON s.id  = pp.subject_id
            JOIN exam_bodies eb ON eb.id = s.exam_body_id
            JOIN question_sets qs ON qs.past_paper_id = pp.id
            WHERE eb.id = ? AND qs.verified = 0';

    if (has_role(ROLE_DATA_ENTRY)) {
        $pp_sql .= ' AND qs.created_by = ?';
    }

    $pp_sql .= ' GROUP BY pp.id ORDER BY s.name ASC, pp.year DESC';

    $stmt = mysqli_prepare($conn, $pp_sql);

    if (has_role(ROLE_DATA_ENTRY)) {
        mysqli_stmt_bind_param($stmt, 'ii', $selected_eb_id, $user_id);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $selected_eb_id);
    }

    mysqli_stmt_execute($stmt);
    $res         = mysqli_stmt_get_result($stmt);
    $past_papers = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unverified MCQs</title>
    <?php include 'inc/links.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>

<div class="container-fluid">

    <div class="qs-list-header">
        <div class="qs-list-title">Unverified question sets</div>
    </div>

    <form method="GET" id="ebForm">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
        <select name="exam_body_id" class="form-select qs-eb-select"
                onchange="document.getElementById('ebForm').submit()">
            <?php foreach ($exam_bodies as $eb): ?>
                <option value="<?= $eb['id'] ?>"
                    <?= $eb['id'] == $selected_eb_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($eb['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="qs-tabs">
        <a href="?exam_body_id=<?= $selected_eb_id ?>&tab=practice"
           class="qs-tab <?= $active_tab === 'practice' ? 'qs-tab-active' : '' ?>">
            Practice Questions
        </a>
        <a href="?exam_body_id=<?= $selected_eb_id ?>&tab=past_paper"
           class="qs-tab <?= $active_tab === 'past_paper' ? 'qs-tab-active' : '' ?>">
            Past Papers
        </a>
    </div>

    <?php if ($active_tab === 'practice'): ?>

        <?php if (empty($subjects)): ?>
            <div class="qs-empty">
                <?= has_role(ROLE_DATA_ENTRY)
                    ? 'You have no pending unverified submissions for this exam body.'
                    : 'No unverified question sets for this exam body.' ?>
            </div>
        <?php else: ?>
            <?php foreach ($subjects as $subject): ?>
                <div class="uv-subject-header">
                    <?= htmlspecialchars($subject['subject_name']) ?>
                </div>
                <?php foreach ($subject['topics'] as $t): ?>
                    <div class="uv-topic-row"
                        onclick="window.location.href='unverified-mcq-list.php?topic_id=<?= $t['topic_id'] ?>'">
                        <span class="uv-topic-name"><?= htmlspecialchars($t['topic_name']) ?></span>
                        <span class="uv-topic-count"><?= $t['unverified_count'] ?> unverified</span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php else: ?>

        <?php if (empty($past_papers)): ?>
            <div class="qs-empty">
                <?= has_role(ROLE_DATA_ENTRY)
                    ? 'You have no pending unverified past paper submissions for this exam body.'
                    : 'No unverified past paper question sets for this exam body.' ?>
            </div>
        <?php else: ?>
            <?php foreach ($past_papers as $pp): ?>
            <div class="uv-topic-row"
                 onclick="window.location.href='unverified-mcq-list.php?past_paper_id=<?= $pp['past_paper_id'] ?>'">
                <span class="uv-topic-name">
                    <?= htmlspecialchars($pp['subject_name']) ?> — <?= (int)$pp['year'] ?>
                </span>
                <span class="uv-topic-count"><?= $pp['unverified_count'] ?> unverified</span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
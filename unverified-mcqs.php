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

// 1. All exam bodies for dropdown
$res         = mysqli_query($conn, 'SELECT id, name FROM exam_bodies ORDER BY id ASC');
$exam_bodies = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);

if ($selected_eb_id === 0 && !empty($exam_bodies)) {
    $selected_eb_id = (int)$exam_bodies[0]['id'];
}

// 2. Topics with unverified count — admin sees all, data entry sees own
$sql = 'SELECT t.id AS topic_id, t.name AS topic_name,
               COUNT(qs.id) AS unverified_count
        FROM topics t
        JOIN subjects s       ON s.id  = t.subject_id
        JOIN exam_bodies eb   ON eb.id = s.exam_body_id
        JOIN question_sets qs ON qs.topic_id = t.id
        WHERE eb.id = ? AND qs.verified = 0';

if (has_role(ROLE_DATA_ENTRY)) {
    $sql .= ' AND qs.created_by = ?';
}

$sql .= ' GROUP BY t.id ORDER BY t.id ASC';

$stmt = mysqli_prepare($conn, $sql);

if (has_role(ROLE_DATA_ENTRY)) {
    mysqli_stmt_bind_param($stmt, 'ii', $selected_eb_id, $user_id);
} else {
    mysqli_stmt_bind_param($stmt, 'i', $selected_eb_id);
}

mysqli_stmt_execute($stmt);
$res    = mysqli_stmt_get_result($stmt);
$topics = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);
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

    <?php if (empty($topics)): ?>
        <div class="qs-empty">
            <?= has_role(ROLE_DATA_ENTRY)
                ? 'You have no pending unverified submissions for this exam body.'
                : 'No unverified question sets for this exam body.' ?>
        </div>
    <?php else: ?>
        <?php foreach ($topics as $t): ?>
        <div class="uv-topic-row"
             onclick="window.location.href='unverified-mcq-details.php?topic_id=<?= $t['topic_id'] ?>'">
            <span class="uv-topic-name"><?= htmlspecialchars($t['topic_name']) ?></span>
            <span class="uv-topic-count"><?= $t['unverified_count'] ?> unverified</span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
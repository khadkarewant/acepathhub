<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_ADMIN);

// ── AJAX ACTION ───────────────────────────────────────────────────────────
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    csrf_verify();

    $action = $_POST['action'] ?? '';
    $id     = isset($_POST['id']) && ctype_digit($_POST['id']) ? (int)$_POST['id'] : 0;

    if (!in_array($action, ['resolve', 'dismiss', 'delete'], true) || $id === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    if ($action === 'delete') {
        $stmt = mysqli_prepare($conn, "DELETE FROM question_reports WHERE id = ? LIMIT 1");
    } else {
        $new_status = $action === 'resolve' ? 'resolved' : 'dismissed';
        $stmt = mysqli_prepare($conn, "UPDATE question_reports SET status = ? WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $new_status, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo json_encode(['status' => 'ok']);
        } else {
            mysqli_stmt_close($stmt);
            echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        }
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'ok']);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
    }
    exit;
}

// ── Filters + Pagination ──────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'pending';
if (!in_array($filter, ['all', 'pending', 'resolved', 'dismissed'], true)) {
    $filter = 'pending';
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = $filter !== 'all' ? "WHERE qr.status = '{$filter}'" : '';

// ── Total count ───────────────────────────────────────────────────────────
$count_res   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM question_reports qr {$where}");
$total_count = (int)(mysqli_fetch_assoc($count_res)['cnt'] ?? 0);
$total_pages = (int)ceil($total_count / $perPage);
mysqli_free_result($count_res);

// ── Fetch reports ─────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT qr.id, qr.reason, qr.status, qr.created_at,
            q.id AS question_id, LEFT(q.question, 120) AS question_preview,
            qs.id AS question_set_id,
            t.name AS topic_name,
            s.name AS subject_name,
            u.username
     FROM question_reports qr
     JOIN questions q     ON q.id      = qr.question_id
     JOIN question_sets qs ON qs.id    = q.question_set_id
     LEFT JOIN topics t   ON t.id      = qs.topic_id
     LEFT JOIN subjects s ON s.id      = t.subject_id
     JOIN users u         ON u.user_id = qr.user_id
     {$where}
     ORDER BY qr.created_at DESC
     LIMIT ? OFFSET ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $perPage, $offset);
mysqli_stmt_execute($stmt);
$r       = mysqli_stmt_get_result($stmt);
$reports = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Reports</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">

    <div class="qs-list-header mb-4">
        <div class="qs-list-title">Question Reports</div>
    </div>

    <!-- Filters -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <?php foreach (['pending' => 'Pending', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed', 'all' => 'All'] as $key => $label): ?>
            <a href="?filter=<?= $key ?>"
               class="<?= $filter === $key ? 'btn-qs-gold' : 'btn-qs-sm' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
        <span class="mp-meta ms-auto align-self-center"><?= $total_count ?> report<?= $total_count != 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($reports)): ?>
        <div class="mp-empty">No reports found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Topic</th>
                        <th>Reported By</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $row): ?>
                    <tr id="report-<?= (int)$row['id'] ?>">
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= htmlspecialchars(strip_tags($row['question_preview']), ENT_QUOTES, 'UTF-8') ?>...</td>
                        <td>
                            <?php if ($row['subject_name']): ?>
                                <div class="mp-meta"><?= htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($row['topic_name']): ?>
                                <div><?= htmlspecialchars($row['topic_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mp-meta"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <?php
                                $badge = match($row['status']) {
                                    'resolved'  => 'mp-badge-active',
                                    'dismissed' => 'mp-badge-exhausted',
                                    default     => 'mp-badge-expired'
                                };
                            ?>
                            <span class="<?= $badge ?>"><?= ucfirst($row['status']) ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="mcq-details.php?id=<?= (int)$row['question_set_id'] ?>"
                                   class="btn-qs-sm" target="_blank">View</a>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <button class="btn-qs-gold btn-sm action-btn"
                                            data-id="<?= (int)$row['id'] ?>"
                                            data-action="resolve">Resolve</button>
                                    <button class="btn-reset-sm action-btn"
                                            data-id="<?= (int)$row['id'] ?>"
                                            data-action="dismiss">Dismiss</button>
                                <?php endif; ?>
                                <button class="btn-reset-sm action-btn"
                                        data-id="<?= (int)$row['id'] ?>"
                                        data-action="delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>

</div>

<div id="csrf-holder" style="display:none;"><?= csrf_input() ?></div>

<script>
function getCsrf() {
    const el = document.querySelector('#csrf-holder input[type="hidden"]');
    return el ? { name: el.name, value: el.value } : null;
}

$(document).on('click', '.action-btn', function () {
    const action = $(this).data('action');
    const id     = $(this).data('id');
    const msgs   = { resolve: 'Mark as resolved?', dismiss: 'Dismiss this report?', delete: 'Delete permanently?' };
    if (!confirm(msgs[action] || 'Continue?')) return;

    const btn     = $(this);
    const csrf    = getCsrf();
    const payload = { action: action, id: id };
    if (csrf) payload[csrf.name] = csrf.value;

    btn.prop('disabled', true);

    $.post('question-reports.php', payload, function (res) {
        if (res.status === 'ok') {
            if (action === 'delete') {
                $('#report-' + id).remove();
            } else {
                location.reload();
            }
        } else {
            alert(res.message || 'Failed.');
            btn.prop('disabled', false);
        }
    }, 'json');
});
</script>

<?php include "inc/footer.php"; ?>
</body>
</html>
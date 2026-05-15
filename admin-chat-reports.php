<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

/**
 * NOTE:
 * - This file supports 2 modes:
 *   1) Normal page view (GET): list + filters + pagination
 *   2) AJAX actions (POST): resolve/delete
 */

// ---- Authorization (keep your policy) ----
$user_id = (int)($_SESSION['id'] ?? 0);
$allowed_users = [1]; // admin IDs
if (!in_array($user_id, $allowed_users, true)) {
    header("Location: index.php");
    exit;
}

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ---------------- AJAX ACTION HANDLER (POST) ----------------
if (isset($_POST['action'])) {
    // POST-only + CSRF
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    csrf_verify();

    $action = (string)($_POST['action'] ?? '');
    $id_raw = (string)($_POST['id'] ?? '');

    if (!in_array($action, ['resolve', 'delete'], true) || !ctype_digit($id_raw)) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    $id = (int)$id_raw;

    if ($action === 'resolve') {
        $stmt = mysqli_prepare($conn, "UPDATE chat_reports SET resolved = 1 WHERE id = ? LIMIT 1");
        if (!$stmt) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(['status' => 'error', 'message' => 'Server error']);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($ok ? ['status' => 'ok'] : ['status' => 'error', 'message' => 'Update failed']);
        exit;
    }

    if ($action === 'delete') {
        $stmt = mysqli_prepare($conn, "DELETE FROM chat_reports WHERE id = ? LIMIT 1");
        if (!$stmt) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(['status' => 'error', 'message' => 'Server error']);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($ok ? ['status' => 'ok'] : ['status' => 'error', 'message' => 'Delete failed']);
        exit;
    }

    // Fallback (should never hit)
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// ---------------- PAGE VIEW (GET) ----------------

// Filter allowlist (prevents weird injection via filter)
$filter = (string)($_GET['filter'] ?? 'all');
if (!in_array($filter, ['all', 'pending', 'resolved'], true)) {
    $filter = 'all';
}

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
if ($filter === 'pending') $where = 'WHERE r.resolved = 0';
elseif ($filter === 'resolved') $where = 'WHERE r.resolved = 1';

// Total count
$total_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM chat_reports r $where");
$total_row = $total_res ? mysqli_fetch_assoc($total_res) : null;
$total_count = (int)($total_row['cnt'] ?? 0);
$total_pages = (int)ceil($total_count / $perPage);

// Fetch reports (LIMIT/OFFSET are ints we control)
$res = mysqli_query($conn, "
    SELECT r.*, d.message, u.username
    FROM chat_reports r
    JOIN discussions d ON r.message_id = d.id
    JOIN users u ON r.reported_by = u.user_id
    $where
    ORDER BY r.created_at DESC
    LIMIT $offset,$perPage
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat Reports - Admin</title>
<?php include("src/inc/links.php"); ?>
<link rel="stylesheet" href="src/css/bootstrap.css">
<style>
.resolved {background:#d4edda;}
.action-btn {margin-right:5px;}
</style>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<!-- Hidden CSRF field to reuse for AJAX without guessing token name -->
<div id="csrf-holder" style="display:none;">
    <?= csrf_input(); ?>
</div>

<div class="container mt-4">
    <h4>Chat Reports</h4>

    <div class="mb-3">
        <a href="?filter=all" class="btn btn-sm <?= $filter=='all'?'btn-primary':'btn-outline-primary'; ?>">All</a>
        <a href="?filter=pending" class="btn btn-sm <?= $filter=='pending'?'btn-primary':'btn-outline-primary'; ?>">Pending</a>
        <a href="?filter=resolved" class="btn btn-sm <?= $filter=='resolved'?'btn-primary':'btn-outline-primary'; ?>">Resolved</a>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Message</th>
                <th>Reported By</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($res): ?>
            <?php while ($row = mysqli_fetch_assoc($res)): ?>
                <tr id="report-<?= (int)$row['id']; ?>" class="<?= !empty($row['resolved']) ? 'resolved' : ''; ?>">
                    <td><?= (int)$row['id']; ?></td>
                    <td><?= e($row['message'] ?? ''); ?></td>
                    <td><?= e($row['username'] ?? ''); ?></td>
                    <td><?= e($row['reason'] ?? ''); ?></td>
                    <td><?= e($row['created_at'] ?? ''); ?></td>
                    <td><?= !empty($row['resolved']) ? 'Resolved' : 'Pending'; ?></td>
                    <td>
                        <?php if (empty($row['resolved'])): ?>
                            <button class="btn btn-success btn-sm action-btn resolve-btn" data-id="<?= (int)$row['id']; ?>">Resolve</button>
                        <?php endif; ?>
                        <button class="btn btn-danger btn-sm action-btn delete-btn" data-id="<?= (int)$row['id']; ?>">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <nav>
      <ul class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?filter=<?= e($filter); ?>&page=<?= (int)$i; ?>"><?= (int)$i; ?></a>
            </li>
        <?php endfor; ?>
      </ul>
    </nav>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function getCsrfKV() {
    const $input = $('#csrf-holder input[type="hidden"]').first();
    return { name: $input.attr('name'), value: $input.val() };
}

$(document).on('click', '.resolve-btn', function(){
    if(!confirm('Mark this report as resolved?')) return;

    let id = $(this).data('id');
    const csrf = getCsrfKV();

    let payload = { action:'resolve', id:id };
    payload[csrf.name] = csrf.value;

    $.post('admin-chat-reports.php', payload, function(res){
        if(res.status === 'ok'){
            let $row = $('#report-'+id);
            $row.addClass('resolved');
            $row.find('td:nth-child(6)').text('Resolved');
            $row.find('.resolve-btn').remove();
        } else {
            alert('Error: ' + (res.message || 'Unknown error'));
        }
    }, 'json');
});

$(document).on('click', '.delete-btn', function(){
    if(!confirm('Delete this report permanently?')) return;

    let id = $(this).data('id');
    const csrf = getCsrfKV();

    let payload = { action:'delete', id:id };
    payload[csrf.name] = csrf.value;

    $.post('admin-chat-reports.php', payload, function(res){
        if(res.status === 'ok'){
            $('#report-'+id).remove();
        } else {
            alert('Error: ' + (res.message || 'Unknown error'));
        }
    }, 'json');
});
</script>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN, ROLE_STUDENT);

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
    header("Location: products.php"); exit;
}

// Fetch product
$stmt = $conn->prepare("
    SELECT id, name, product_type, exam_body_id, description, duration_minutes, total_questions,total_marks, sets, price, status
    FROM products
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: products.php"); exit;
}

// Student can only see active products
if (has_role(ROLE_STUDENT) && $product['status'] !== 'active') {
    header("Location: products.php"); exit;
}

$product_type = $product['product_type'];

// Fetch subjects linked to this product
$stmt = $conn->prepare("
    SELECT t.id, t.name, t.subject_id, s.name AS subject_name
    FROM topics t
    JOIN subjects s ON s.id = t.subject_id
    WHERE s.exam_body_id = ?
    ORDER BY s.name ASC, t.name ASC
");
$stmt->bind_param("i", $product['exam_body_id']);
$stmt->execute();
$all_topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$subjects = [];
$topics_by_subject = [];
foreach ($all_topics as $t) {
    $topics_by_subject[$t['subject_id']][] = $t;
    if (!isset($subjects[$t['subject_id']])) {
        $subjects[$t['subject_id']] = ['id' => $t['subject_id'], 'name' => $t['subject_name']];
    }
}
$subjects = array_values($subjects);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_role(ROLE_ADMIN)) {
    csrf_verify();

    // --- Add exam group (mock) ---
    if (isset($_POST['add_exam_group'])) {
        $group_name     = trim((string)($_POST['group_name'] ?? ''));
        $question_count = isset($_POST['question_count']) ? (int)$_POST['question_count'] : 0;

        if ($group_name === '' || $question_count <= 0) {
            $msg = '<p class="text-danger">Group name and question count are required.</p>';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO exam_groups (product_id, name, question_count)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("isi", $product_id, $group_name, $question_count);
            $msg = $stmt->execute()
                ? '<p class="text-success">Exam group added.</p>'
                : '<p class="text-danger">Failed to add exam group.</p>';
            $stmt->close();
        }
    }

    // --- Add practice group ---
    if (isset($_POST['add_practice_group'])) {
        $group_name = trim((string)($_POST['group_name'] ?? ''));
        $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;

        if ($group_name === '') {
            $msg = '<p class="text-danger">Group name is required.</p>';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO practice_groups (product_id, name, sort_order)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("isi", $product_id, $group_name, $sort_order);
            $msg = $stmt->execute()
                ? '<p class="text-success">Practice group added.</p>'
                : '<p class="text-danger">Failed to add practice group.</p>';
            $stmt->close();
        }
    }

    // --- Save exam group topics ---
    if (isset($_POST['save_exam_group_topics'])) {
        $group_id       = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
        $selected_topics = isset($_POST['topics']) && is_array($_POST['topics'])
            ? array_map('intval', $_POST['topics'])
            : [];

        if ($group_id <= 0) {
            $msg = '<p class="text-danger">Invalid group.</p>';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM exam_group_topics WHERE exam_group_id = ?");
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $stmt->close();

                if (!empty($selected_topics)) {
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO exam_group_topics (exam_group_id, topic_id)
                        VALUES (?, ?)
                    ");
                    foreach ($selected_topics as $tid) {
                        $stmt->bind_param("ii", $group_id, $tid);
                        $stmt->execute();
                    }
                    $stmt->close();
                }

                $conn->commit();
                $msg = '<p class="text-success">Topics updated.</p>';
            } catch (Throwable $e) {
                $conn->rollback();
                $msg = '<p class="text-danger">Failed to update topics.</p>';
            }
        }
    }

    // --- Save practice group topics ---
    if (isset($_POST['save_practice_group_topics'])) {
        $group_id        = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
        $selected_topics = isset($_POST['topics']) && is_array($_POST['topics'])
            ? array_map('intval', $_POST['topics'])
            : [];

        if ($group_id <= 0) {
            $msg = '<p class="text-danger">Invalid group.</p>';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM practice_group_topics WHERE practice_group_id = ?");
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $stmt->close();

                if (!empty($selected_topics)) {
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO practice_group_topics (practice_group_id, topic_id)
                        VALUES (?, ?)
                    ");
                    foreach ($selected_topics as $tid) {
                        $stmt->bind_param("ii", $group_id, $tid);
                        $stmt->execute();
                    }
                    $stmt->close();
                }

                $conn->commit();
                $msg = '<p class="text-success">Topics updated.</p>';
            } catch (Throwable $e) {
                $conn->rollback();
                $msg = '<p class="text-danger">Failed to update topics.</p>';
            }
        }
    }

    // PRG
    header("Location: product-details.php?product_id=" . $product_id . "&ok=1");
    exit;
}

// Fetch groups AFTER post handling (so page reflects latest state)
$groups = [];
if ($product_type === "mock") {
    $stmt = $conn->prepare("
        SELECT id, name, question_count
        FROM exam_groups
        WHERE product_id = ?
        ORDER BY id ASC
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch assigned topics per group
    foreach ($groups as &$g) {
        $stmt = $conn->prepare("
            SELECT t.id, t.name
            FROM exam_group_topics egt
            JOIN topics t ON t.id = egt.topic_id
            WHERE egt.exam_group_id = ?
        ");
        $stmt->bind_param("i", $g['id']);
        $stmt->execute();
        $g['topics'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    unset($g);
} else {
    $stmt = $conn->prepare("
        SELECT id, name, sort_order
        FROM practice_groups
        WHERE product_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($groups as &$g) {
        $stmt = $conn->prepare("
            SELECT t.id, t.name
            FROM practice_group_topics pgt
            JOIN topics t ON t.id = pgt.topic_id
            WHERE pgt.practice_group_id = ?
        ");
        $stmt->bind_param("i", $g['id']);
        $stmt->execute();
        $g['topics'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    unset($g);
}

$ok = isset($_GET['ok']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> — Details</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid p-3">

    <?php if ($ok): ?>
        <div class="alert alert-success alert-dismissible">Saved. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?= $msg ?>

    <!-- Product Info -->
    <div class="table-responsive mb-3">
        <table class="table">
            <tbody>
                <tr><th>Name</th><td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Type</th><td><?= ucfirst(str_replace('_', ' ', $product_type)) ?></td></tr>
                <tr><th>Duration</th><td><?= $product['duration_minutes'] ?> minutes</td></tr>
                <tr><th>Total Questions</th><td><?= $product['total_questions'] ?></td></tr>
                <tr><th>Description</th><td><?= htmlspecialchars($product['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Total Marks</th><td><?= $product['total_marks'] ?></td></tr>
                <?php if ($product_type !== 'practice'): ?>
                <tr><th>Sets</th><td><?= $product['sets'] ?></td></tr>
                <?php endif; ?>
                <tr><th>Price</th><td>₦<?= number_format((float)$product['price'], 2) ?></td></tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge <?= $product['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($product['status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Subjects</th>
                    <td><?= !empty($subjects) ? implode(', ', array_column($subjects, 'name')) : '—' ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Action Buttons -->
    <div class="mb-4">
        <?php if (has_role(ROLE_STUDENT)): ?>
            <a href="inc/pdf/<?= $product_id ?>.pdf"
               target="_blank"
               class="btn btn-sm btn-outline-secondary me-1">View Syllabus</a>
            <a href="https://wa.me/2348169321558?text=<?= rawurlencode('I want to purchase: ' . $product['name'] . '. My username is: ' . $username) ?>"
               target="_blank"
               class="btn btn-sm"
               style="background:var(--accent);color:#000;">Purchase</a>
        <?php endif; ?>

        <?php if (has_role(ROLE_ADMIN)): ?>
            <a href="product-edit.php?product_id=<?= $product_id ?>"
               class="btn btn-sm btn-dark me-1">Update</a>

            <?php if ($product['status'] === 'inactive'): ?>
                <form method="POST" action="product-status-change.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn btn-sm btn-success">Make Active</button>
                </form>
            <?php else: ?>
                <form method="POST" action="product-status-change.php" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="status" value="inactive">
                    <button type="submit" class="btn btn-sm btn-secondary">Make Inactive</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Groups Management (Admin only) -->
    <?php if (has_role(ROLE_ADMIN)): ?>
        <hr>
        <h6><?= $product_type === 'practice' ? 'Practice Groups' : 'Exam Groups' ?></h6>

        <!-- Add Group Form -->
        <form method="POST" class="mb-4">
            <?= csrf_input() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Group Name</label>
                    <input type="text" name="group_name" class="form-control form-control-sm" required>
                </div>
                <?php if ($product_type !== 'practice'): ?>
                <div class="col-md-3">
                    <label class="form-label small">Question Count</label>
                    <input type="number" name="question_count" min="1" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="add_exam_group"
                            class="btn btn-sm w-100"
                            style="background:var(--accent);color:#000;">Add Group</button>
                </div>
                <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label small">Sort Order</label>
                    <input type="number" name="sort_order" min="0" value="0" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="add_practice_group"
                            class="btn btn-sm w-100"
                            style="background:var(--accent);color:#000;">Add Group</button>
                </div>
                <?php endif; ?>
            </div>
        </form>

        <!-- Existing Groups -->
        <?php if (empty($groups)): ?>
            <p class="text-muted small">No groups added yet.</p>
        <?php endif; ?>

        <?php foreach ($groups as $g): ?>
            <div class="card mb-3" style="background:#13131a;border:1px solid #2a2a3a;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span style="color:var(--accent);">
                        <?= htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($product_type !== 'practice'): ?>
                            <small class="text-muted">(<?= $g['question_count'] ?> questions)</small>
                        <?php else: ?>
                            <small class="text-muted">(sort: <?= $g['sort_order'] ?>)</small>
                        <?php endif; ?>
                    </span>
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick="document.getElementById('topics_form_<?= $g['id'] ?>').classList.toggle('d-none')">
                        Edit Topics
                    </button>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Topics:</strong>
                        <?= !empty($g['topics'])
                            ? htmlspecialchars(implode(', ', array_column($g['topics'], 'name')), ENT_QUOTES, 'UTF-8')
                            : '<span class="text-muted">None assigned</span>' ?>
                    </p>

                    <!-- Topic assignment form -->
                    <form id="topics_form_<?= $g['id'] ?>" method="POST" class="d-none">
                        <?= csrf_input() ?>
                        <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                        <select name="topics[]" class="form-control form-control-sm mb-2" multiple size="8">
                            <?php
                            $assigned_ids = array_column($g['topics'], 'id');
                            foreach ($topics_by_subject as $sub_id => $sub_topics):
                                // Find subject name
                                $sub_name = '';
                                foreach ($subjects as $s) {
                                    if ($s['id'] == $sub_id) { $sub_name = $s['name']; break; }
                                }
                            ?>
                                <optgroup label="<?= htmlspecialchars($sub_name, ENT_QUOTES, 'UTF-8') ?>">
                                <?php foreach ($sub_topics as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                        <?= in_array($t['id'], $assigned_ids) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"
                                name="<?= $product_type === 'practice' ? 'save_practice_group_topics' : 'save_exam_group_topics' ?>"
                                class="btn btn-sm btn-success">Save Topics</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
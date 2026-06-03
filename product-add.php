<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN);

$stmt = mysqli_prepare($conn, "SELECT id, name FROM exam_bodies ORDER BY name ASC");
mysqli_stmt_execute($stmt);
$res         = mysqli_stmt_get_result($stmt);
$exam_bodies = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit'] ?? '') === 'add_product') {
    csrf_verify();

    $name             = trim((string)($_POST['name'] ?? ''));
    $product_type     = trim((string)($_POST['product_type'] ?? 'mock'));
    $allowed_types    = ['mock', 'practice', 'past_paper'];
    if (!in_array($product_type, $allowed_types, true)) $product_type = 'mock';
    $description      = trim((string)($_POST['description'] ?? ''));
    $exam_body_id     = isset($_POST['exam_body_id']) ? (int)$_POST['exam_body_id'] : 0;
    $price            = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $sets             = ($product_type === 'mock') ? (int)($_POST['sets'] ?? 0) : 1;
    $duration_minutes = ($product_type !== 'practice') ? (int)($_POST['duration_minutes'] ?? 0) : 0;
    $total_questions  = ($product_type !== 'practice') ? (int)($_POST['total_questions'] ?? 0) : 0;
    $total_marks      = ($product_type !== 'practice') ? (int)($_POST['total_marks'] ?? 0) : 0;

    if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
        $err = 'Product name is required and must be under 120 characters.';
    } elseif ($exam_body_id <= 0) {
        $err = 'Exam body is required.';
    } elseif ($price < 0) {
        $err = 'Price cannot be negative.';
    } elseif ($product_type !== 'practice' && $duration_minutes <= 0) {
        $err = 'Duration must be greater than 0.';
    } elseif ($product_type !== 'practice' && $total_questions <= 0) {
        $err = 'Total questions must be greater than 0.';
    } elseif ($product_type !== 'practice' && $total_marks <= 0) {
        $err = 'Total marks must be greater than 0.';
    } elseif ($product_type === 'mock' && $sets <= 0) {
        $err = 'Sets must be greater than 0 for mock exam.';
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO products
                     (name, product_type, exam_body_id, description, duration_minutes, total_questions, total_marks, sets, price, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')"
            );
            mysqli_stmt_bind_param(
                $stmt, "ssisiiiid",
                $name, $product_type, $exam_body_id, $description,
                $duration_minutes, $total_questions, $total_marks, $sets, $price
            );
            mysqli_stmt_execute($stmt);
            $product_id = (int)mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            mysqli_commit($conn);
            header("Location: product-details.php?product_id=" . $product_id . "&ok=1");
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $err = 'Failed to create product. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid p-3">
    <div class="row">
        <div class="col-md-6">

            <div class="qs-list-header mb-3">
                <div class="qs-list-title">Add Product</div>
                <a href="products.php" class="btn-qs-sm">← Back</a>
            </div>

            <?php if ($err !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_input() ?>

                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" maxlength="120" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description <small class="text-muted">(optional)</small></label>
                    <input type="text" name="description" class="form-control" maxlength="255"
                        value="<?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Exam Body</label>
                    <select name="exam_body_id" class="form-control" required>
                        <option value="" disabled selected>Select Exam Body</option>
                        <?php foreach ($exam_bodies as $eb): ?>
                            <option value="<?= $eb['id'] ?>"
                                <?= (isset($_POST['exam_body_id']) && (int)$_POST['exam_body_id'] === $eb['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($eb['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="product_type" value="mock" id="type_mock"
                            <?= (($_POST['product_type'] ?? 'mock') === 'mock') ? 'checked' : '' ?>
                            onchange="toggleProductType('mock')">
                        <label class="form-check-label" for="type_mock">Mock Exam</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="product_type" value="practice" id="type_practice"
                            <?= (($_POST['product_type'] ?? '') === 'practice') ? 'checked' : '' ?>
                            onchange="toggleProductType('practice')">
                        <label class="form-check-label" for="type_practice">Practice</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="product_type" value="past_paper" id="type_past_paper"
                            <?= (($_POST['product_type'] ?? '') === 'past_paper') ? 'checked' : '' ?>
                            onchange="toggleProductType('past_paper')">
                        <label class="form-check-label" for="type_past_paper">Past Paper</label>
                    </div>
                </div>

                <div class="mb-3" id="sets_row" <?= in_array(($_POST['product_type'] ?? 'mock'), ['practice', 'past_paper']) ? 'class="d-none"' : '' ?>>
                    <label class="form-label">Sets</label>
                    <input type="number" name="sets" class="form-control" min="1"
                           value="<?= (int)($_POST['sets'] ?? 1) ?>">
                </div>

                <div id="exam_fields_row" <?= (($_POST['product_type'] ?? 'mock') === 'practice') ? 'class="d-none"' : '' ?>>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" min="1"
                               value="<?= (int)($_POST['duration_minutes'] ?? 60) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Questions</label>
                        <input type="number" name="total_questions" class="form-control" min="1"
                               value="<?= (int)($_POST['total_questions'] ?? 50) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Marks</label>
                        <input type="number" name="total_marks" class="form-control" min="1"
                            value="<?= (int)($_POST['total_marks'] ?? 100) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (₦)</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01" required
                           value="<?= htmlspecialchars($_POST['price'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" name="submit" value="add_product"
                        class="btn-qs-gold">
                    Add Product
                </button>

            </form>
        </div>
    </div>
</div>

<script>
function toggleProductType(type) {
    document.getElementById('sets_row').classList.toggle('d-none', type !== 'mock');
    document.getElementById('exam_fields_row').classList.toggle('d-none', type === 'practice');
}
</script>

<?php include("inc/footer.php"); ?>
</body>
</html>
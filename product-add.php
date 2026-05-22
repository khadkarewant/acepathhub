<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN);

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit'] ?? '') === 'add_product') {
    csrf_verify();

    $name                  = trim((string)($_POST['name'] ?? ''));
    $product_type = trim((string)($_POST['product_type'] ?? 'mock'));
    $duration_minutes      = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 0;
    $total_questions       = isset($_POST['total_questions']) ? (int)$_POST['total_questions'] : 0;
    $mark_per_question     = isset($_POST['mark_per_question']) ? (float)$_POST['mark_per_question'] : 0.0;
    $negative_mark_percent = isset($_POST['negative_mark_percent']) ? (float)$_POST['negative_mark_percent'] : 0.0;
    $allowed_types = ['mock', 'practice', 'past_paper'];
    if (!in_array($product_type, $allowed_types, true)) $product_type = 'mock';
    $description  = trim((string)($_POST['description'] ?? ''));
    $sets         = ($product_type === 'mock') ? (int)($_POST['sets'] ?? 0) : 1;
    $price                 = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;

    // Validate
    if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
        $err = 'Product name is required and must be under 120 characters.';
    } elseif ($duration_minutes <= 0) {
        $err = 'Duration must be greater than 0.';
    } elseif ($total_questions <= 0) {
        $err = 'Total questions must be greater than 0.';
    } elseif ($mark_per_question <= 0) {
        $err = 'Mark per question must be greater than 0.';
    } elseif ($negative_mark_percent < 0 || $negative_mark_percent > 100) {
        $err = 'Negative mark percent must be between 0 and 100.';
    } elseif ($product_type === "mock" && $sets <= 0) {
        $err = 'Sets must be greater than 0 for mock exam.';
    } elseif ($price < 0) {
        $err = 'Price cannot be negative.';
    } else {
        $conn->begin_transaction();
        try {
            // Insert product
            $stmt = $conn->prepare("
                INSERT INTO products
                    (name, product_type, description, duration_minutes, total_questions,
                    mark_per_question, negative_mark_percent, sets, price, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param(
                "sssiiiddi",
                $name,
                $product_type,
                $description,
                $duration_minutes,
                $total_questions,
                $mark_per_question,
                $negative_mark_percent,
                $sets,
                $price
            );
            $stmt->execute();
            $product_id = (int)$conn->insert_id;
            $stmt->close();

            $conn->commit();
            header("Location: product-details.php?product_id=" . $product_id . "&ok=1");
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
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

            <h4 class="mb-3">Add Product</h4>

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
                    <label class="form-label">Type</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="product_type" value="mock" id="type_mock"
                            <?= (($_POST['product_type'] ?? 'mock') === 'mock') ? 'checked' : '' ?>
                            onchange="document.getElementById('sets_row').classList.remove('d-none')">
                        <label class="form-check-label" for="type_mock">Mock Exam</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="product_type" value="practice" id="type_practice"
                            <?= (($_POST['product_type'] ?? '') === 'practice') ? 'checked' : '' ?>
                            onchange="document.getElementById('sets_row').classList.add('d-none')">
                        <label class="form-check-label" for="type_practice">Practice</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="product_type" value="past_paper" id="type_past_paper"
                            <?= (($_POST['product_type'] ?? '') === 'past_paper') ? 'checked' : '' ?>
                            onchange="document.getElementById('sets_row').classList.add('d-none')">
                        <label class="form-check-label" for="type_past_paper">Past Paper</label>
                    </div>
                </div>

                <div class="mb-3" id="sets_row" <?= in_array(($_POST['product_type'] ?? 'mock'), ['practice', 'past_paper']) ? 'class="d-none"' : '' ?>>
                    <label class="form-label">Sets</label>
                    <input type="number" name="sets" class="form-control" min="1"
                           value="<?= (int)($_POST['sets'] ?? 1) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" class="form-control" min="1" required
                           value="<?= (int)($_POST['duration_minutes'] ?? 60) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Questions</label>
                    <input type="number" name="total_questions" class="form-control" min="1" required
                           value="<?= (int)($_POST['total_questions'] ?? 50) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Mark Per Question</label>
                    <input type="number" name="mark_per_question" class="form-control"
                           min="0.01" step="0.01" required
                           value="<?= htmlspecialchars($_POST['mark_per_question'] ?? '1.00', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Negative Mark %</label>
                    <input type="number" name="negative_mark_percent" class="form-control"
                           min="0" max="100" step="0.01"
                           value="<?= htmlspecialchars($_POST['negative_mark_percent'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (₦)</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01" required
                           value="<?= htmlspecialchars($_POST['price'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" name="submit" value="add_product"
                        class="btn" style="background:var(--accent);color:#000;">
                    Add Product
                </button>

            </form>
        </div>
    </div>
</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
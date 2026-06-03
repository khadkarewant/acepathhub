<?php
declare(strict_types=1);

require_once __DIR__ . "/src/db/db_conn.php";
require_once __DIR__ . "/src/db/session.php";

require_role(ROLE_ADMIN);

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}

// Fetch product
$stmt = mysqli_prepare($conn,
    "SELECT id, name, product_type, exam_body_id, description,
            duration_minutes, total_questions, total_marks, sets,
            price, price_1m, price_3m, price_6m, price_12m, status
     FROM products
     WHERE id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$res     = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: products.php");
    exit;
}

// Fetch exam bodies
$stmt = mysqli_prepare($conn, "SELECT id, name FROM exam_bodies ORDER BY name ASC");
mysqli_stmt_execute($stmt);
$res         = mysqli_stmt_get_result($stmt);
$exam_bodies = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$product_type = $product['product_type'];
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit'] ?? '') === 'update_product') {
    csrf_verify();

    $name             = trim((string)($_POST['name'] ?? ''));
    $exam_body_id     = isset($_POST['exam_body_id']) ? (int)$_POST['exam_body_id'] : 0;
    $description      = trim((string)($_POST['description'] ?? ''));
    $duration_minutes = ($product_type !== 'practice') ? (int)($_POST['duration_minutes'] ?? 0) : 0;
    $total_questions  = ($product_type !== 'practice') ? (int)($_POST['total_questions'] ?? 0) : 0;
    $total_marks      = ($product_type !== 'practice') ? (int)($_POST['total_marks'] ?? 0) : 0;
    $sets             = ($product_type === 'mock') ? (int)($_POST['sets'] ?? 0) : 1;
    $price            = ($product_type !== 'practice') ? (float)($_POST['price'] ?? 0.0) : 0.0;
    $price_1m         = ($product_type === 'practice') ? (float)($_POST['price_1m'] ?? 0.0) : null;
    $price_3m         = ($product_type === 'practice') ? (float)($_POST['price_3m'] ?? 0.0) : null;
    $price_6m         = ($product_type === 'practice') ? (float)($_POST['price_6m'] ?? 0.0) : null;
    $price_12m        = ($product_type === 'practice') ? (float)($_POST['price_12m'] ?? 0.0) : null;

    if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
        $err = 'Product name is required and must be under 120 characters.';
    } elseif ($exam_body_id <= 0) {
        $err = 'Exam body is required.';
    } elseif ($product_type !== 'practice' && $duration_minutes <= 0) {
        $err = 'Duration must be greater than 0.';
    } elseif ($product_type !== 'practice' && $total_questions <= 0) {
        $err = 'Total questions must be greater than 0.';
    } elseif ($product_type !== 'practice' && $total_marks <= 0) {
        $err = 'Total marks must be greater than 0.';
    } elseif ($product_type === 'mock' && $sets <= 0) {
        $err = 'Sets must be greater than 0 for mock exam.';
    } elseif ($product_type !== 'practice' && $price < 0) {
        $err = 'Price cannot be negative.';
    } elseif ($product_type === 'practice' && ($price_1m < 0 || $price_3m < 0 || $price_6m < 0 || $price_12m < 0)) {
        $err = 'Prices cannot be negative.';
    } else {
        $stmt = mysqli_prepare($conn,
            "UPDATE products
             SET name = ?, exam_body_id = ?, description = ?,
                 duration_minutes = ?, total_questions = ?, total_marks = ?, sets = ?,
                 price = ?, price_1m = ?, price_3m = ?, price_6m = ?, price_12m = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt, "siiiiiiddddi",
            $name, $exam_body_id, $description,
            $duration_minutes, $total_questions, $total_marks, $sets,
            $price, $price_1m, $price_3m, $price_6m, $price_12m,
            $product_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: product-details.php?product_id=" . $product_id . "&ok=updated");
        exit;
    }

    // Repopulate on error
    $product = array_merge($product, [
        'name'             => $name,
        'exam_body_id'     => $exam_body_id,
        'description'      => $description,
        'duration_minutes' => $duration_minutes,
        'total_questions'  => $total_questions,
        'total_marks'      => $total_marks,
        'sets'             => $sets,
        'price'            => $price,
        'price_1m'         => $price_1m,
        'price_3m'         => $price_3m,
        'price_6m'         => $price_6m,
        'price_12m'        => $price_12m,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Product</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container-fluid p-3">
    <div class="row">
        <div class="col-md-6">

            <div class="qs-list-header mb-3">
                <div class="qs-list-title">Update Product</div>
                <a href="product-details.php?product_id=<?= $product_id ?>" class="btn-qs-sm">← Back</a>
            </div>

            <?php if ($err !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_input() ?>

                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" maxlength="120" required
                           value="<?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description <small class="text-muted">(optional)</small></label>
                    <input type="text" name="description" class="form-control" maxlength="255"
                           value="<?= htmlspecialchars((string)($product['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Exam Body</label>
                    <select name="exam_body_id" class="form-control" required>
                        <option value="" disabled>Select Exam Body</option>
                        <?php foreach ($exam_bodies as $eb): ?>
                            <option value="<?= $eb['id'] ?>"
                                <?= ((int)$product['exam_body_id'] === (int)$eb['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($eb['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <?php
                    $type_labels = ['mock' => 'Mock Exam', 'practice' => 'Practice', 'past_paper' => 'Past Paper'];
                    ?>
                    <input type="text" class="form-control"
                        value="<?= htmlspecialchars($type_labels[$product_type] ?? $product_type, ENT_QUOTES, 'UTF-8') ?>"
                        disabled>
                </div>

                <?php if ($product_type === 'mock'): ?>
                <div class="mb-3">
                    <label class="form-label">Sets</label>
                    <input type="number" name="sets" class="form-control" min="1"
                           value="<?= (int)$product['sets'] ?>">
                </div>
                <?php endif; ?>

                <?php if ($product_type !== 'practice'): ?>
                <div class="mb-3">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" class="form-control" min="1"
                           value="<?= (int)$product['duration_minutes'] ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Questions</label>
                    <input type="number" name="total_questions" class="form-control" min="1"
                           value="<?= (int)$product['total_questions'] ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Marks</label>
                    <input type="number" name="total_marks" class="form-control" min="1"
                           value="<?= (int)$product['total_marks'] ?>">
                </div>
                <?php endif; ?>

                <?php if ($product_type !== 'practice'): ?>
                <div class="mb-3">
                    <label class="form-label">Price (₦)</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <label class="form-label">Price — 1 Month (₦)</label>
                    <input type="number" name="price_1m" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars((string)($product['price_1m'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Price — 3 Months (₦)</label>
                    <input type="number" name="price_3m" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars((string)($product['price_3m'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Price — 6 Months (₦)</label>
                    <input type="number" name="price_6m" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars((string)($product['price_6m'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Price — 12 Months (₦)</label>
                    <input type="number" name="price_12m" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars((string)($product['price_12m'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php endif; ?>

                <button type="submit" name="submit" value="update_product"
                        class="btn-qs-gold">
                    Update Product
                </button>

            </form>
        </div>
    </div>
</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
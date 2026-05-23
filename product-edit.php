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
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.product_type, p.exam_body_id, p.description,
           p.duration_minutes, p.total_questions, p.total_marks, p.sets, p.price, p.status
    FROM products p
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: products.php");
    exit;
}

// Fetch exam bodies
$stmt = $conn->prepare("SELECT id, name FROM exam_bodies ORDER BY name ASC");
$stmt->execute();
$exam_bodies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit'] ?? '') === 'update_product') {
    csrf_verify();

    $name             = trim((string)($_POST['name'] ?? ''));
    $exam_body_id     = isset($_POST['exam_body_id']) ? (int)$_POST['exam_body_id'] : 0;
    $description      = trim((string)($_POST['description'] ?? ''));
    $duration_minutes = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 0;
    $total_questions  = isset($_POST['total_questions']) ? (int)$_POST['total_questions'] : 0;
    $total_marks      = isset($_POST['total_marks']) ? (int)$_POST['total_marks'] : 0;
    $product_type = $product['product_type']; 
    $sets             = ($product_type === 'mock') ? (int)($_POST['sets'] ?? 0) : 1;
    $price            = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;

    if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
        $err = 'Product name is required and must be under 120 characters.';
    } elseif ($exam_body_id <= 0) {
        $err = 'Exam body is required.';
    } elseif ($duration_minutes <= 0) {
        $err = 'Duration must be greater than 0.';
    } elseif ($total_questions <= 0) {
        $err = 'Total questions must be greater than 0.';
    } elseif ($total_marks <= 0) {
        $err = 'Total marks must be greater than 0.';
    } elseif ($product_type === 'mock' && $sets <= 0) {
        $err = 'Sets must be greater than 0 for mock exam.';
    } elseif ($price < 0) {
        $err = 'Price cannot be negative.';
    } else {
        $stmt = $conn->prepare("
            UPDATE products
            SET name = ?, exam_body_id = ?, description = ?,
                duration_minutes = ?, total_questions = ?, total_marks = ?, sets = ?, price = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "siiiiiidi",
            $name, $exam_body_id, $description,
            $duration_minutes, $total_questions, $total_marks, $sets, $price,
            $product_id
        );
        $stmt->execute();
        $stmt->close();

        header("Location: product-details.php?product_id=" . $product_id . "&ok=updated");
        exit;
    }

    // Repopulate $product with POST values on error
    $product = array_merge($product, [
        'name'             => $name,
        'exam_body_id'     => $exam_body_id,
        'description'      => $description,
        'duration_minutes' => $duration_minutes,
        'total_questions'  => $total_questions,
        'total_marks'      => $total_marks,
        'sets'             => $sets,
        'price'            => $price,
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

            <div class="mb-3">
                <a href="product-details.php?product_id=<?= $product_id ?>" class="btn btn-sm btn-secondary">
                    &larr; Back to Product
                </a>
            </div>

            <h4 class="mb-3">Update Product</h4>

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
                        value="<?= htmlspecialchars($type_labels[$product['product_type']] ?? $product['product_type'], ENT_QUOTES, 'UTF-8') ?>"
                        disabled>
                    <input type="hidden" name="product_type" value="<?= htmlspecialchars($product['product_type'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3" id="sets_row" <?= $product['product_type'] !== 'mock' ? 'class="d-none"' : '' ?>>
                    <label class="form-label">Sets</label>
                    <input type="number" name="sets" class="form-control" min="1"
                           value="<?= (int)$product['sets'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" class="form-control" min="1" required
                           value="<?= (int)$product['duration_minutes'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Questions</label>
                    <input type="number" name="total_questions" class="form-control" min="1" required
                           value="<?= (int)$product['total_questions'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Marks</label>
                    <input type="number" name="total_marks" class="form-control" min="1" required
                           value="<?= (int)$product['total_marks'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (₦)</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01" required
                           value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" name="submit" value="update_product"
                        class="btn" style="background:var(--accent);color:#000;">
                    Update Product
                </button>

            </form>
        </div>
    </div>
</div>

<?php include("inc/footer.php"); ?>
</body>
</html>
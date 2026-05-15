<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php";

// strict access (adjust roles if needed)
if (!isset($type) || !in_array($type, ['admin'], true)) {
    header("Location: home.php");
    exit;
}

$message = ''; // For error/success messages

if (isset($_POST['submit']) && $_POST['submit'] === "add_product") {
    csrf_verify();

    // Sanitize / cast
    $course_id      = (int)($_POST['course_id'] ?? 0);
    $product_name   = trim((string)($_POST['product_name'] ?? ''));
    $description    = trim((string)($_POST['description'] ?? ''));
    $sets           = (int)($_POST['sets'] ?? 0);
    $level_1        = (int)($_POST['level_1'] ?? 0);
    $level_2        = (int)($_POST['level_2'] ?? 0);
    $price          = (int)($_POST['price'] ?? 0);
    $exam_duration  = (int)($_POST['exam_duration'] ?? 0);
    $total_question = (int)($_POST['total_question'] ?? 0);
    $tag            = trim((string)($_POST['tag'] ?? ''));
    $mark           = (int)($_POST['mark'] ?? 0);

    // Basic validation (minimal)
    if ($course_id <= 0 || $sets <= 0 || $price < 0 || $exam_duration <= 0 || $total_question <= 0) {
        $message = '<div class="alert alert-danger">❌ Invalid input.</div>';
    } elseif ($product_name === '' || mb_strlen($product_name, 'UTF-8') > 50) {
        $message = '<div class="alert alert-danger">❌ Invalid product name.</div>';
    } elseif ($tag === '' || mb_strlen($tag, 'UTF-8') > 50) {
        $message = '<div class="alert alert-danger">❌ Invalid tag.</div>';
    } elseif ($level_1 < 0 || $level_2 < 0 || ($level_1 + $level_2) !== $total_question) {
        $message = '<div class="alert alert-danger">❌ Level questions must sum to total questions.</div>';
    } else {
        // Fetch is_practice safely
        $is_practice = 0;
        $stmt = $conn->prepare("SELECT is_practice FROM `courses` WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $message = '<div class="alert alert-danger">❌ Service temporarily unavailable.</div>';
        } else {
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $stmt->bind_result($course_is_practice);
            $found = $stmt->fetch();
            $stmt->close();

            if (!$found) {
                $message = '<div class="alert alert-danger">❌ Invalid course selected.</div>';
            } else {
                $is_practice = ((int)$course_is_practice === 1) ? 1 : 0;

                // Insert product (prepared)
                $stmt = $conn->prepare("
                    INSERT INTO `products`
                    (`course_id`, `name`, `description`, `sets`, `price`, `exam_duration`, `total_question`,
                     `created_by`, `created_on`, `created_at`, `level_1`, `level_2`, `is_practice`, `tag`, `mark`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$stmt) {
                    $message = '<div class="alert alert-danger">❌ Service temporarily unavailable.</div>';
                } else {
                    $now_date = date("Y-m-d");
                    $now_time = date("H:i:s");
                    $created_by = (int)($_SESSION['id'] ?? 0);

                    // FIXED bind_param types:
                    // course_id(i), name(s), desc(s), sets(i), price(i), duration(i), total(i),
                    // created_by(i), date(s), time(s), level_1(i), level_2(i), is_practice(i), tag(s), mark(i)
                    $stmt->bind_param(
                        "issiiiiissiiisi",
                        $course_id, $product_name, $description, $sets, $price, $exam_duration, $total_question,
                        $created_by, $now_date, $now_time, $level_1, $level_2, $is_practice, $tag, $mark
                    );

                    if ($stmt->execute()) {
                        $stmt->close();
                        header("Location: products.php?msg=" . urlencode("Product created successfully."));
                        exit;
                    } else {
                        // Don't leak SQL errors on live
                        // error_log("Add product failed: " . $stmt->error);
                        $stmt->close();
                        $message = '<div class="alert alert-danger">❌ Failed to create product.</div>';
                    }
                }
            }
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
<?php include("src/inc/links.php"); ?>
<style>
.container { max-width: 700px; margin: 20px auto; }
label { font-weight:bold; margin-top:10px; }
.alert { padding:10px; margin-bottom:15px; border-radius:5px; }
.alert-success { background:#d4edda; color:#155724; }
.alert-danger { background:#f8d7da; color:#721c24; }
</style>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container">
    <h4>Add New Product</h4>
    <?php if($message) echo $message; ?>

    <form action="add-product.php" method="POST">
        <?= csrf_input(); ?>

        <label>Course</label>
        <select name="course_id" class="form-control" required>
            <option value="" selected disabled>SELECT COURSE</option>
            <?php
            $get_course = mysqli_query($conn, "SELECT id, name FROM `courses` ORDER BY name ASC");
            if ($get_course && mysqli_num_rows($get_course) > 0) {
                while ($row = mysqli_fetch_assoc($get_course)) {
                    echo '<option value="'.(int)$row['id'].'">'.
                        htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8').
                        ' (ID: '.(int)$row['id'].')</option>';
                }
            }
            ?>
        </select>

        <label>Product Name:</label>
        <input type="text" name="product_name" placeholder="Enter Product Name" class="form-control" required maxlength="50"/>

        <label>No. Of Sets:</label>
        <input type="number" name="sets" class="form-control" required/>

        <div class="row">
            <div class="col-6">
                <label>Level 1 Questions</label>
                <input type="number" class="form-control" name="level_1" required>
            </div>
            <div class="col-6">
                <label>Level 2 Questions</label>
                <input type="number" class="form-control" name="level_2" required>
            </div>
        </div>

        <label>Description:</label>
        <input type="text" placeholder="Enter description" name="description" class="form-control" required/>

        <label>Price:</label>
        <input type="number" name="price" class="form-control" required/>

        <label>Exam Duration (minutes):</label>
        <input type="number" name="exam_duration" class="form-control" required/>

        <label>Total Questions:</label>
        <input type="number" name="total_question" class="form-control" required/>

        <label>Tag:</label>
        <input type="text" name="tag" class="form-control" placeholder="Enter tag" required maxlength="50"/>

        <label>Mark:</label>
        <input type="number" name="mark" class="form-control" placeholder="Enter mark" required/>

        <br>
        <button type="submit" name="submit" value="add_product" class="btn" style="background:var(--primary);color:white;">
            Add Product
        </button>
    </form>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
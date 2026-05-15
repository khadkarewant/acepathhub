<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";
require_once "src/security/csrf.php"; // ✅ CSRF without public_bootstrap

// ✅ AUTHZ: require login + admin
if (!isset($user_id)) {
    header("Location: login.php");
    exit;
}

// ✅ Restrict access (adjust roles if needed)
if (!isset($type) || !in_array($type, ['admin'], true)) {
    header("Location: home.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $course_name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $syllabus    = trim($_POST['syllabus'] ?? '');
    $is_practice_raw = $_POST['is_practice'] ?? null;

    // ✅ Validation
    if ($course_name === '' || mb_strlen($course_name) < 3 || mb_strlen($course_name) > 120) {
        $errors[] = "Course name must be 3–120 characters.";
    }
    if ($description === '' || mb_strlen($description) < 5 || mb_strlen($description) > 255) {
        $errors[] = "Description must be 5–255 characters.";
    }
    if ($syllabus !== '' && mb_strlen($syllabus) > 5000) {
        $errors[] = "Syllabus is too long (max 5000 chars).";
    }

    if (!in_array($is_practice_raw, ['0', '1'], true)) {
        $errors[] = "Please select a valid course type.";
    } else {
        $is_practice = (int)$is_practice_raw;
    }

    if (empty($errors)) {
        $created_date = date("Y-m-d");
        $created_time = date("H:i:s");

        $stmt = $conn->prepare("
            INSERT INTO courses (name, description, syllabus, created_by, created_on, created_at, is_practice)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            http_response_code(500);
            $errors[] = "Server error. Please try again.";
        } else {
            $stmt->bind_param(
                "sssissi",
                $course_name,
                $description,
                $syllabus,
                $user_id,
                $created_date,
                $created_time,
                $is_practice
            );

            if ($stmt->execute()) {
                header("Location: courses.php?created=1");
                exit;
            } else {
                $errors[] = "Failed to create course. Please try again.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Courses</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 p-2 m-1">
            <h2>Add New Course</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?= csrf_input(); ?>

                <label>Course Name:</label>
                <input type="text" name="course_name" class="form-control" required
                       placeholder="Enter Course Name"
                       value="<?= htmlspecialchars($_POST['course_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <label>Description:</label>
                <input type="text" name="description" class="form-control" required
                       placeholder="Enter Description"
                       value="<?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <label>Syllabus:</label>
                <textarea name="syllabus" class="form-control"
                          placeholder="Enter Syllabus"><?= htmlspecialchars($_POST['syllabus'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

                <label>Course Type:</label>
                <select name="is_practice" class="form-control" required>
                    <option value="" disabled <?= (!isset($_POST['is_practice']) || $_POST['is_practice']==='') ? 'selected' : ''; ?>>Select One</option>
                    <option value="0" <?= (($_POST['is_practice'] ?? '') === '0') ? 'selected' : ''; ?>>Mock Exam</option>
                    <option value="1" <?= (($_POST['is_practice'] ?? '') === '1') ? 'selected' : ''; ?>>Practice Course</option>
                </select>
                <br>

                <button type="submit" class="btn text-light" style="background:var(--primary);">
                    Add Course
                </button>
            </form>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
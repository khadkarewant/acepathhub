<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// Redirect students
if (!in_array($type, ['admin', 'super_admin', 'data_entry'])) {
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 table-responsive">
            <h3><strong>All Courses</strong></h3>
            
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Course Name</th>
                        <?php if ($type == "admin") { echo '<th>Status</th>'; } ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Only fetch courses with is_practice = 1
                    $get_courses = mysqli_query($conn, "SELECT * FROM `courses` ORDER BY id ASC");
                    if (mysqli_num_rows($get_courses) > 0) {
                        $sn = 1;
                        while ($row = mysqli_fetch_assoc($get_courses)) {
                            echo '<tr>';
                            echo '<td>' . $sn . '</td>';
                            echo '<td>' . htmlspecialchars($row['name']) . '</td>';

                            // Status column only for admin
                            if ($type == "admin") {
                                echo '<td>' . $row['status'] . '</td>';
                            }

                            // Action buttons
                            echo '<td>';
                            ?>
                            <?php if (($row['status'] === "draft") && ($type === "super_admin" || $type === "admin")): ?>
                            <form method="POST" action="course-status-change.php" style="display:inline;">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="course_id" value="<?= (int)$row['id']; ?>">
                                <input type="hidden" name="status" value="live">
                                <button type="submit" class="bg-warning">Make Live</button>
                            </form>
                            <?php elseif (($row['status'] === "live") && ($type === "super_admin" || $type === "admin")): ?>
                            <form method="POST" action="course-status-change.php" style="display:inline;">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="course_id" value="<?= (int)$row['id']; ?>">
                                <input type="hidden" name="status" value="draft">
                                <button type="submit" class="bg-success">Make Offline</button>
                            </form>
                            <?php endif; ?>

                            <?php

                            // Details button (always shown)
                            echo '<button class="bg-info" onclick="window.location.href=\'course-details.php?course_id=' . $row['id'] . '\'">Details</button>';
                            
                            // Manage Subsets button: only if course is practice
                            if (isset($row['is_practice']) && $row['is_practice'] == 1 && $type == "admin") {
                                echo ' <button class="bg-primary" onclick="window.location.href=\'subsets-manage.php?course_id=' . $row['id'] . '\'">Manage</button>';
                            }

                            echo '</td>';

                            echo '</tr>';
                            $sn++;
                        }
                    } else {
                        // Adjust colspan dynamically
                        $colspan = ($type == "admin") ? 4 : 3;
                        echo '<tr><td colspan="' . $colspan . '">No Courses Available</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <?php include("inc/links.php"); ?>
    
</head>
<body>
    <?php
       include("inc/header.php");
    ?>
 
    <div class="container-fluid">

        <?php if(has_role(ROLE_ADMIN)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <h4 style="color:var(--accent);">Today's Stats:</h4>
                <div class="info_card">
                    <?php
                        $get_data = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE registered_on = CURDATE()");
                        $row = mysqli_fetch_assoc($get_data);
                        echo '<h1>' . $row['cnt'] . '</h1><div>New Users</div>';
                    ?>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <h4 style="color:var(--accent);">ACE PATH HUB Stats:</h4>
                <div class="info_card">
                    <?php
                        $get_data = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users");
                        $row = mysqli_fetch_assoc($get_data);
                        echo '<h1>' . $row['cnt'] . '</h1><div>Total Users</div>';
                    ?>
                </div>
                <div class="info_card"><h1>—</h1><div>Total Topics</div></div>
                <div class="info_card"><h1>—</h1><div>Total MCQs</div></div>
                <div class="info_card"><h1>—</h1><div>Total Questions</div></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(has_role(ROLE_STUDENT)): ?>
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary same-btn"
                            onclick="window.location.href='my-products.php'">Take Exam</button>
                    <button class="btn btn-sm btn-outline-primary same-btn"
                            onclick="window.location.href='course.php'">Full Course</button>
                    <button class="btn btn-sm btn-outline-primary same-btn"
                            onclick="window.location.href='downloads.php'">Downloads</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(has_role(ROLE_DATA_ENTRY)): ?>
        <div class="row">
            <div class="col-12">
                <p>Welcome DATA ENTRY</p>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <?php
        include("inc/footer.php");
    ?>
</body>
</html>
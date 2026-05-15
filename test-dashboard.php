<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

// ===================== CHECK IF USER IS ALLOWED =====================
// Assuming $user_id is already set from session
$allowed_check = mysqli_query($conn, "
    SELECT 1 
    FROM allowed_users 
    WHERE user_id = '$user_id' 
    LIMIT 1
");

if(mysqli_num_rows($allowed_check) === 0){
    // User is not allowed, redirect
    header("Location: home.php");
    exit();
}

    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <?php
        include("src/inc/links.php");
    ?>
    <meta property="og:image" content="https://quizmania.org/src/img/lsw.png">
    <meta name="twitter:image" content="https://quizmania.org/src/img/lsw.png">
    <style>
        .info_card{
            display:inline-block;
            margin:10px; 
            padding:10px;
            border-radius:5px;
            border:1px solid var(--primary);
            text-align:center;
            color:var(--primary);
        }

        .info_card:hover{
            color:white;
            background:var(--primary);
            box-shadow:5px 5px 10px grey;
            cursor:pointer;
        }
    </style>
    
</head>
<body>
    <?php
       include("src/inc/header.php");
    ?>
    <!-- ================= QUICK NAVIGATION ================= -->
    <div class="row mt-5">
        <div class="col-md-12 text-center">
            <h4 class="section-title">Quick Test Navigation</h4>
            <hr style="width:200px;margin:10px auto;border:3px solid var(--primary)">
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">

                <a href="test-dashboard.php" class="btn btn-primary">Dashboard</a>
                <a href="test-download.php" class="btn btn-secondary">Download</a>
                <a href="test-exam-revision.php" class="btn btn-success">Exam Revision</a>
                <a href="test-learn.php" class="btn btn-info text-white">Learn</a>
                <a href="test-profile.php" class="btn btn-secondary">Profile</a>
                <a href="admin-mcq-reports.php" class="btn btn-secondary">MCQ Reports</a>
                <a href="test-result-page.php" class="btn btn-primary">Result Page</a>

            </div>
        </div>
    </div>
    
    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>
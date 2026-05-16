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
        <div class="row">
        
        <div class="row">
            <?php if(has_role(ROLE_ADMIN)): ?>
            <div class="col-md-8">
                <h4 style="color:var(--primary);">Today's Stats:</h4>
                <div class="info_card">
                    <?php
                        $get_data = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE DATE(created_at) = CURDATE()");
                        $row = mysqli_fetch_assoc($get_data);
                        echo '<h1>' . $row['cnt'] . '</h1><div>New Users</div>';
                    ?>
                </div>
            </div>
            <div class="col-md-8">
                <h4 style="color:var(--primary);">ACE PATH HUB Stats:</h4>
                <div class="info_card">
                    <?php
                        $get_data = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users");
                        $row = mysqli_fetch_assoc($get_data);
                        echo '<h1>' . $row['cnt'] . '</h1><div>Total Users</div>';
                    ?>
                </div>
                <div class="info_card">
                    <h1>—</h1><div>Total Topics</div>
                </div>
                <div class="info_card">
                    <h1>—</h1><div>Total MCQs</div>
                </div>
                <div class="info_card">
                    <h1>—</h1><div>Total Questions</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if(has_role(ROLE_STUDENT)){ ?>
                <div class="col-md-12 mt-3">
                    <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                
                        <!-- Purchased Mocks -->
                        <button class="btn btn-sm btn-outline-primary same-btn"
                                onclick="window.location.href='my-products.php'">
                            Take Exam
                        </button>
                
                        <!-- Full Course -->
                        <button class="btn btn-sm btn-outline-primary same-btn"
                                onclick="window.location.href='course.php'">
                            Full Course
                        </button>
                        
                        <!-- Downloads -->
                        <button class="btn btn-sm btn-outline-primary same-btn"
                                onclick="window.location.href='downloads.php'">
                            Downloads
                        </button>
                
                    </div>
                </div>

            <?php } ?>

            <?php if(has_role(ROLE_DATA_ENTRY)) {
                ?> 
                <div>
                    Welcome DATA ENTRY
                </div>
            <?php } ?>
        </div>
    </div>

    <?php
        include("inc/footer.php");
    ?>
</body>
</html>
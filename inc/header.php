<?php
$user_id = $_SESSION['user_id'] ?? null;
?>

<div class="container-fluid pt-2 pb-2" style="background:var(--primary);">
    <div class="row">
        <div class="col-2">
            <div class="side_panel_icon m-1" id="side_panel_icon">&equiv;</div>
            <div class="side_panel_cancel m-1">&cross;</div>
        </div>
        <div class="col-3 header-logo">
            <a href="<?= isset($_SESSION['user_id']) ? 'home.php' : 'index.php' ?>">
                <img src="assets/img/logo.png" style="width:50px;" />
            </a>
        </div>
        <div class="col-7" style="text-align:right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="links">
                    <a href="notification.php" title="Notification">
                        <img src="https://cdn-icons-png.flaticon.com/512/1827/1827392.png" style="width:30px;" />
                        <?php
                            $stmt = $conn->prepare("SELECT notif_id FROM notifications WHERE user_id = ? AND is_read = 0 LIMIT 1");
                            $stmt->bind_param('i', $user_id);
                            $stmt->execute();
                            if($stmt->get_result()->num_rows > 0) echo '<div class="new"></div>';
                            $stmt->close();
                        ?>
                    </a>
                </div>
                <div class="links mt-2 profile_menu" style="z-index:1">
                    <img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" alt="" style="width:30px;">
                    <div class="submenu profile_submenu">
                        <div class="sublinks">
                            <a href="profile.php"><img src="assets/img/my_profile.png" alt=""> My Profile</a>
                        </div>
                        <div class="sublinks">
                            <a href="logout.php"><img src="assets/img/login_out.png" alt=""> Logout</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="links">
                    <a href="login.php"><img src="assets/img/login_out.png" alt=""> Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2" id="side_panel" style="z-index:1">
            <div class="side_menu_links" id="side_menu_links">

                <!-- Dashboard -->
                <div class="side-links">
                    <a href="<?= isset($_SESSION['user_id']) ? 'home.php' : 'index.php' ?>">
                        <img src="assets/img/dashboard.png" alt=""> Dashboard
                    </a>
                </div>

                <!-- ADMIN MENU -->
                <?php if(has_role(ROLE_ADMIN)): ?>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <img src="assets/img/user.png" alt=""> Users &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="users.php"><img src="assets/img/user.png" alt=""> User List</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="add-user.php"><img src="assets/img/add_user.png" alt=""> Add User</a>
                            </div>
                        </div>
                    </div>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <img src="assets/img/products.png" alt=""> Products &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="products.php"><img src="assets/img/products.png" alt=""> Product List</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="add-product.php"><img src="assets/img/add.png" alt=""> Add Product</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="assign-product.php"><img src="assets/img/add.png" alt=""> Assign Product</a>
                            </div>
                        </div>
                    </div>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <img src="assets/img/data.png" alt=""> MCQ Pool &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="draft-mcqs.php"><img src="assets/img/draft.png" alt=""> Drafted MCQs</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="updatable-mcqs.php"><img src="assets/img/updatable.png" alt=""> Updatable MCQs</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="unverified-mcqs.php"><img src="assets/img/unverified_data.png" alt=""> Unverified MCQs</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="admin-mcq-reports.php"><img src="assets/img/report.png" alt=""> Reports</a>
                            </div>
                        </div>
                    </div>

                    <div class="side-links">
                        <a href="user-stats.php"><img src="assets/img/chart.png" alt=""> Exam Stats</a>
                    </div>

                    <div class="side-links">
                        <a href="purchase-stats.php"><img src="assets/img/chart.png" alt=""> Purchase Stats</a>
                    </div>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <img src="assets/img/settings.png" alt=""> Settings &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="block-list.php"><img src="assets/img/settings.png" alt=""> Block List</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="admin-chat-reports.php"><img src="assets/img/settings.png" alt=""> Chat Reports</a>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <!-- DATA ENTRY MENU -->
                <?php if(has_role(ROLE_DATA_ENTRY)): ?>

                    <div class="side-links">
                        <a href="add-mcq.php"><img src="assets/img/add.png" alt=""> Add MCQ</a>
                    </div>
                    <div class="side-links">
                        <a href="unverified-mcqs.php"><img src="assets/img/unverified_data.png" alt=""> Unverified MCQs</a>
                    </div>

                <?php endif; ?>

                <!-- STUDENT MENU -->
                <?php if(has_role(ROLE_STUDENT)): ?>

                    <div class="side-links">
                        <a href="products.php"><img src="assets/img/products.png" alt=""> Products</a>
                    </div>
                    <div class="side-links">
                        <a href="my-products.php"><img src="assets/img/my_products.png" alt=""> My Products</a>
                    </div>
                    <div class="side-links">
                        <a href="course.php"><img src="assets/img/course.png" alt=""> Full Course</a>
                    </div>
                    <div class="side-links">
                        <a href="downloads.php"><img src="assets/img/products.png" alt=""> Downloads</a>
                    </div>
                    <div class="side-links">
                        <a href="leaderboard.php"><img src="assets/img/chart.png" alt=""> Leaderboard</a>
                    </div>
                    <div class="side-links">
                        <a href="user-stats.php"><img src="assets/img/chart.png" alt=""> My Stats</a>
                    </div>

                <?php endif; ?>

                <!-- COMMON — logged in -->
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="side-links">
                        <a href="profile.php"><img src="assets/img/my_profile.png" alt=""> My Profile</a>
                    </div>
                    <div class="side-links">
                        <a href="logout.php"><img src="assets/img/login_out.png" alt=""> Logout</a>
                    </div>
                <?php else: ?>
                    <div class="side-links">
                        <a href="login.php"><img src="assets/img/login_out.png" alt=""> Login</a>
                    </div>
                    <div class="side-links">
                        <a href="signup.php"><img src="assets/img/my_profile.png" alt=""> Register</a>
                    </div>
                    <div class="side-links">
                        <a href="contact.php"><img src="assets/img/course.png" alt=""> Contact Us</a>
                    </div>
                    <div class="side-links">
                        <a href="about.php"><img src="assets/img/course.png" alt=""> About Us</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        <div class="col-md-10" id="body_panel_area">

<script>
    document.querySelectorAll(".menu-toggle").forEach(function(btn){
        btn.addEventListener("click", function(e){
            e.preventDefault();
            const submenu = this.nextElementSibling;
            if(submenu) submenu.classList.toggle("open");
        });
    });
</script>
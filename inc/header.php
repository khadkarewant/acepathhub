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
                            <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                        </div>
                        <div class="sublinks">
                            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="links">
                    <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
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
                        <i class="fa-solid fa-gauge"></i> Dashboard
                    </a>
                </div>

                <!-- ADMIN MENU -->
                <?php if(has_role(ROLE_ADMIN)): ?>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <i class="fa-solid fa-users"></i> Users &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="users.php"><i class="fa-solid fa-users"></i> User List</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="add-user.php"><i class="fa-solid fa-user-plus"></i> Add User</a>
                            </div>
                        </div>
                    </div>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <i class="fa-solid fa-box"></i> Products &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="products.php"><i class="fa-solid fa-box"></i> Product List</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="add-product.php"><i class="fa-solid fa-plus"></i> Add Product</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="assign-product.php"><i class="fa-solid fa-hand-holding-box"></i> Assign Product</a>
                            </div>
                        </div>
                    </div>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <i class="fa-solid fa-layer-group"></i> MCQ Pool &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="draft-mcqs.php"><i class="fa-solid fa-file-pen"></i> Drafted MCQs</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="updatable-mcqs.php"><i class="fa-solid fa-rotate"></i> Updatable MCQs</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="unverified-mcqs.php"><i class="fa-solid fa-circle-exclamation"></i> Unverified MCQs</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="admin-mcq-reports.php"><i class="fa-solid fa-flag"></i> Reports</a>
                            </div>
                        </div>
                    </div>

                    <div class="side-links">
                        <a href="user-stats.php"><i class="fa-solid fa-chart-bar"></i> Exam Stats</a>
                    </div>

                    <div class="side-links">
                        <a href="purchase-stats.php"><i class="fa-solid fa-chart-line"></i> Purchase Stats</a>
                    </div>

                    <div class="side-links">
                        <a href="javascript:void(0)" class="menu-toggle">
                            <i class="fa-solid fa-gear"></i> Settings &triangledown;
                        </a>
                        <div class="side-submenu">
                            <div class="side-sublinks">
                                <a href="block-list.php"><i class="fa-solid fa-ban"></i> Block List</a>
                            </div>
                            <div class="side-sublinks">
                                <a href="admin-chat-reports.php"><i class="fa-solid fa-comments"></i> Chat Reports</a>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <!-- DATA ENTRY MENU -->
                <?php if(has_role(ROLE_DATA_ENTRY)): ?>

                    <div class="side-links">
                        <a href="add-mcq.php"><i class="fa-solid fa-plus"></i> Add MCQ</a>
                    </div>
                    <div class="side-links">
                        <a href="unverified-mcqs.php"><i class="fa-solid fa-circle-exclamation"></i> Unverified MCQs</a>
                    </div>

                <?php endif; ?>

                <!-- STUDENT MENU -->
                <?php if(has_role(ROLE_STUDENT)): ?>

                    <div class="side-links">
                        <a href="products.php"><i class="fa-solid fa-book"></i> Products</a>
                    </div>
                    <div class="side-links">
                        <a href="my-products.php"><i class="fa-solid fa-star"></i> My Products</a>
                    </div>
                    <div class="side-links">
                        <a href="course.php"><i class="fa-solid fa-graduation-cap"></i> Full Course</a>
                    </div>
                    <div class="side-links">
                        <a href="downloads.php"><i class="fa-solid fa-download"></i> Downloads</a>
                    </div>
                    <div class="side-links">
                        <a href="leaderboard.php"><i class="fa-solid fa-trophy"></i> Leaderboard</a>
                    </div>
                    <div class="side-links">
                        <a href="user-stats.php"><i class="fa-solid fa-chart-simple"></i> My Stats</a>
                    </div>

                <?php endif; ?>

                <!-- COMMON — logged in -->
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="side-links">
                        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                    </div>
                    <div class="side-links">
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                <?php else: ?>
                    <div class="side-links">
                        <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                    </div>
                    <div class="side-links">
                        <a href="signup.php"><i class="fa-solid fa-user-pen"></i> Register</a>
                    </div>
                    <div class="side-links">
                        <a href="contact.php"><i class="fa-solid fa-phone"></i> Contact Us</a>
                    </div>
                    <div class="side-links">
                        <a href="about.php"><i class="fa-solid fa-circle-info"></i> About Us</a>
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

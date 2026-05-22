<?php $user_id = $_SESSION['id'] ?? null; ?>

<!-- Fixed Navbar -->
<nav id="navbar">
    <div id="navbar-left">
        <button id="side_panel_icon" aria-label="Toggle sidebar">&#9776;</button>
        <a href="<?= isset($_SESSION['id']) ? 'home.php' : 'index.php' ?>" id="navbar-brand">AcePath Hub</a>
    </div>
    <div id="navbar-right">
        <?php if (isset($_SESSION['id'])): ?>
            <a href="notification.php" id="notif-link" title="Notifications">
                <i class="fa-solid fa-bell"></i>
                <?php
                    $stmt = mysqli_prepare($conn, "SELECT id FROM notification WHERE user_id = ? AND is_read = 0 LIMIT 1");
                    mysqli_stmt_bind_param($stmt, 'i', $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if (mysqli_num_rows($result) > 0) echo '<span class="notif-dot"></span>';
                    mysqli_stmt_close($stmt);
                ?>
            </a>
            <div class="profile_menu" id="profile-menu">
                <i class="fa-solid fa-circle-user" style="font-size:1.6rem; cursor:pointer;"></i>
                <div class="profile_submenu">
                    <div class="sublinks">
                        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                    </div>
                    <div class="sublinks">
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="navbar-login-link">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </a>
        <?php endif; ?>
    </div>
</nav>

<!-- Mobile overlay -->
<div id="sidebar-overlay"></div>

<!-- Fixed Sidebar -->
<aside id="side_panel">
    <div id="side_menu_links">

        <!-- Dashboard -->
        <div class="side-links">
            <a href="<?= isset($_SESSION['id']) ? 'home.php' : 'index.php' ?>">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
        </div>

        <!-- ADMIN MENU -->
        <?php if (has_role(ROLE_ADMIN)): ?>

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
                    <i class="fa-solid fa-building-columns"></i> Exam Bodies &triangledown;
                </a>
                <div class="side-submenu">
                    <div class="side-sublinks">
                        <a href="exam-body-list.php"><i class="fa-solid fa-list"></i> Exam Body List</a>
                    </div>
                    <div class="side-sublinks">
                        <a href="exam-body-add.php"><i class="fa-solid fa-plus"></i> Add Exam Body</a>
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
                        <a href="draft-mcqs.php"><i class="fa-solid fa-circle-check"></i> Verified MCQs</a>
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
        <?php if (has_role(ROLE_DATA_ENTRY)): ?>

            <div class="side-links">
                <a href="exam-body-list.php"><i class="fa-solid fa-plus"></i> Add MCQ</a>
            </div>
            <div class="side-links">
                <a href="unverified-mcqs.php"><i class="fa-solid fa-circle-exclamation"></i> Unverified MCQs</a>
            </div>

        <?php endif; ?>

        <!-- STUDENT MENU -->
        <?php if (has_role(ROLE_STUDENT)): ?>

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
        <?php if (isset($_SESSION['id'])): ?>
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
</aside>

<!-- Main content -->
<div id="main-content">

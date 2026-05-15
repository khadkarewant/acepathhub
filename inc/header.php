<?php
$view_user = $view_user ?? "false";
$view_course = $view_course ?? "false";
$view_product = $view_product ?? "false";
$view_mcq = $view_mcq ?? "false";
$view_sales_bill = $view_sales_bill ?? "false";
?>
<?php
$user_id = $_SESSION['id'] ?? null;
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-FZ442VW9KV"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-FZ442VW9KV');
</script>

<style>
/* Submenu stays inside sidebar, stacked below parent */
.side-submenu {
    display: none;
    background: #f5f5f5;
    border-left: 2px solid #ccc;
    padding-left: 10px;
}

.side-submenu.open {
    display: block;
}

.side-sublinks a {
    display: block;
    padding: 5px 10px;
    white-space: nowrap;
}

#side_panel {
    overflow: visible;
}
/* DESKTOP PROFILE HOVER */
@media (min-width: 501px) {
    .profile_menu {
        position: relative; /* REQUIRED */
    }

    .profile_menu:hover .profile_submenu {
        display: block;
    }
}
.profile_submenu {
    transition: opacity 0.15s ease;
}

</style>

<div class="container-fluid pt-2 pb-2 " style="background:var(--primary);">
    <div class="row">
        <div class="col-2">
            <div class="side_panel_icon m-1" id="side_panel_icon">
                &equiv;
            </div>

            <div class="side_panel_cancel m-1">
                &cross;
            </div> 

        </div>
        <div class="col-3 header-logo">

            <?php
                if(isset($_SESSION['id'])){
                    echo' 
                        <a href="home.php">
                            <img src="src/img/lsw.png"  style="width:50px;" />
                        </a>
                    ';
                }
                else{
                    echo'
                        <a href="index.php">
                            <img src="src/img/lsw.png"  style="width:50px;" />
                        </a>
                    ';
                }
            ?>
        </div>
        <div class="col-7" style="text-align:right">
            <?php
                if(isset($_SESSION['id'])){
                    echo' 
                       
                        <div class="links">
                            <a href="notification.php" title="Notification">
                                <img src="https://cdn-icons-png.flaticon.com/512/1827/1827392.png"  style="width:30px;" />
                                ';
                                    $unread_notification = mysqli_query($conn, "SELECT * FROM `notification` WHERE `user_id` = '".$user_id."' AND `status` = 'unread'");
                                    if(mysqli_num_rows($unread_notification)>0){
                                        echo'
                                            <div class="new"></div> 
                                        ';
                                    };
                                echo'
                            </a>
                        </div> 
                        
                        <div class="links mt-2 profile_menu" style="z-index:1">
                            <img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" alt="" style="width:30px;">
                            <div class="submenu profile_submenu">
                            ';
                                
                            echo'
                                <div class="sublinks">
                                    <a href="profile.php"><img src="src/img/my_profile.png" alt=""> My Profile</a>
                                </div>
                                <div class="sublinks">
                                    <a href="logout.php"><img src="src/img/login_out.png" alt=""> Logout</a>
                                </div>
                            </div>
                        </div>
                    ';
                }else{
                    echo'

                         <div class="links">
                            <a href="login.php"><img src="src/img/login_out.png" alt=""> Login</a>
                        </div> 
                    ';
                }
            ?>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2" id="side_panel" style="z-index:1">
            <div class="side_menu_links" id="side_menu_links">
                <div class="side-links">
                    <?php
                        if(isset($_SESSION['id'])){
                            echo' 
                                <a href="home.php"><img src="src/img/dashboard.png" alt=""> Dashboard</a>
                            ';
                        }
                        else{
                            echo'
                                <a href="index.php"><img src="src/img/dashboard.png" alt=""> Dashboard</a>
                            ';
                        }
                    ?>
                </div>

                <?php
                    if($view_user == "true"){

                        if($type == "admin"){
                            $get_user_count = mysqli_query($conn, "SELECT `user_id` FROM `users`  ");
                        }else{
                            $get_user_count = mysqli_query($conn, "SELECT `user_id` FROM `users` WHERE `type` = 'student'  ");
                        }


                        $user_count = mysqli_num_rows($get_user_count);
                        echo '<div class="side-links">
                            <a href="javascript:void(0)" class="menu-toggle">
                                <img src="src/img/user.png" alt=""> Users &triangledown;
                            </a>
                            <div class="side-submenu">
                                <div class="side-sublinks">
                                    <a href="users.php"><img src="src/img/user.png" alt="">User List ('.$user_count.')</a>
                                </div>';
                                if($create_user == "true"){
                                    echo '<div class="side-sublinks">
                                            <a href="add-user.php"><img src="src/img/add_user.png" alt="">Add User</a>
                                          </div>';
                                }
                    echo '  </div>
                          </div>';
                    }

                    if($type == "admin"){

                        echo '<div class="side-links">
                            <a href="javascript:void(0)" class="menu-toggle">
                                <img src="src/img/user.png" alt=""> Agents &triangledown;
                            </a>
                            <div class="side-submenu">
                                <div class="side-sublinks">
                                    <a href="add-credit.php"><img src="src/img/add.png" alt=""> Add Credit</a>
                                </div>
                                <div class="side-sublinks">
                                    <a href="agent-txn.php"><img src="src/img/course.png" alt=""> Agent TXN</a>
                                </div>
                                <div class="side-sublinks">
                                    <a href="agent-stat.php"><img src="src/img/course.png" alt=""> Agent Stat</a>
                                </div>
                            </div>
                          </div>';
                    }

                    if($view_course == "true"){

                        $get_course_count = mysqli_query($conn, "SELECT `id` FROM `courses` ");

                        $courses_count = mysqli_num_rows($get_course_count);
                        echo'
                            <div class="side-links">
                                <a href="javascript:void(0)" class="menu-toggle">
                                    <img src="src/img/course.png" alt=""> Courses &triangledown;
                                </a>
                                <div class="side-submenu">
                                    <div class="side-sublinks">
                                        <a href="courses.php"><img src="src/img/course.png" alt=""> Courses List (' . $courses_count . ')</a>
                                    </div>
                                    <?php if($create_course == "true") { ?>
                                        <div class="side-sublinks">
                                            <a href="add-course.php"><img src="src/img/add_course.png" alt=""> Add Course</a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        ';
                    }
                    if($view_product == "true"){
                        if($type == "student"){
                            $get_mock_products_count = mysqli_query($conn, "SELECT `id` FROM `products` WHERE `status` = 'live' AND `is_practice` = 0 AND `name` NOT LIKE '%demo%' ");
                            
                            $get_practice_courses_count = mysqli_query($conn, "SELECT `id` FROM `courses` WHERE `status` = 'live' AND `is_practice`= 1 ");
                        }else{
                            $get_mock_products_count = mysqli_query($conn, "SELECT `id` FROM `products` WHERE `is_practice` = 0" );
                            
                            $get_practice_courses_count = mysqli_query($conn, "SELECT `id` FROM `courses` WHERE `is_practice` = 1");
                        }

                        $mock_products_count = mysqli_num_rows($get_mock_products_count);
                        
                        $practice_courses_count = mysqli_num_rows($get_practice_courses_count);
                        
                        echo'
                            <div class="side-links">
                                <a href="javascript:void(0)" class="menu-toggle"><img src="src/img/products.png" alt=""> MOCK TEST &triangledown;</a>
                                <div class="side-submenu">
                                    <div class="side-sublinks">
                                        <a href="products.php"><img src="src/img/products.png" alt=""> Products ('.$mock_products_count.')</a>
                                    </div>';
                                    if($create_product == "true"){
                                        echo'
                                            <div class="side-sublinks">
                                                <a href="add-product.php"><img src="src/img/add.png" alt=""> Add Product</a>
                                            </div>
                                        ';
                                    }
                                    if($type == "student"){
                                        echo'
                                            <div class="side-sublinks">
                                                <a href="my-products.php"><img src="src/img/my_products.png" alt=""> My Products</a>
                                            </div>
                                        ';
                                    }

                                    echo'
                                </div>
                            </div>
                            
                            <div class="side-links">
                                <a href="course.php"><img src="src/img/products.png" alt=""> FULL COURSE ('.$practice_courses_count.')</a>
                            </div>
                            <div class="side-links">
                                <a href="downloads.php"><img src="src/img/products.png" alt=""> Downloads</a>
                            </div>
                            ';
                    }

                    if($type == "admin"  || $type == "teacher"){
                        echo'
                            <div class="side-links">
                                <a href="javascript:void(0)" class="menu-toggle"><img src="src/img/data.png" alt=""> Verified Pool &triangledown;</a>
                                <div class="side-submenu">
                                    <div class="side-sublinks">
                                        <a href="draft-mcqs.php"><img src="src/img/draft.png" alt=""> Drafted Mcqs</a>
                                    </div>
                                    <div class="side-sublinks">
                                        <a href="updatable-mcqs.php"><img src="src/img/updatable.png" alt=""> Updatable Mcqs</a>
                                    </div>
                                     <div class="side-sublinks">
                                        <a href="admin-mcq-reports.php"><img src="src/img/report.png" alt=""> Reports</a>
                                    </div>
                                </div>
                            </div>

                            <div class="side-links">
                                <a href="javascript:void(0)" class="menu-toggle"><img src="src/img/unverified_data.png" alt=""> Unverified Pool &triangledown;</a>
                                <div class="side-submenu">
                                    <div class="side-sublinks">
                                        <a href="unverified-mcqs.php"><img src="src/img/unverified_data.png" alt=""> Unverified Mcqs</a>
                                    </div>
                                </div>
                            </div>
                        ';
                    }

                    
                    if($view_mcq == "true" && $type == "data_entry"){
                        echo'
                            <div class="side-links">
                                <a href="unverified-mcqs.php"><img src="src/img/unverified_data.png" alt=""> Unverified MCQs</a>
                            </div>
                        ';
                    }

                    if($type == "admin" || $type == "teacher"){
                        echo'
                            <div class="side-links">
                                <a href="user-stats.php"><img src="src/img/chart.png" alt=""> Exam Stats</a>
                            </div>
                            
                        ';
                    }
                    
                    if($type == "agent"){
                        echo'
                            <div class="side-links">
                                <a href="a-assign-product.php"><img src="src/img/add_course.png" alt=""> Assign Product</a>
                            </div>
                        
                            <div class="side-links">
                                <a onclick="window.open(\'https://wa.me/9779700186061?text='.rawurlencode('I want to load credit on my account. My username is: '.$username).'\', \'_blank\');"><img src="src/img/add.png" alt=""> Load Credit</a>
                            </div>

                            <div class="side-links">
                                <a href="agent-txn.php"><img src="src/img/course.png" alt=""> My TXN</a>
                            </div>
                    
                        ';
                    }


                    if($view_sales_bill == "true"){

                        echo'
                            <div class="side-links">
                                <a href="javascript:void(0)" class="menu-toggle"><img src="src/img/chart.png" alt=""> Account Stats &triangledown;</a>
                                <div class="side-submenu">
                                    ';
                                    if($view_product == "true"){
                                        echo'
                                            <div class="side-sublinks">
                                                <a href="purchase-stats.php"><img src="src/img/chart.png" alt=""> Product Statics</a>
                                            </div>
                                        ';
                                    }
                                    if($add_sales_bill == "true"){
                                        echo'
                                            <div class="side-sublinks">
                                                <a href="sales-bill.php"><img src="src/img/sales.png" alt=""> Product Sales</a>
                                            </div>
                                        ';
                                    }
                                    echo'
                                </div>
                            </div>
                        ';
                    }

                    if($type == "admin"){
                    ?>
                        <div class="side-links">
                            <a href="javascript:void(0)" class="menu-toggle"><img src="src/img/settings.png" alt=""> Settings &triangledown;</a>
                            <div class="side-submenu">
                                <div class="side-sublinks">
                                    <a href="block-list.php"><img src="src/img/settings.png" alt=""> Block List</a>
                                    <!-- <a href="admin-allowed-user.php"><img src="src/img/settings.png" alt=""> Access</a> -->
                                    <a href="admin-chat-reports.php"><img src="src/img/settings.png" alt=""> Chat reports</a>
                                </div>
                            </div>
                        </div>
                    <?php
                    }

                    if($type == "student"){
                        echo'
                            <div class="side-links">
                                <a href="user-stats.php"><img src="src/img/chart.png" alt=""> My Stats</a>
                            </div>

                        ';
                    }
                    
                    if(isset($_SESSION['id'])){

                        echo'
                            <div class="side-links">
                                <a href="profile.php"><img src="src/img/my_profile.png" alt=""> My Profile</a>
                            </div>
    
                            <div class="side-links">
                                <a href="logout.php"><img src="src/img/login_out.png" alt=""> Logout</a>
                            </div>
                        ';
                    }else{
                        echo'

                            <div class="side-links">
                                <a href="login.php"><img src="src/img/login_out.png" alt=""> Login</a>
                            </div>
                            <div class="side-links">
                                <a href="signup.php"><img src="src/img/my_profile.png" alt=""> Register</a>
                            </div>
                            <div class="side-links">
                                <a href="contact.php"><img src="src/img/course.png" alt=""> Contact Us</a>
                            </div>
                            <div class="side-links">
                                <a href="about.php"><img src="src/img/course.png" alt=""> About Us</a>
                            </div>
                        ';
                    }
                ?>
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
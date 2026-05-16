<?php
require_once("src/db/db_conn.php");
require_once("src/config/public_bootstrap.php");
require_once("src/config/roles.php");

// Basic session-based signup throttling
if (!isset($_SESSION['signup_attempts'])) {
    $_SESSION['signup_attempts'] = [];
}

$window = 15 * 60; // 15 minutes
$max_attempts = 8;

// Remove old attempts
$_SESSION['signup_attempts'] = array_filter(
    $_SESSION['signup_attempts'],
    function($t) use ($window) {
        return $t > time() - $window;
    }
);

// Initialize variables
$errors = [];
$first_name = $middle_name = $last_name = $username = $email = $phone = $referral_by = "";

// Get referral from URL if provided
if(isset($_GET['referral_by'])){
    $referral_by = $_GET['referral_by'];
}

// Handle form submission
if(isset($_POST['submit']) && $_POST['submit'] === "student_registration"){
    csrf_verify();

    if (count($_SESSION['signup_attempts']) >= $max_attempts) {
        $errors['general'] = "Too many registration attempts. Please try again later.";
    } 
    else{

        $_SESSION['signup_attempts'][] = time();

        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $username = strtolower(trim($_POST['username']));
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $referral_by = trim($_POST['referral_by']);
        if($referral_by !== ""){
            $stmt_ref = $conn->prepare("SELECT `user_id` FROM `users` WHERE `referral_code` = ? LIMIT 1");
            $stmt_ref->bind_param("s", $referral_by);
            $stmt_ref->execute();
            $stmt_ref->store_result();
            if($stmt_ref->num_rows === 0){
                $errors['referral'] = "Invalid referral code.";
            }
            $stmt_ref->close();
        }

        // Validate password match
        if(strlen($first_name) < 2 || strlen($first_name) > 50){
            $errors['first_name'] = "First name must be 2–50 characters.";
        }
        if(strlen($last_name) < 2 || strlen($last_name) > 50){
            $errors['last_name'] = "Last name must be 2–50 characters.";
        }
        if(!preg_match('/^[a-z0-9_]{3,30}$/', $username)){
            $errors['username'] = "Username must be 3–30 characters. Letters, numbers, underscores only.";
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors['email'] = "Invalid email address.";
        }
        if(!preg_match('/^[0-9]{7,15}$/', $phone)){
            $errors['phone'] = "Phone must be 7–15 digits.";
        }
        if(strlen($password) < 8 || strlen($password) > 72){
            $errors['password'] = "Password must be 8–72 characters.";
        } elseif($password !== $confirm_password){
            $errors['password'] = "Passwords do not match.";
        }

        // Check if username, email, or phone already exists
        $stmt = $conn->prepare("SELECT `username`, `email`, `phone` FROM `users` WHERE `username` = ? OR `email` = ? OR `phone` = ?");
        $stmt->bind_param("sss", $username, $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()){
            if($row['username'] === $username) $errors['username'] = "Username already taken.";
            if($row['email'] === $email) $errors['email'] = "Email already taken.";
            if($row['phone'] === $phone) $errors['phone'] = "Phone number already taken.";
        }
        $stmt->close();

        if(empty($errors)){
            // Generate random referral code
            $referral_code = bin2hex(random_bytes(4));

            // Hash password
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO `users`(`username`,`first_name`,`middle_name`,`last_name`,`email`,`phone`,`password`,`type`,`status`,`registered_on`,`registered_at`,`referral_code`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $date = date("Y-m-d");
            $time = date("H:i:s");
            $role = ROLE_STUDENT;
            $status = 'active';
            $stmt->bind_param("sssssssissss", $username, $first_name, $middle_name, $last_name, $email, $phone, $password_hashed, $role, $status, $date, $time, $referral_code);
            if($stmt->execute()){
                $student_id = $stmt->insert_id;

                // Add notifications
                $stmt_notif = $conn->prepare("INSERT INTO `notification`(`user_id`,`notification`,`date`,`time`) VALUES (?, ?, ?, ?)");
                $message1 = "Thanks for choosing us. We hope you achieve your goal here.";
                $message2 = "You have one free trial set.";
                $stmt_notif->bind_param("isss", $student_id, $message1, $date, $time);
                $stmt_notif->execute();
                $stmt_notif->bind_param("isss", $student_id, $message2, $date, $time);
                $stmt_notif->execute();
                $stmt_notif->close();

                // Add free demo set
                // $stmt_demo = $conn->prepare("INSERT INTO `purchased_products`(`user_id`,`product_id`,`amount`,`remaining_sets`,`txn_no`,`txn_mode`,`refrence_no`,`purchased_on`,`purchased_at`,`mobile`,`status`,`created_by`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                // $product_id = 11;
                // $amount = 0;
                // $remaining_sets = 1;
                // $txn_no = $student_id;
                // $txn_mode = 'free';
                // $refrence_no = "FREE-DEMO.$student_id";
                // $mobile = '0000000000';
                // $status = 'active';
                // $created_by = 1;
                // $stmt_demo->bind_param("iiiisssssssi",$student_id,$product_id,$amount,$remaining_sets,$txn_no,$txn_mode,$refrence_no,$date,$time,$mobile,$status,$created_by);
                // $stmt_demo->execute();
                // $stmt_demo->close();

                header("Location: login.php?msg=" . urlencode("Account created successfully. Please login.")); 
                exit;
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

    <?php include("inc/links.php"); ?>

    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include("inc/header.php"); ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto p-2 mt-2">
                <h4 style="color:var(--primary);">User Registration</h4>
                <?php if(isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php endif; ?>
                <form class="p-3 shadow border" action="" method="POST">
                    <?= csrf_input(); ?>
                    <div class="row">
                        <div class="col-md-4">
                            <label>First Name:</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" class="form-control" required/>
                        </div>
                        <div class="col-md-4">
                            <label>Middle Name:</label>
                            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>" class="form-control"/>
                        </div>
                        <div class="col-md-4">
                            <label>Last Name:</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" class="form-control" required/>
                        </div>

                        <div class="col-md-12">
                            <label>Username:</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" class="form-control" required/>
                            <?php if(isset($errors['username'])): ?><i class="text-danger"><?= htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8') ?></i><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control" required/>
                            <?php if(isset($errors['email'])): ?><i class="text-danger"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></i><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label>Phone:</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="form-control" required/>
                            <?php if(isset($errors['phone'])): ?><i class="text-danger"><?= htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8') ?></i><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label>Password:</label>
                            <div class="input-group mb-2">
                                <input type="password" name="password" class="form-control" id="password" required/>
                                <span class="input-group-text toggle-password" style="cursor:pointer; background-color:#fff; border-left:0;">👁️</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label>Confirm Password:</label>
                            <div class="input-group mb-2">
                                <input type="password" name="confirm_password" class="form-control" id="confirm_password" required/>
                                <span class="input-group-text toggle-password" style="cursor:pointer; background-color:#fff; border-left:0;">👁️</span>
                            </div>
                        </div>
                        
                            <?php if(isset($errors['password'])): ?><i class="text-danger"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></i><?php endif; ?>
                        </div>

                        <div class="col-md-12">
                            <label>Referral (Optional):</label>
                            <input type="text" name="referral_by" value="<?php echo htmlspecialchars($referral_by); ?>" class="form-control"><br>
                            <?php if(isset($errors['referral'])): ?>
                                <i class="text-danger"><?= htmlspecialchars($errors['referral'], ENT_QUOTES, 'UTF-8') ?></i>
                            <?php endif; ?>

                            <input type="checkbox" required id="tnc" />
                            <label for="tnc">I accept <a href="t&c.php" class="text-decoration-none">Terms & Conditions</a></label><br>

                            <button type="submit" name="submit" value="student_registration" class="btn text-light mt-2" style="background:var(--primary);">Register</button><br><br>

                            <i>Already have an account? Sign in <a href="login.php">here</a></i>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    document.querySelectorAll('.toggle-password').forEach(function(toggle) {
        toggle.addEventListener('click', function () {
            const input = this.previousElementSibling; // the input before the span
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    });
</script>


    <?php include("inc/footer.php"); ?>
</body>
</html>

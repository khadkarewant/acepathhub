<?php

include("src/db/db_conn.php");
require_once("src/security/public_bootstrap.php");


$error_msg = "";


// Handle messages from URL (e.g., Session expired)
$msg = "";
if (isset($_GET['msg']) && !empty($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']); // sanitize
}

// Redirect if already logged in
if (isset($_SESSION['id']) && $_SESSION['id'] != null) {
    header("Location: home.php");
    exit;
}

if (isset($_POST["sign_in"])) {
    csrf_verify();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
    $error_msg = "Invalid credential.";
} else {

    // ===== 1. LOGIN THROTTLING CHECK =====

    $lock_window = 15 * 60;
    $max_attempts = 5;
    $time_limit = time() - $lock_window;

    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) 
        FROM login_attempts 
        WHERE username = ? 
        AND attempt_time > ?
    ");

    mysqli_stmt_bind_param($stmt, "si", $username, $time_limit);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $attempt_count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($attempt_count >= $max_attempts) {
        $error_msg = "Too many failed attempts. Try again later.";
    } else {

        // ===== 2. FETCH USER =====

        $stmt = mysqli_prepare($conn, "
            SELECT user_id, password, first_login_on, first_login_at, type
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $error_msg = "Login temporarily unavailable.";
        } else {

            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $user_id, $db_pass, $first_login_on, $first_login_at, $type);
            $has_user = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($has_user && password_verify($password, $db_pass)) {

                // SUCCESS
                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);

                $token = bin2hex(random_bytes(32));

                $_SESSION['id'] = (int)$user_id;
                $_SESSION['token'] = $token;
                $_SESSION['type'] = $type;

                $token_time = time();
                $is_session = 'true';
                $uid = (int)$user_id;

                $stmt = mysqli_prepare($conn, "
                    UPDATE users
                    SET session_token = ?, session_token_time = ?, is_session = ?
                    WHERE user_id = ?
                ");

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sisi", $token, $token_time, $is_session, $uid);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                // CLEAR FAILED ATTEMPTS
                $stmt = mysqli_prepare($conn, "
                    DELETE FROM login_attempts WHERE username = ?
                ");
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                header("Location: home.php");
                exit;

            } else {

                // FAILED LOGIN — RECORD ATTEMPT
                $now = time();

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO login_attempts (username, attempt_time)
                    VALUES (?, ?)
                ");

                mysqli_stmt_bind_param($stmt, "si", $username, $now);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $error_msg = "Invalid credential.";
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign-in</title>
    <?php include("src/inc/links.php"); ?>
</head>
<body>
    <?php include("src/inc/header.php"); ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4 p-2">
                <h2 style="color:var(--primary)">User Sign-in</h2>
                <form class="p-4 shadow border" action="login.php" method="POST">
                    <?= csrf_input(); ?>
        
                    <label>Username:</label>
                    <input type="text" name="username" placeholder="Enter username" class="form-control" required/>
                    <br>
                    
                    <label>Password:</label>
                        <div class="input-group mb-2">
                            <input type="password" placeholder="Enter password" name="password" class="form-control" id="password" required/>
                            <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                                👁️
                            </span>
                        </div>
                    <br>
                    <?php if (!empty($error_msg)): ?>
                        <div class="text-danger mb-2"><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                        
                    <button type="submit" name="sign_in" value="Sign-in" class="btn text-light" style="background:var(--primary)">Login</button>
                    <br><br>
                    <i>Don't have an account? sign-up <a href="signup.php">here</a></i>
                </form>
            </div>
        </div>
    </div>

    <?php include("src/inc/footer.php"); ?>

    <!-- Show popup message if exists -->
    <?php if(!empty($msg)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            alert("<?php echo $msg; ?>");
        });
    </script>
    <?php endif; ?>
    
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        // toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // optionally toggle the icon
        this.textContent = type === 'password' ? '👁️' : '🙈';
    });
</script>


</body>
</html>

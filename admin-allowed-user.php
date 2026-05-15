<?php
http_response_code(410);
exit('Gone');
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// ===================== ADMIN CHECK =====================
// Only admin can access
if(!isset($type) || $type !== "admin"){
    header("Location: home.php");
    exit();
}

// ===================== ADD USER =====================
if(isset($_POST['add_user'])){
    $new_user_id = intval($_POST['user_id']);
    if($new_user_id > 0){
        // Check if the user is already allowed
        $check = mysqli_query($conn, "SELECT id FROM allowed_users WHERE user_id='$new_user_id'");
        if(mysqli_num_rows($check) == 0){
            mysqli_query($conn, "INSERT INTO allowed_users (user_id) VALUES ('$new_user_id')") 
                or die(mysqli_error($conn));
            // Optional: redirect with success
            header("Location: admin-allowed-user.php?msg=added");
        } else {
            // Optional: redirect with a warning instead of inserting
            header("Location: admin-allowed-user.php?msg=exists");
        }
        exit();
    }
}


// ===================== REMOVE USER =====================
if(isset($_GET['remove'])){
    $remove_id = intval($_GET['remove']);
    mysqli_query($conn, "DELETE FROM allowed_users WHERE id='$remove_id'")
        or die(mysqli_error($conn));
    header("Location: admin-allowed-user.php");
    exit();
}

// ===================== FETCH ALLOWED USERS =====================
$allowed_res = mysqli_query($conn, "
    SELECT au.id, au.user_id, u.username 
    FROM allowed_users au
    LEFT JOIN users u ON u.user_id = au.user_id
    ORDER BY au.user_id ASC
") or die(mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Allowed Users</title>
<?php include("src/inc/links.php"); ?>
<style>
.container { max-width:900px; margin:auto; padding:20px; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
table, th, td { border:1px solid #ddd; }
th, td { padding:10px; text-align:left; }
th { background:#f2f2f2; }
.btn { padding:5px 10px; text-decoration:none; border-radius:4px; }
.btn-add { background:green; color:#fff; border:none; cursor:pointer; }
.btn-delete { background:red; color:#fff; text-decoration:none; padding:5px 10px; border-radius:4px; }
input[type=number] { padding:5px; width:100px; }
</style>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container">
    <h3>Allowed Users Management</h3>
    <p>Add or remove users who are allowed to access restricted pages.</p>

<?php if(isset($_GET['msg'])){
    if($_GET['msg'] == 'added'){
        echo '<p style="color:green;">User added successfully!</p>';
    } elseif($_GET['msg'] == 'exists'){
        echo '<p style="color:red;">User already exists!</p>';
    }
} ?>
    <!-- Add User Form -->
    <form method="post" style="margin-top:20px;">
        <label>User ID: </label>
        <input type="number" name="user_id" required>
        <button type="submit" name="add_user" class="btn btn-add">Add User</button>
    </form>

    <!-- Allowed Users Table -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>User ID</th>
                <th>Username</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sn = 1;
            $has_rows = false;
            while($row = mysqli_fetch_assoc($allowed_res)):
                $has_rows = true;
            ?>
            <tr>
                <td><?php echo $sn++; ?></td>
                <td><?php echo $row['user_id']; ?></td>
                <td><?php echo htmlspecialchars($row['username'] ?? '-'); ?></td>
                <td>
                    <a href="admin-allowed-user.php?remove=<?php echo $row['id']; ?>" 
                       class="btn-delete" 
                       onclick="return confirm('Are you sure you want to remove this user?');">
                       Delete
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if(!$has_rows): ?>
            <tr>
                <td colspan="4" style="text-align:center;">No allowed users found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

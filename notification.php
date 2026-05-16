<?php
    require_once "src/db/db_conn.php";
    require_once "src/db/session.php";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    csrf_verify();
    $stmt = mysqli_prepare($conn, "UPDATE notification SET is_read = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: notification.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — AcePath Hub</title>

    <?php include("inc/links.php"); ?>

</head>

<body>
    <?php include("inc/header.php"); ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 p-2">
                <h3 style="color:var(--accent)">Notification:</h3>

                <form method="POST" style="display:inline;">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="mark_read" value="1">
                    <button type="submit" class="btn" style="background:var(--accent);color:#0a0a0f;">
                        Mark All Read
                    </button>
                </form>

            </div>
            <div class="col-md-12 rounded p-2 mb-3" style="background:rgba(22, 89, 235, 0.2)">
         
            <?php  
                $stmt = mysqli_prepare($conn, "SELECT DISTINCT date FROM notification WHERE user_id = ? ORDER BY date DESC");
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                mysqli_stmt_execute($stmt);
                $dates = mysqli_stmt_get_result($stmt);

                while ($d = mysqli_fetch_assoc($dates)) {
                    echo '<div class="btn p-1 mt-1 mb-1" style="background:var(--accent);color:#0a0a0f;">' . $d['date'] . '</div>';
                    
                    $stmt2 = mysqli_prepare($conn, "SELECT * FROM notification WHERE user_id = ? AND date = ? ORDER BY id DESC LIMIT 50");
                    mysqli_stmt_bind_param($stmt2, 'is', $user_id, $d['date']);
                    mysqli_stmt_execute($stmt2);
                    $notices = mysqli_stmt_get_result($stmt2);
                    
                    while ($n = mysqli_fetch_assoc($notices)) {
                        $class = $n['is_read'] == 0 ? 'bg-secondary text-light' : '';
                        echo '<div class="rounded p-1 ' . $class . '">' . htmlspecialchars($n['notification']) . ' (' . $n['time'] . ')</div>';
                    }
                    mysqli_stmt_close($stmt2);
                }
                mysqli_stmt_close($stmt);
            ?>  
            </div>
        </div>
    </div>
    
    <?php include("inc/footer.php"); ?>
    
</body>
</html>
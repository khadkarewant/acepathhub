<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

// ── Admin stats ───────────────────────────────────────────────────────────
if (has_role(ROLE_ADMIN)) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM users WHERE registered_on = CURDATE()");
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $new_users = (int)mysqli_fetch_assoc($res)['cnt'];
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM users");
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $total_users = (int)mysqli_fetch_assoc($res)['cnt'];
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM topics");
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $total_topics = (int)mysqli_fetch_assoc($res)['cnt'];
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM questions");
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $total_mcqs = (int)mysqli_fetch_assoc($res)['cnt'];
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);
}

// ── Data Entry stats ──────────────────────────────────────────────────────
if (has_role(ROLE_DATA_ENTRY)) {
    $stmt = mysqli_prepare($conn,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) AS verified,
            SUM(CASE WHEN verified = 0 THEN 1 ELSE 0 END) AS pending
         FROM question_sets
         WHERE created_by = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $de_stats = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container-fluid py-4">

    <?php if (has_role(ROLE_ADMIN)): ?>

        <h4 class="mb-3" style="color:var(--accent);">Today's Stats</h4>
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="info_card">
                    <h1><?= $new_users ?></h1>
                    <div>New Users</div>
                </div>
            </div>
        </div>

        <h4 class="mb-3" style="color:var(--accent);">AcePath Hub Stats</h4>
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="info_card">
                    <h1><?= $total_users ?></h1>
                    <div>Total Users</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info_card">
                    <h1><?= $total_topics ?></h1>
                    <div>Total Topics</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info_card">
                    <h1><?= $total_mcqs ?></h1>
                    <div>Total MCQs</div>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <?php if (has_role(ROLE_DATA_ENTRY)): ?>

        <h4 class="mb-3" style="color:var(--accent);">My Contributions</h4>
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="info_card">
                    <h1><?= (int)$de_stats['total'] ?></h1>
                    <div>Total Submitted</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info_card">
                    <h1><?= (int)$de_stats['verified'] ?></h1>
                    <div>Verified</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info_card">
                    <h1><?= (int)$de_stats['pending'] ?></h1>
                    <div>Pending</div>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <?php if (has_role(ROLE_STUDENT)): ?>

        <h4 style="color:var(--accent);">Welcome, <?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?>!</h4>
        <p style="color:var(--muted);">Use the menu to access your products and start practising.</p>

    <?php endif; ?>

</div>

<?php include "inc/footer.php"; ?>
</body>
</html>
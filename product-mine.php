<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";

require_role(ROLE_STUDENT);

$tab = $_GET['tab'] ?? 'practice';
if (!in_array($tab, ['mock', 'practice', 'past_paper'], true)) {
    $tab = 'practice';
}

// ── MOCK active ───────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT pp.id, pp.sets_remaining, pp.purchased_on,
            p.name, p.total_questions, p.duration_minutes, p.total_marks,
            eb.name AS exam_body
     FROM purchased_products pp
     JOIN products p ON p.id = pp.product_id
     LEFT JOIN exam_bodies eb ON eb.id = p.exam_body_id
     WHERE pp.user_id = ? AND pp.status = 'active'
       AND p.product_type = 'mock' AND pp.sets_remaining > 0
     ORDER BY pp.id DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
$mock_active = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

// ── PRACTICE active ───────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT pp.id, pp.expires_at, pp.purchased_on,
            p.id AS product_id, p.name, p.total_questions, p.duration_minutes, p.total_marks,
            eb.name AS exam_body
     FROM purchased_products pp
     JOIN products p ON p.id = pp.product_id
     LEFT JOIN exam_bodies eb ON eb.id = p.exam_body_id
     WHERE pp.user_id = ? AND pp.status = 'active'
       AND p.product_type = 'practice' AND pp.expires_at >= CURDATE()
     ORDER BY pp.expires_at ASC"
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
$practice_active = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

// ── PAST PAPER active ─────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT pp.id, pp.purchased_on,
            p.id AS product_id, p.name, p.total_questions, p.duration_minutes, p.total_marks,
            eb.name AS exam_body
     FROM purchased_products pp
     JOIN products p ON p.id = pp.product_id
     LEFT JOIN exam_bodies eb ON eb.id = p.exam_body_id
     WHERE pp.user_id = ? AND pp.status = 'active'
       AND p.product_type = 'past_paper'
     ORDER BY pp.id DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
$past_paper_active = mysqli_fetch_all($r, MYSQLI_ASSOC);
mysqli_free_result($r);
mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Products</title>
<?php include("inc/links.php"); ?>
</head>
<body>
<?php include("inc/header.php"); ?>

<div class="container py-4">
    <h4 class="mp-page-title mb-4">My Products</h4>

    <ul class="mp-tabs mb-4">
        <li>
            <a href="?tab=mock" class="mp-tab <?= $tab === 'mock' ? 'mp-tab-active' : '' ?>">
                Mock Exams
                <?php if (count($mock_active)): ?>
                    <span class="mp-tab-count"><?= count($mock_active) ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="?tab=practice" class="mp-tab <?= $tab === 'practice' ? 'mp-tab-active' : '' ?>">
                Practice
                <?php if (count($practice_active)): ?>
                    <span class="mp-tab-count"><?= count($practice_active) ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="?tab=past_paper" class="mp-tab <?= $tab === 'past_paper' ? 'mp-tab-active' : '' ?>">
                Past Papers
                <?php if (count($past_paper_active)): ?>
                    <span class="mp-tab-count"><?= count($past_paper_active) ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <?php if ($tab === 'mock'): ?>

        <?php if (empty($mock_active)): ?>
            <div class="mp-empty">
                <p>You have no mock exam purchases.</p>
                <a href="products.php" class="btn-qs-gold">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($mock_active as $row): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="mp-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="mp-card-title"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <span class="mp-badge-active">Active</span>
                        </div>
                        <?php if ($row['exam_body']): ?>
                            <div class="mp-meta"><?= htmlspecialchars($row['exam_body'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="mp-meta mb-2">
                            <?= $row['total_questions'] ?> Qs &bull;
                            <?= $row['duration_minutes'] ?> min &bull;
                            <?= $row['total_marks'] ?> marks
                        </div>
                        <div class="mp-sets-remaining">
                            <?= $row['sets_remaining'] ?> set<?= $row['sets_remaining'] != 1 ? 's' : '' ?> remaining
                        </div>
                        <div class="mp-meta mt-1">Purchased: <?= date('d M Y', strtotime($row['purchased_on'])) ?></div>
                        <div class="mt-auto pt-3">
                            <a href="exam-guidelines.php?purchased_id=<?= $row['id'] ?>"
                               class="btn-qs-gold d-block text-center">Start Exam</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'practice'): ?>

        <?php if (empty($practice_active)): ?>
            <div class="mp-empty">
                <p>You have no practice purchases.</p>
                <a href="products.php" class="btn-qs-gold">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($practice_active as $row):
                    $today    = new DateTime('today');
                    $expiry   = new DateTime($row['expires_at']);
                    $days     = (int)$today->diff($expiry)->days;
                    $exp_cls  = $days <= 7 ? 'mp-expires-warn' : 'mp-expires-ok';
                ?>
                <div class="col-md-4 col-sm-6">
                    <div class="mp-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="mp-card-title"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <span class="mp-badge-active">Active</span>
                        </div>
                        <?php if ($row['exam_body']): ?>
                            <div class="mp-meta"><?= htmlspecialchars($row['exam_body'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="mp-meta mb-2">
                            <?= $row['total_questions'] ?> Qs &bull;
                            <?= $row['duration_minutes'] ?> min &bull;
                            <?= $row['total_marks'] ?> marks
                        </div>
                        <div class="<?= $exp_cls ?>">
                            Expires: <?= date('d M Y', strtotime($row['expires_at'])) ?>
                            <?= $days <= 7 ? " &bull; {$days}d left" : '' ?>
                        </div>
                        <div class="mp-meta mt-1">Purchased: <?= date('d M Y', strtotime($row['purchased_on'])) ?></div>
                        <div class="mt-auto pt-3">
                            <a href="practice-subject.php?purchased_id=<?= $row['id'] ?>"
                               class="btn-qs-gold d-block text-center">Start Practice</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'past_paper'): ?>

        <?php if (empty($past_paper_active)): ?>
            <div class="mp-empty">
                <p>You have no past paper purchases.</p>
                <a href="products.php" class="btn-qs-gold">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($past_paper_active as $row): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="mp-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="mp-card-title"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <span class="mp-badge-active">Active</span>
                        </div>
                        <?php if ($row['exam_body']): ?>
                            <div class="mp-meta"><?= htmlspecialchars($row['exam_body'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="mp-meta mb-2">
                            <?= $row['total_questions'] ?> Qs &bull;
                            <?= $row['duration_minutes'] ?> min &bull;
                            <?= $row['total_marks'] ?> marks
                        </div>
                        <div class="mp-unlimited">Unlimited Attempts</div>
                        <div class="mp-meta mt-1">Purchased: <?= date('d M Y', strtotime($row['purchased_on'])) ?></div>
                        <div class="mt-auto pt-3">
                            <a href="exam-guidelines.php?purchased_id=<?= $row['id'] ?>"
                               class="btn-qs-gold d-block text-center">Start Exam</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?php include "inc/footer.php"; ?>
</body>
</html>

<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// Get group_id from URL
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// Get product_id from URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// ===================== AJAX RESET SUBSET =====================
if(isset($_POST['ajax_reset']) && $_POST['ajax_reset']=='1'){
    $product_id = intval($_POST['product_id']);
    $sub_set_number = intval($_POST['sub_set_number']);
    $topic_id = intval($_POST['topic_id']);

    mysqli_query($conn, "
        DELETE pa
        FROM practice_answers pa
        JOIN mcqs m ON m.id = pa.mcq_id
        WHERE pa.user_id = $user_id
          AND pa.product_id = $product_id
          AND m.sub_set_number = $sub_set_number
          AND m.topic_id = $topic_id
    ");

    echo json_encode(['status'=>'ok']);
    exit;
}

// ===================== ACCESS CHECK =====================
$check_group = mysqli_query($conn, "
    SELECT g.id AS group_id, g.group_name, p.id AS product_id, 
           p.course_id, c.name AS course_name,
           IF(pp.id IS NOT NULL, 1, 0) AS is_purchased
    FROM product_topic_groups g
    JOIN products p ON p.id = g.product_id AND p.is_practice = 1
    JOIN courses c ON c.id = p.course_id
    LEFT JOIN purchased_products pp 
        ON pp.product_id = p.id 
       AND pp.user_id='$user_id'
       AND pp.remaining_sets = 1
       AND pp.status = 'active'
    WHERE g.id='$group_id'
    LIMIT 1
");


if(mysqli_num_rows($check_group) == 0){
    die("You do not have access to this group or it's not a practice course.");
}

$group = mysqli_fetch_assoc($check_group);
$is_purchased = (int)$group['is_purchased'];

// ===================== DEMO ACCESS (FIRST GROUP ONLY) =====================
if(!$is_purchased){
    $first_group_res = mysqli_query($conn, "
        SELECT g.id 
        FROM product_topic_groups g
        WHERE g.product_id = ".$group['product_id']."
        ORDER BY g.sort_order ASC
        LIMIT 1
    ");
    $first_group_id = mysqli_fetch_assoc($first_group_res)['id'] ?? 0;

    if($group_id != $first_group_id){
        die("You do not have access to this group. Buy the full course to unlock.");
    }
}

// ===================== FETCH TOPICS UNDER GROUP =====================
$topic_ids = [];
$res = mysqli_query($conn, "SELECT topic_id FROM product_topics WHERE group_id='$group_id'");
while($r = mysqli_fetch_assoc($res)){
    $topic_ids[] = $r['topic_id'];
}

if(empty($topic_ids)){
    die("No topics found under this group.");
}

$topic_ids_str = implode(',', $topic_ids);

// ===================== FETCH SUBSETS =====================
$subsets_res = mysqli_query($conn, "
    SELECT m.topic_id,
           m.sub_set_number,
           COUNT(*) AS total_questions,
           SUM(CASE WHEN pa.id IS NOT NULL THEN 1 ELSE 0 END) AS attempted,
           SUM(CASE WHEN pa.is_correct=1 THEN 1 ELSE 0 END) AS correct
    FROM mcqs m
    LEFT JOIN practice_answers pa 
        ON pa.mcq_id = m.id 
       AND pa.user_id='$user_id'
       AND pa.product_id = {$product_id}  -- move product filter here
    WHERE m.topic_id IN ($topic_ids_str)
      AND m.verified='true'
      AND m.status='live'
    GROUP BY m.topic_id, m.sub_set_number
    ORDER BY m.topic_id ASC, m.sub_set_number ASC
");


$subsets = [];
while($row = mysqli_fetch_assoc($subsets_res)){
    $attempted = (int)$row['attempted'];
    $correct   = (int)$row['correct'];
    $incorrect = $attempted - $correct;
    $total     = (int)$row['total_questions'];

    // Calculate progress per product
    $row['progress'] = ($total > 0) ? round(($attempted / $total) * 100) : 0;
    $row['correct_percent'] = ($attempted > 0) ? round(($correct / $attempted) * 100) : 0;
    $row['error_percent']   = ($attempted > 0) ? round(($incorrect / $attempted) * 100) : 0;

    // Fetch topic name
    $tn_res = mysqli_query($conn, "SELECT name FROM topics WHERE id='{$row['topic_id']}' LIMIT 1");
    $row['topic_name'] = mysqli_fetch_assoc($tn_res)['name'] ?? 'Topic '.$row['topic_id'];

    $subsets[] = $row;
}


// ===================== FIRST TOPIC (FOR DEMO LOCKING) =====================
$first_topic_id = $subsets[0]['topic_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Practice Subsets - <?php echo htmlspecialchars($group['group_name']); ?></title>
<?php include("src/inc/links.php"); ?>

<style>
/* ===================== SUBSET CARD ===================== */
.subset-card{
    border:1px solid #e3e3e3;
    border-radius:10px;
    padding:14px;
    background:#fff;
    transition:all .2s ease;
    height:100%;
}
.subset-card:hover{
    box-shadow:0 8px 20px rgba(0,0,0,.12);
    transform:translateY(-4px);
}

.subset-title{font-size:14px;font-weight:600;text-align:center;}
.subset-meta{font-size:12px;color:#666;}

.progress{height:18px;border-radius:8px;overflow:hidden;}
.progress-bar{font-size:11px;font-weight:600;line-height:18px;text-align:center;color:#fff;}

.analytics{font-size:11px;margin-top:4px;}
.analytics .correct{color:#198754;font-weight:600;}
.analytics .wrong{color:#dc3545;font-weight:600;}

.reset-link{font-size:11px;color:#dc3545;cursor:pointer;}
.reset-link:hover{text-decoration:underline;}

.locked-link{pointer-events:none;opacity:0.5;}

.locked-subset {
    background: #f8f9fa;
    border: 1px dashed #ccc;
    opacity: 0.6;
}

.locked-subset .subset-title,
.locked-subset .subset-meta,
.locked-subset .analytics {
    color: #6c757d;
}

/* ===================== MOBILE CLEANUP ONLY ===================== */
@media(max-width:767px){
    .col-sm-6,
    .col-md-4,
    .col-lg-3,
    .col-xl-2{
        flex:0 0 100%;
        max-width:100%;
    }

    .subset-card{
        padding:12px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px; /* proportional height */
    }

    .subset-title,
    .subset-meta{
        font-size:16px;
        text-align:left;
        margin-bottom:4px;
    }

    .progress,
    .analytics{
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        margin-bottom:6px;
    }

    .progress-bar{
        font-size:12px;
        line-height:18px;
    }

    .subset-card .btn{
        width:100%;
        font-size:14px;
        padding:8px;
    }

    .analytics span{
        display:inline-block;
    }
}

</style>
</head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">


<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid mt-4">

<div class="text-center mb-3">
    <h3 style="color:var(--primary)">Practice Mode</h3>
    <p class="text-muted"><?php echo htmlspecialchars($group['group_name']); ?></p>
</div>

<div class="mb-3">
    <a href="topics.php?product_id=<?php echo $group['product_id']; ?>"
       class="btn btn-outline-primary btn-sm">← Back to Topics</a>
</div>

<div class="row g-3 mt-3">
<?php
$serial = 1;
foreach($subsets as $subset):

    $is_demo_allowed = true;
    if(!$is_purchased){
        if(!($subset['topic_id']==$first_topic_id && $subset['sub_set_number']==1)){
            $is_demo_allowed = false;
        }
    }
    $locked_class = $is_demo_allowed ? '' : 'locked-subset';

    $progress = $subset['progress'];

    if($progress==0){ $btnText="Start"; $btnClass="btn-outline-primary"; }
    elseif($progress<100){ $btnText="Continue"; $btnClass="btn-primary"; }
    else{ $btnText="Completed"; $btnClass="btn-success"; }

    if ($progress == 0) $barColor = '#adb5bd';
    elseif ($progress < 50) $barColor = '#0d6efd';
    elseif ($progress < 100) $barColor = '#fd7e14';
    else $barColor = '#198754';
?>
<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
    <div class="subset-card <?php echo $locked_class; ?>">

        <div class="subset-title">Set <?php echo $serial; ?></div>
        <div class="subset-meta"><?php echo htmlspecialchars($subset['topic_name']); ?></div>

        <div class="progress mb-2">
            <div class="progress-bar" style="width:<?php echo $progress; ?>%;background:<?php echo $barColor; ?>">
                <?php echo $progress; ?>%
            </div>
        </div>

        <div class="analytics mb-2">
            <span class="correct">✔ <?php echo $subset['correct_percent']; ?>%</span>
            <span class="wrong">✖ <?php echo $subset['error_percent']; ?>%</span>
        </div>

        <?php if($is_demo_allowed): ?>
        <a href="practice.php?group_id=<?php echo $group_id; ?>&subset=<?php echo $subset['sub_set_number']; ?>&topic_id=<?php echo $subset['topic_id']; ?>&product_id=<?php echo $product_id; ?>"
           class="btn btn-sm w-100 <?php echo $btnClass; ?>"><?php echo $btnText; ?></a>
        <?php else: ?>
        <a class="btn btn-sm w-100 <?php echo $btnClass; ?> locked-link">Locked</a>
        <?php endif; ?>

        <?php if($is_demo_allowed): ?>
        <div class="text-center mt-2">
            <span class="reset-link reset-subset-btn"
                  data-subset="<?php echo $subset['sub_set_number']; ?>"
                  data-topic="<?php echo $subset['topic_id']; ?>">Reset Subset</span>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php $serial++; endforeach; ?>
</div>
</div>

<script>
$('.reset-subset-btn').on('click', function(){
    if(!confirm('Are you sure you want to reset this subset?')) return;

    $.post('subsets.php',{
        ajax_reset:1,
        sub_set_number: $(this).data('subset'),
        topic_id: $(this).data('topic'),
        product_id: <?php echo $group['product_id']; ?>
    }, function(res){
        if(res.status==='ok'){ location.reload(); }
    },'json');
});

</script>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

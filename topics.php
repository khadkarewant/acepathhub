<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

function toNepaliNumber($number){
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    $nepali  = ['०','१','२','३','४','५','६','७','८','९'];
    return str_replace($english, $nepali, $number);
}

// ===================== AJAX RESET GROUP =====================
if(isset($_POST['ajax_reset']) && $_POST['ajax_reset']=='1'){
    $group_id_reset = intval($_POST['group_id']);
    $product_id_reset = intval($_POST['product_id']); // NEW: pass product_id from JS

    // Reset practice answers for this user, group, and product
    mysqli_query($conn, "
        DELETE pa FROM practice_answers pa
        JOIN mcqs m ON m.id = pa.mcq_id
        JOIN product_topics pt ON pt.topic_id = m.topic_id
        WHERE pa.user_id='$user_id' 
          AND pt.group_id='$group_id_reset'
          AND pa.product_id='$product_id_reset'
    ");

    echo json_encode(['status'=>'ok']);
    exit;
}

// Get product_id from URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// ===================== FETCH PRODUCT AND CHECK PURCHASE =====================
$product_info = mysqli_query($conn, "
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        c.id AS course_id,
        c.name AS course_name,
        IF(pp.id IS NOT NULL, 1, 0) AS is_purchased
    FROM products p
    JOIN courses c ON c.id = p.course_id
    LEFT JOIN purchased_products pp 
        ON pp.user_id = '$user_id'
       AND pp.product_id = p.id
       AND pp.remaining_sets > 0
       AND pp.status = 'active'
    WHERE p.id = '$product_id'
      AND p.is_practice = 1
    LIMIT 1
");


if(mysqli_num_rows($product_info) == 0){
    die("You do not have access to this product or it's not a practice product.");
}

$product = mysqli_fetch_assoc($product_info);
$is_purchased = $product['is_purchased'];

// ===================== FETCH GROUPS =====================
$groups_res = mysqli_query($conn, "
    SELECT g.id, g.group_name, g.sort_order
    FROM product_topic_groups g
    WHERE g.product_id='$product_id'
    ORDER BY g.sort_order ASC
");

$groups = [];
$first_group = true;
while($g = mysqli_fetch_assoc($groups_res)){
    // Total MCQs in this group
    $mcq_count = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM product_topics pt
        JOIN mcqs m ON m.topic_id = pt.topic_id
        WHERE pt.group_id='".$g['id']."' AND m.verified='true' AND m.status='live'
    "))['total'] ?? 0;

    // User attempted MCQs filtered by product_id
    $answered_count = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS answered
        FROM practice_answers pa
        JOIN mcqs m ON m.id = pa.mcq_id
        JOIN product_topics pt ON pt.topic_id = m.topic_id
        WHERE pa.user_id='$user_id' 
          AND pt.group_id='".$g['id']."' 
          AND pa.product_id='$product_id'
          AND m.verified='true' 
          AND m.status='live'
    "))['answered'] ?? 0;

    // Correctly answered by user filtered by product_id
    $correct_count = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS correct
        FROM practice_answers pa
        JOIN mcqs m ON m.id = pa.mcq_id
        JOIN product_topics pt ON pt.topic_id = m.topic_id
        WHERE pa.user_id='$user_id' 
          AND pt.group_id='".$g['id']."' 
          AND pa.is_correct=1 
          AND pa.product_id='$product_id'
          AND m.verified='true' 
          AND m.status='live'
    "))['correct'] ?? 0;

    $incorrect_count = $answered_count - $correct_count;

    $g['progress'] = ($mcq_count>0) ? round(($answered_count/$mcq_count)*100) : 0;
    $g['correct_percent'] = ($answered_count>0) ? round(($correct_count/$answered_count)*100) : 0;
    $g['error_percent'] = ($answered_count>0) ? round(($incorrect_count/$answered_count)*100) : 0;
    $g['status'] = ($g['progress']==100) ? 'completed' : (($g['progress']>0) ? 'in_progress' : 'not_started');

    // ===================== LOCK LOGIC =====================
    if(!$is_purchased){
        if($first_group){
            $g['locked'] = false; // first group accessible for demo
            $first_group = false;
        } else {
            $g['locked'] = true;  // all other groups locked
        }
    } else {
        $g['locked'] = false; // purchased users access all
    }

    $groups[] = $g;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practice Groups - <?php echo htmlspecialchars($product['product_name']); ?></title>
<?php include("src/inc/links.php"); ?>
<style>
.topic-card{
    position: relative;
    border:1px solid #ddd;
    border-left:6px solid var(--primary);
    padding:15px;
    border-radius:6px;
    margin-bottom:20px;
    background:#fff;
    transition:0.3s;
}

.topic-card:hover{
    box-shadow:0 6px 15px rgba(0,0,0,0.1);
    transform:translateY(-3px);
}

.progress{height:8px;border-radius:10px;}
.status{font-size:13px;font-weight:bold;}
.analytics{font-size:12px;margin-top:5px;}

/* Leaderboard button top-right */
.leaderboard-btn{
    position:absolute;
    top:10px;
    right:10px;
    padding:4px 10px;
    font-size:12px;
}

/* Only add extra padding on mobile to avoid overlap */
@media(max-width:767px){
    .topic-card{
        padding-top:45px;
    }
}

.btn-group{
    display:flex;
    gap:10px;
    margin-top:15px;
    flex-wrap:wrap;
}
.btn-group .btn{
    flex:1;
    text-align:center;
}

.locked-group{opacity:0.5;}

/* MOBILE-FIRST COLUMN */
.group-column{
    width:100%;
    margin-bottom:15px;
    box-sizing:border-box;
}

@media(min-width:768px){
    .group-column{
        width:50%;
        float:left;
        padding:0 10px;
    }
}

.row::after{
    content:"";
    display:table;
    clear:both;
}

/* Responsive: stack buttons on mobile */
@media(max-width:767px){
    .btn-group{
        flex-direction:column;
    }
}
</style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container-fluid mt-4">
    <div class="text-center">
        <h3 style="color:var(--primary)">
            <?php echo htmlspecialchars($product['course_name']); ?>
        </h3>

        <p class="text-muted">Practice the full syllabus using grouped MCQs</p>
    </div>

<h5 class="mt-4">
    Product: <span style="color:var(--primary)"><?php echo htmlspecialchars($product['product_name']); ?></span>
</h5>

<div class="mb-3">
    <a href="learn.php" class="btn btn-outline-primary btn-sm">← Back to Courses</a>
</div>

<div class="row mt-3">
    <?php if(count($groups)==0): ?>
        <div class="col-12 text-center"><p class="text-muted">No Topics found for this product.</p></div>
    <?php else: ?>
    
        <?php $sn = 1; foreach($groups as $group):
            $status_class = 'text-muted';
            $button_class = 'btn-success';
            $button_text = 'Start Practice';

            if($group['status']=='completed'){
                $status_class = 'text-success';
                $button_class = 'btn-outline-primary';
                $button_text = 'Revise';
            } elseif($group['status']=='in_progress'){
                $status_class = 'text-warning';
                $button_class = 'btn-primary';
                $button_text = 'Continue Practice';
            }

            $progress_width = $group['progress'];
            $locked_class = $group['locked'] ? 'locked-group' : '';
        ?>
        <div class="group-column">
            <div class="topic-card <?php echo $locked_class; ?>">

                <a href="leaderboard.php?group_id=<?php echo $group['id']; ?>" 
                   class="btn btn-warning btn-sm leaderboard-btn" 
                   <?php echo $group['locked'] ? 'style="pointer-events:none; opacity:0.5;"' : ''; ?>>
                   🏆 Leaderboard
                </a>

                <h6>
                    (<?php echo toNepaliNumber($sn); ?>)
                    <?php echo htmlspecialchars($group['group_name']); ?>
                </h6>

                <div class="progress mt-2 mb-2">
                    <div class="progress-bar <?php 
                        echo ($group['status']=='completed') ? 'bg-success' : (($group['status']=='in_progress') ? 'bg-warning' : 'bg-secondary'); ?>" 
                        style="width:<?php echo $progress_width; ?>%"></div>
                </div>

                <span class="status <?php echo $status_class; ?>">
                    <?php echo ($group['status']=='not_started') ? 'Not Started' : ($group['status']=='in_progress' ? $progress_width.'% Completed' : 'Completed'); ?>
                </span>

                <div class="analytics">
                    ✔ Correct: <?php echo $group['correct_percent']; ?>% &nbsp; | &nbsp;
                    ✖ Error: <?php echo $group['error_percent']; ?>%
                </div>

                <div class="btn-group">
                    <?php if($group['locked']): ?>
                        <button class="btn btn-secondary btn-sm" disabled>Locked - Buy to Unlock</button>
                    <?php else: ?>
                        <a href="subsets.php?group_id=<?php echo $group['id']; ?>&product_id=<?php echo $product_id; ?>" 
                           class="btn <?php echo $button_class; ?> btn-sm">
                            <?php echo $button_text; ?>
                        </a>

                        <button class="btn btn-danger btn-sm reset-group-btn" data-group="<?php echo $group['id']; ?>">
                            Reset Topic
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php $sn++; endforeach; ?>
    <?php endif; ?>
</div>
</div>

<script>
$('.reset-group-btn').on('click', function(){
    let group_id = $(this).data('group');
    let product_id = <?php echo $product_id; ?>; // Pass the current product_id

    if(!confirm('Are you sure you want to reset this group?')) return;

    $.post('topics.php', {
        ajax_reset: 1,
        group_id: group_id,
        product_id: product_id
    }, function(res){
        if(res.status==='ok'){
            alert('Group reset successfully!');
            location.reload();
        }
    }, 'json');
});

</script>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

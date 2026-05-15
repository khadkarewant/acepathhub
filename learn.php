<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");
require_once "src/security/csrf.php";
// ===================== AJAX RESET PRODUCT =====================
if(isset($_POST['ajax_reset']) && $_POST['ajax_reset']=='1'){
    csrf_verify();

    $product_id = intval($_POST['product_id']);

    // Get all topic IDs for this product
    $stmt = mysqli_prepare($conn, "SELECT topic_id FROM product_topics WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $topic_ids_res = mysqli_stmt_get_result($stmt);

    $topic_ids = [];
    while($row = mysqli_fetch_assoc($topic_ids_res)){
        $topic_ids[] = (int)$row['topic_id'];
    }
    mysqli_stmt_close($stmt);

    if(!empty($topic_ids)){
        $topic_ids_str = implode(',', $topic_ids);

        // Delete practice answers for this user and these topics
        mysqli_query($conn, "
            DELETE FROM practice_answers 
            WHERE user_id='$user_id' 
              AND topic_id IN ($topic_ids_str)
              AND product_id='$product_id'
        ");
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status'=>'ok']);
    exit;
}


// ===================== FETCH PRACTICE PRODUCTS =====================
$get_products = mysqli_query($conn, "
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        p.course_id,
        p.price,
        c.name AS course_name,
        IF(pp.id IS NOT NULL, 1, 0) AS is_purchased
    FROM products p
    JOIN courses c ON c.id = p.course_id
    LEFT JOIN purchased_products pp 
        ON pp.user_id = '$user_id'
       AND pp.product_id = p.id
       AND pp.remaining_sets > 0
       AND pp.status = 'active'
    WHERE p.is_practice = 1
      AND p.status = 'live'
    ORDER BY p.id ASC
");


$products = [];
while($row = mysqli_fetch_assoc($get_products)){
    $products[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practice Dashboard</title>
<?php include("src/inc/links.php"); ?>
<style>
body {
    font-family: Arial, sans-serif;
    background:#f5f5f5;
    margin:0;
    padding:0;
}

.container-fluid {
    padding:10px;
}

.section-title {
    color:var(--primary);
    margin-top:20px;
    margin-bottom:10px;
    text-align:center;
}

/* COURSE CARDS */
.course-card {
    border:1px solid #ddd;
    border-radius:6px;
    padding:15px;
    margin-bottom:15px;
    box-shadow:0 0 5px rgba(0,0,0,0.1);
    text-align:center;
}


/* BUTTONS */
.btn, .reset-product-btn {
    width:100%;
    padding:10px;
    margin-bottom:8px;
    font-size:0.95rem;
}

.chat-locked {
    opacity: 0.6;
    cursor: not-allowed;
}

/* LIST ITEMS */
.list-group-item {
    word-wrap: break-word;
    font-size:0.9rem;
    padding:10px 12px;
}

/* COLOR CODING */
.list-group-item.correct {background:#d4edda;color:#155724;}
.list-group-item.wrong {background:#f8d7da;color:#721c24;}

/* INFO CARDS */
.info_card {
    display:inline-block;
    margin:10px;
    padding:15px;
    border-radius:5px;
    border:1px solid var(--primary);
    text-align:center;
    color:var(--primary);
    min-width:160px;
}

/* RESPONSIVE COLUMNS: MOBILE-FIRST */
.product-column {
    width:100%;
    margin-bottom:15px;
    box-sizing:border-box;
}

@media(min-width:768px){
    .product-column {
        width:33.3333%;
        float:left;
        padding:0 10px;
    }
}

/* CLEARFIX FOR ROWS */
.row::after {
    content: "";
    clear: both;
    display: table;
}
.price-tag {
    display: inline-block;
    background: #ffc107; /* price yellow */
    color: #000;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 4px 8px;
    border-radius: 4px;
    margin-top: 5px;
}

.price-tag.free {
    background: #6c757d;
    color: #fff;
}

.price-tag.purchased {
    background: #0d6efd; /* blue info style */
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 4px 8px;
    border-radius: 4px;
    margin-top: 5px;
    display: inline-block;
    transition: transform 0.2s ease;
}

.price-tag.purchased:hover {
    transform: scale(1.05);
}

.product-name {
    display: flex;
    align-items: center;
    justify-content: center; /* center for your card layout */
    gap: 5px; /* space between name and tick */
}

.product-name .tick {
    color: #0d6efd; /* Bootstrap info blue */
    font-weight: bold;
    font-size: 1.1rem;
    transition: transform 0.2s ease;
}

.product-name .tick:hover {
    transform: scale(1.2);
}

/*.validity-badge {*/
/*    display: inline-block;*/
/*    font-size: 12px;*/
/*    padding: 4px 8px;*/
/*    border-radius: 6px;*/
/*    background: #fff3cd;*/
/*    color: #856404;*/
/*    font-weight: 500;*/
/*}*/

.price-box {
    display: flex;
    align-items: center;
    justify-content: center; 
    gap: 10px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.old-price {
    text-decoration: line-through;
    color: #999;
    font-size: 14px;
}

.discount-badge {
    background: #ff4d4d;
    color: #fff;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 4px;
}

.new-price {
    font-size: 18px;
    font-weight: bold;
    color: #28a745;
}


.validity-badge {
    background: #fff3cd;
    color: #856404;
}

@media(max-width:767px){
    .product-name .tick {
        font-size: 1rem;
    }
}

</style>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid mt-0 pt-2"> <!-- remove mt-4, optional padding-top -->

    <!-- PRACTICE PRODUCTS -->
    <div class="row mt-0">

        <div class="col-12 text-center mt-0 mb-2"> <!-- remove extra mt -->
            <h4 class="section-title mb-1">Loksewa Course Products</h4>
            <hr style="width:150px;margin:5px auto;border:3px solid var(--primary)">
            
            <h3 class="mt-1" style="color:var(--primary)">
                <?php echo htmlspecialchars($products[0]['course_name'] ?? ''); ?>
            </h3>
        
            <p class="text-muted mb-1">Practice the full syllabus using grouped MCQs</p>
        </div>
    </div>
</div>


        <?php
        $product_ids = array_column($products, 'product_id');
        $product_ids_str = implode(',', $product_ids);

        $stats_res = mysqli_query($conn, "
            SELECT 
                pt.product_id,
                COUNT(DISTINCT m.id) AS total_questions,
                SUM(CASE WHEN pa.id IS NOT NULL THEN 1 ELSE 0 END) AS attempted_questions,
                SUM(CASE WHEN pa.is_correct = 1 THEN 1 ELSE 0 END) AS correct_questions
            FROM product_topics pt
            JOIN mcqs m 
            ON m.topic_id = pt.topic_id AND m.status='live' AND m.verified='true'
            LEFT JOIN practice_answers pa 
            ON pa.mcq_id = m.id 
            AND pa.user_id = $user_id
            AND pa.product_id = pt.product_id
            WHERE pt.product_id IN ($product_ids_str)
            GROUP BY pt.product_id
        ");


$product_stats = [];
while($row = mysqli_fetch_assoc($stats_res)){
    $product_stats[$row['product_id']] = $row;
}
?>

<?php if(count($products) > 0): foreach($products as $product): ?>

<?php
    // Get stats from the pre-fetched $product_stats array
    $stats = $product_stats[$product['product_id']] ?? [
        'total_questions' => 0,
        'attempted_questions' => 0,
        'correct_questions' => 0
    ];

    // Accuracy calculation
    $accuracy = $stats['attempted_questions'] > 0 
                ? round(($stats['correct_questions'] / $stats['attempted_questions']) * 100) 
                : 0;
?>

<div class="product-column">
    <div class="course-card">
        <h5 class="product-name">
            <?php echo htmlspecialchars($product['product_name']); ?>
            <?php if($product['is_purchased']): ?>
                <span class="tick">✔</span>
            <?php endif; ?>
        </h5>
        <div class="validity-badge mb-2">
            ⏳ Course Access: 12 months from purchase
        </div>


        <!-- Price tag only if not purchased -->
             <?php if(!$product['is_purchased'] && $product['price'] > 0): ?>
            <?php
                $original_price = 1499; // Anchor price
                $launch_price = $product['price']; // Discounted / launch price
                $discount_percent = ($original_price > $launch_price)
                    ? round((($original_price - $launch_price) / $original_price) * 100)
                    : 0;
            ?>
            <div class="price-box" style="display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap;">
                <div class="old-price">NPR <?php echo number_format($original_price); ?></div>
                <?php if($discount_percent > 0): ?>
                    <div class="discount-badge"><?php echo $discount_percent; ?>% OFF</div>
                <?php endif; ?>
                <div class="new-price">NPR <?php echo number_format($launch_price); ?></div>
                <div class="price-tag" style="background:#17a2b8;color:#fff;font-weight:700;padding:2px 6px;border-radius:4px;">
                    Launch Offer!
                </div>
            </div>
        <?php elseif(!$product['is_purchased'] && $product['price'] == 0): ?>
            <div class="price-tag free">Free</div>
        <?php endif; ?>



        <div class="mb-2">
            Attempted: <?php echo $stats['attempted_questions']; ?> / <?php echo $stats['total_questions']; ?> questions
        </div>
        <div class="mb-2">
            Accuracy: <?php echo $accuracy; ?>%
        </div>

        <?php if(!$product['is_purchased']): ?>
            <a href="topics.php?product_id=<?php echo $product['product_id']; ?>" 
               class="btn text-light mb-2" style="background:var(--primary)">
               Start Demo
            </a>
                <!-- PURCHASE -->
            <a href="javascript:void(0);"
               class="btn btn-success mb-2"
               onclick="window.open(
                   'https://wa.me/9779700186061?text=<?php 
                       echo rawurlencode(
                           'I want to buy the product: '.$product['product_name'].
                           ' (Price: NPR '.number_format($product['price']).')'.
                           ' and My username is: '.$username
                       ); 
                   ?>',
                   '_blank'
               );">
               Upgrade Now for NPR <?php echo number_format($product['price']); ?>
            </a>

        <?php else: ?>
            <a href="topics.php?product_id=<?php echo $product['product_id']; ?>" 
               class="btn text-light mb-2" style="background:var(--primary)">
               Start Full Course
            </a>
        <?php endif; ?>

        <button class="btn btn-danger reset-product-btn" data-product="<?php echo $product['product_id']; ?>">Reset Product</button>
        <?php if($product['is_purchased']): ?>
            <a href="discussion.php?product_id=<?php echo $product['product_id']; ?>"
               class="btn btn-primary w-100 mb-2">
                💬 Open Chat
            </a>
        <?php else: ?>
            <button class="btn btn-secondary w-100 mb-2 chat-locked"
                    onclick="alert('Purchase the course to unlock chat access')">
                🔒 Chat (Locked)
            </button>
        <?php endif; ?>

    </div>
</div>

<?php endforeach; else: ?>
<div class="col-12 text-center text-muted">No practice products purchased yet.</div>
<?php endif; ?>

</div> <!-- /container-fluid -->
<div id="csrf-holder" style="display:none;"><?= csrf_input(); ?></div>

<script>
$('.reset-product-btn').on('click', function(){
    let btn = $(this);
    let product_id = btn.data('product');
    if(!confirm('Are you sure you want to reset this product?')) return;
    btn.prop('disabled',true).text('Resetting...');
    
    const csrfInput = document.querySelector('#csrf-holder input[type="hidden"]');

    let payload = { ajax_reset: 1, product_id: product_id };
    if (csrfInput) payload[csrfInput.name] = csrfInput.value;

    $.post('learn.php', payload, function(res){
        if(res.status=='ok'){ 
            alert('Product reset successfully!'); 
            location.reload(); 
        } else { 
            alert('Error resetting product'); 
            btn.prop('disabled',false).text('Reset Product'); 
        }
    }, 'json');
});
</script>

<?php include("src/inc/footer.php"); ?>
</body>
</html>

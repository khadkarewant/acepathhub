<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// Only admins or super_admin
if(!in_array($type, ['admin','super_admin'])){
    echo json_encode(['status'=>'error','error'=>'Access denied']);
    exit;
}

if(!isset($_POST['topic_id'], $_POST['sub_set_number'])){
    echo json_encode(['status'=>'error','error'=>'Invalid request']);
    exit;
}

$topic_id = intval($_POST['topic_id']);
$num_subsets = max(1,intval($_POST['sub_set_number']));

// Fetch all MCQs for this topic
$mcqs_res = mysqli_query($conn,"
    SELECT id 
    FROM mcqs 
    WHERE topic_id='$topic_id' 
    AND LOWER(TRIM(verified))='true' 
    AND status='live' 
    ORDER BY id ASC
");

$mcq_ids = [];
while($mcq = mysqli_fetch_assoc($mcqs_res)){
    $mcq_ids[] = $mcq['id'];
}

if(count($mcq_ids) == 0){
    echo json_encode(['status'=>'error','error'=>'No MCQs found']);
    exit;
}

$total_mcqs = count($mcq_ids);
$quotient = floor($total_mcqs / $num_subsets);
$remainder = $total_mcqs % $num_subsets;

$subset_number = 1;
$counter = 0;

foreach($mcq_ids as $mcq_id){
    $res = mysqli_query($conn,"UPDATE mcqs SET sub_set_number='$subset_number' WHERE id='$mcq_id'");
    if(!$res){
        echo json_encode(['status'=>'error','error'=>mysqli_error($conn)]);
        exit;
    }

    $counter++;
    $max_in_subset = $quotient + ($remainder > 0 ? 1 : 0);
    if($counter >= $max_in_subset){
        $counter = 0;
        $subset_number++;
        if($remainder > 0) $remainder--;
        if($subset_number > $num_subsets) $subset_number = $num_subsets;
    }
}

echo json_encode(['status'=>'ok']);
exit;

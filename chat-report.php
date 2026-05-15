<?php
include("src/db/db_conn.php");
include("src/db/session.php"); // optional if session_start() is already called here
include("src/db/privileges.php");

$user_id = $_SESSION['id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit();
}

// ---------------- POST DATA ----------------
$msg_id = intval($_POST['msg_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if($msg_id < 1){
    echo json_encode(['success'=>false,'error'=>'Invalid message ID']);
    exit();
}

if($reason === ''){
    echo json_encode(['success'=>false,'error'=>'Reason cannot be empty']);
    exit();
}

// ---------------- INSERT REPORT ----------------
$stmt = $conn->prepare("
    INSERT INTO chat_reports
    (message_id, reported_by, reason, created_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->bind_param("iis", $msg_id, $user_id, $reason);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>'Database error']);
}
exit();

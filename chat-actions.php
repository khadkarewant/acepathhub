<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");
require_once("src/security/csrf.php");
header('Content-Type: application/json; charset=UTF-8');

// ---------------- USER ----------------
$user_id = intval($user_id); // ✅ enforce int, no assumptions

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user || trim($user['is_blocked']) === 'true') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// ---------------- PRODUCT ----------------
$product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
if ($product_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product']);
    exit();
}

// ---------------- STUDENT ACCESS CHECK ----------------
if ($user['type'] === 'student') {
    $stmt = $conn->prepare("
    SELECT 1 FROM purchased_products
    WHERE user_id = ?
      AND product_id = ?
      AND status = 'active'
      AND remaining_sets > 0
    LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $check = $stmt->get_result();
    $stmt->close();

    if ($check->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'No access']);
        exit();
    }
}

// ---------------- DELETE MESSAGE ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    
    csrf_verify();

    $msg_id = intval($_POST['msg_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($msg_id < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid message']);
        exit();
    }

    $stmt = $conn->prepare("
    SELECT user_id
    FROM discussions
    WHERE id = ?
      AND product_id = ?
      AND is_deleted = 0
    LIMIT 1
    ");
    $stmt->bind_param("ii", $msg_id, $product_id);
    $stmt->execute();
    $msg_q = $stmt->get_result();
    $stmt->close();

    if ($msg_q->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Message not found']);
        exit();
    }

    $msg = $msg_q->fetch_assoc();

    //  Set permissions explicitly
    $can_delete_any = (isset($delete_any_chat) && $delete_any_chat === "true");
    $can_delete_own = (isset($delete_own_chat) && $delete_own_chat === "true");

    if (
        !$can_delete_any &&
        !($can_delete_own && $msg['user_id'] == $user_id)
    ) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $deleted_reason = null;
    if ($can_delete_any && $reason !== '') {
        $deleted_reason = $reason; // prepared stmt will handle escaping
    }

    $stmt = $conn->prepare("
        UPDATE discussions SET
            is_deleted = 1,
            deleted_by = ?,
            deleted_at = NOW(),
            deleted_reason = ?
        WHERE id = ?
        AND is_deleted = 0
    ");
    $stmt->bind_param("isi", $user_id, $deleted_reason, $msg_id);
    $stmt->execute();
    $stmt->close();

    
    if ($conn->affected_rows === 0) {
    echo json_encode(['error' => 'Already deleted']);
    exit();
}


    echo json_encode(['success' => true]);
    exit();
}

// ---------------- SEND MESSAGE ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {

    csrf_verify();

    $message  = trim($_POST['message']);
    $reply_to = intval($_POST['reply_to'] ?? 0);
    $reply_to = $reply_to > 0 ? $reply_to : null;

    if ($message === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Empty message']);
        exit();
    }

    if (mb_strlen($message) > 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message too long']);
        exit();
    }

    $date = date('Y-m-d');
    $time = date('H:i:s');

    
    if ($reply_to === null) {
        $stmt = $conn->prepare("
        INSERT INTO discussions
        (product_id, user_id, message, reply_to, sent_on, sent_at)
        VALUES (?, ?, ?, NULL, ?, ?)
        ");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
            exit();
        }

        $stmt->bind_param("iisss", $product_id, $user_id, $message, $date, $time);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO discussions
            (product_id, user_id, message, reply_to, sent_on, sent_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
            exit();
        }
        $stmt->bind_param("iisiss", $product_id, $user_id, $message, $reply_to, $date, $time);
    }

    echo json_encode(['success' => $stmt->execute()]);
    $stmt->close();
    exit();
}

// ---------------- FETCH MESSAGES ----------------
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {

    $stmt = $conn->prepare("
    SELECT
        d.id,
        d.message,
        d.reply_to,
        d.user_id,
        d.is_deleted,
        d.deleted_reason,
        u.username,
        r.message AS reply_message,
        r.is_deleted AS reply_deleted,
        ru.username AS reply_username

    FROM discussions d
    JOIN users u ON u.user_id = d.user_id

    LEFT JOIN discussions r ON r.id = d.reply_to
    LEFT JOIN users ru ON ru.user_id = r.user_id

    WHERE d.product_id = ?
    ORDER BY d.id ASC
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    // ✅ Set permissions explicitly
    $can_delete_any = (isset($delete_any_chat) && $delete_any_chat === "true");
    $can_delete_own = (isset($delete_own_chat) && $delete_own_chat === "true");

    $out = [];
    while ($row = $res->fetch_assoc()) {

        $out[] = [
            'id' => $row['id'],
            'username' => $row['username'],

            'is_deleted' => (bool)$row['is_deleted'],
            'message' => $row['is_deleted']
                ? 'This message was deleted'
                : $row['message'],

            'deleted_reason' => $can_delete_any ? $row['deleted_reason'] : null,

            'reply_message' => (!empty($row['reply_deleted']))
                ? 'This message was deleted'
                : $row['reply_message'],

            'reply_username' => $row['reply_username'],

            'sent_by_user' => ($row['user_id'] == $user_id),

            // ✅ Only show delete for own if student, any if admin
            'can_delete' => $can_delete_any || ($can_delete_own && $row['user_id'] == $user_id)
        ];
    }

    echo json_encode($out);
    exit();
}


http_response_code(400);
echo json_encode(['error' => 'Invalid request']);

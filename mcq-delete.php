<?php
require_once 'src/db/db_conn.php';
require_once 'src/db/session.php';
require_once 'src/util/mcq_image.php';

require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

csrf_verify();

$question_set_id = filter_input(INPUT_POST, 'question_set_id', FILTER_VALIDATE_INT);
if (!$question_set_id) {
    header('Location: home.php');
    exit;
}

// Fetch topic_id + image_path before deleting
$stmt = mysqli_prepare($conn,
    'SELECT topic_id, image_path FROM question_sets WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $question_set_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$set = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$set) {
    header('Location: home.php');
    exit;
}

$topic_id = (int)$set['topic_id'];

// Delete image file from disk first
if ($set['image_path']) {
    delete_mcq_image($set['image_path']);
}

// CASCADE handles questions deletion automatically
$stmt = mysqli_prepare($conn, 'DELETE FROM question_sets WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $question_set_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    header('Location: draft-mcq-details.php?topic_id=' . $topic_id . '&err=1');
    exit;
}

header('Location: draft-mcq-details.php?topic_id=' . $topic_id . '&ok=1');
exit;
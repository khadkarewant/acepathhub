<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

if ($type !== "admin") {
    header("Location: home.php"); exit;
}

// POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php"); exit;
}

csrf_verify();

$question_topic_id = isset($_POST['question_topic_id']) ? (int)$_POST['question_topic_id'] : 0;
$pattern_id = isset($_POST['pattern_id']) ? (int)$_POST['pattern_id'] : 0;

if ($question_topic_id <= 0 || $pattern_id <= 0) {
    header("Location: products.php"); exit;
}

// Delete
mysqli_query($conn, "DELETE FROM `question_topics` WHERE `id` = " . $question_topic_id);

// Always redirect back
header("Location: question-pattern-details.php?id=" . $pattern_id); exit;

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

header('Content-Type: application/json');

// Get POST data (no $ in keys)
$post_id = $_POST['post_id'] ?? null;
$comment_id = $_POST['comment_id'] ?? null;
$reply = $_POST['reply'] ?? null;
$user = $_SESSION["user_id"] ?? null;

// Validate input
if (!$post_id || !$comment_id || !$reply || !$user) {
    echo json_encode([
        "success" => false,
        "error" => "Missing required parameters or user not logged in"
    ]);
    exit;
}

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO reply (post_id, user_id, comment_id, reply) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "error" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

// Bind parameters and execute
$stmt->bind_param("siis", $post_id, $user, $comment_id, $reply);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Reply added successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Execute failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
exit;
?>
<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$story_id = $_POST['story_id'] ?? '';
$comment = $_POST['comment'] ?? '';
$user_id = $_SESSION["user_id"];

if (empty($story_id) || empty($comment)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO story_comments (story_id, user_id, comments) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $story_id, $user_id, $comment);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Comment saved"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save comment"]);
}
$stmt->close();
$conn->close();
?>

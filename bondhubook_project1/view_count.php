<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$post_id = $_POST['storyId'] ?? '';
if (empty($post_id)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

// Fetch current view count
$stmt = $conn->prepare("SELECT view_count FROM posts WHERE creator=?");
$stmt->bind_param("s", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["status" => "success", "view_count" => $row['view_count']]);
} else {
    echo json_encode(["status" => "error", "message" => "Post not found"]);
}

$stmt->close();
$conn->close();
?>

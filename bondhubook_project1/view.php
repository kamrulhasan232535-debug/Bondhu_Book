<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$post_id = $_POST['post_id'] ?? '';
$type = $_POST['post_type'] ?? '';
$user_id = $_SESSION["user_id"];

if (empty($post_id)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

// Check if this user has already viewed the post
$stmt1 = $conn->prepare("SELECT 1 FROM post_viewers WHERE post_id=? AND user_id=?");
$stmt1->bind_param("si", $post_id, $user_id);
$stmt1->execute();
$result = $stmt1->get_result();
if ($result->num_rows > 0) {
    echo json_encode(["status" => "success", "message" => "View already recorded"]);
    exit;
}
$stmt1->close();

// Insert new viewer record
$stmt = $conn->prepare("INSERT INTO post_viewers (post_id, user_id, post_type) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $post_id, $user_id, $type);
if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => "Failed to save view"]);
    exit;
}
$stmt->close();

// Get updated total views
$stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM post_viewers WHERE post_id=?");
$stmt2->bind_param("s", $post_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$row = $result2->fetch_assoc();
$total_views = $row['total'];
$stmt2->close();

// Update posts table with new view count
$stmt3 = $conn->prepare("UPDATE posts SET view_count=? WHERE creator=?");
$stmt3->bind_param("is", $total_views, $post_id);
$stmt3->execute();
$stmt3->close();

echo json_encode(["status" => "success", "message" => "View recorded", "total_views" => $total_views]);

$conn->close();
?>

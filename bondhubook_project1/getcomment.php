<?php
include "db.php";
header('Content-Type: application/json');

// Step 1: Get creator ID
$id = $_POST["creator"] ?? '';

if (empty($id)) {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

// Step 2: Find post_id from posts table
$stmnt = $conn->prepare("SELECT post_id FROM posts WHERE creator = ?");
$stmnt->bind_param("s", $id);
$stmnt->execute();
$result = $stmnt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "error" => "No post found for this creator"]);
    exit;
}

$post_id = $row["post_id"];

// Step 3: Fetch all comments for that post with user information
$sql = "SELECT 
            c.comment_id, 
            c.post_id, 
            c.comment_text, 
            c.created_at,
            u.id,
            u.name,
            u.profile_picture
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        "comment_id" => $row['comment_id'],
        "post_id" => $row['post_id'],
        "text" => $row['comment_text'],
        "timestamp" => $row['created_at'],
        "user" => [
            "id" => $row['id'],
            "name" => $row['name'],
            "profile_picture" => $row['profile_picture']
        ],
        "likes" => 0, // You'll need to fetch likes count separately
        "replies" => [] // You'll need to fetch replies separately
    ];
}

// Close connections
$stmt->close();
$conn->close();

echo json_encode([
    "success" => true, 
    "data" => $comments
]);
?>
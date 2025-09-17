<?php
header('Content-Type: application/json');
include "db.php";

// Get creator from GET
$creator = $_GET['creator'] ?? null;
$user_id = $_POST["user_id"] ?? null;

if (!$creator || !$user_id) {
    echo json_encode(['error' => 'creator or user_id missing']);
    exit;
}

// Step 1: Get like_count and post_id from posts table
$stmnt = $conn->prepare("SELECT like_count, post_id FROM posts WHERE creator = ?");
$stmnt->bind_param("s", $creator);
$stmnt->execute();
$result = $stmnt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'No post found']);
    exit;
}

$row = $result->fetch_assoc();
$like_count = $row['like_count'];
$post_id = $row['post_id'];  // now we have post_id from database

// Step 2: Use that post_id to find user's reaction
$stmnt1 = $conn->prepare("SELECT reaction FROM likes WHERE post_id = ? AND user_id = ?");
$stmnt1->bind_param("ii", $post_id, $user_id);
$stmnt1->execute();
$result1 = $stmnt1->get_result();

$isgiven = null;
if ($row1 = $result1->fetch_assoc()) {
    $isgiven = $row1["reaction"];
}

// Step 3: Return as JSON
echo json_encode([
    'like_count' => $like_count,
    'post_id' => $post_id,
    'isgiven' => $isgiven
]);
?>

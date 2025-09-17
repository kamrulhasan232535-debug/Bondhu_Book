<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';
header('Content-Type: application/json');

$userid  = $_POST['userid'] ?? null;
$creator = $_POST['creator'] ?? null;
$type    = $_POST['type'] ?? null;
$like    = isset($_POST['like']) ? (int)$_POST['like'] : null;

if (is_null($userid) || is_null($creator) || !in_array($like, [1, -1])) {
    echo json_encode(["success" => false, "message" => "Missing or invalid input"]);
    exit;
}

// Get post owner and post ID
$stmt = $conn->prepare("SELECT user_id, post_id FROM posts WHERE creator = ?");
$stmt->bind_param("s", $creator);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Post not found"]);
    exit;
}

$row = $result->fetch_assoc();
$post_owner_id = $row['user_id'];
$post_id       = $row['post_id'];
$like_change   = 0;

if ($like === 1) {
    // Insert or update the like
    $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id, reaction) 
        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reaction = VALUES(reaction)");
    $stmt->bind_param("iis", $post_id, $userid, $type);
    $stmt->execute();

    $is_duplicate = ($stmt->affected_rows === 2);

    // If not duplicate (i.e., new like), increase like count
    if (!$is_duplicate) {
        $like_change = 1;

        // Create notification if it's not your own post
        if ($post_owner_id != $userid) {
            $notif = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type) VALUES (?, ?, 'likes')");
            $notif->bind_param("ii", $post_owner_id, $userid);
            $notif->execute();
        }
    }

} elseif ($like === -1) {
    // Unlike: delete the like
    $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $userid);
    $stmt->execute();

    $like_change = -1;
}

// Update like count only if it really changed
if ($like_change !== 0) {
    $stmt = $conn->prepare("UPDATE posts SET like_count = like_count + ? WHERE creator = ?");
    $stmt->bind_param("is", $like_change, $creator);
    $stmt->execute();
}

// Get updated like count
$stmt = $conn->prepare("SELECT like_count FROM posts WHERE creator = ?");
$stmt->bind_param("s", $creator);
$stmt->execute();
$result = $stmt->get_result();
$countRow = $result->fetch_assoc();
$like_count = $countRow["like_count"] ?? 0;

// Final response
echo json_encode(["success" => true, "like_count" => $like_count]);
exit;

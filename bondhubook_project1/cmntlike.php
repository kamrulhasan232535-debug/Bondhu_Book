<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'] ?? null;
    $post_id = $_POST['post_id'] ?? null;
    $comment_id = $_POST['id'] ?? null;
    $reaction_type = $_POST['reaction_type'] ?? null;
    
    // Validate inputs
    if (!$user_id || !$post_id || !$comment_id || !$reaction_type) {
        throw new Exception("Missing required parameters");
    }

    $sql = "INSERT INTO cmntreactions (user_id, post_id, comment_id, reaction_type) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type), created_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param(
        "isis",  // user_id (int), post_id (int), comment_id (int), reaction_type (string)
        $user_id, 
        $post_id, 
        $comment_id, 
        $reaction_type
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    echo json_encode([
        "success" => true,
        "message" => "Reaction recorded successfully"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    exit;
}
?>
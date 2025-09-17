<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);
require_once "db.php";
header('Content-Type: application/json');

// Validate user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "User not logged in"]);
    exit;
}

// Validate required POST data
if (empty($_POST["post12"])) {
    echo json_encode(["success" => false, "error" => "No post content provided"]);
    exit;
}

// Sanitize and validate inputs
$user_id = (int)$_SESSION["user_id"];
$post_content = trim($_POST["post12"]);
$post_type = isset($_POST["post_type"]) ? trim($_POST["post_type"]) : 'text';
$creator = isset($_POST["creator"]) ? trim($_POST["creator"]) : '';
$caption=$_POST["caption"];
// Validate post content length
if (strlen($post_content) < 1 || strlen($post_content) > 5000) {
    echo json_encode(["success" => false, "error" => "Post content must be between 1 and 5000 characters"]);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Insert post
    $stmt = $conn->prepare("INSERT INTO posts (user_id, creator, post_content, post_type,caption) VALUES (?, ?, ?, ?,?)");
    $stmt->bind_param("issss", $user_id, $creator, $post_content, $post_type,$caption);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert post: " . $stmt->error);
    }
    
    // Insert notification if needed
    if (!empty($post_type) && $post_type !== 'text') {
       $stmnt3 = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type) VALUES (NULL, ?, ?)");
        $stmnt3->bind_param("is", $user_id, $post_type);
        
        if (!$stmnt3->execute()) {
            throw new Exception("Failed to insert notification: " . $stmnt3->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(["success" => true, "post_id" => $conn->insert_id]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
} finally {
    // Close statements
    if (isset($stmt)) $stmt->close();
    if (isset($stmnt3)) $stmnt3->close();
    $conn->close();
}
?>
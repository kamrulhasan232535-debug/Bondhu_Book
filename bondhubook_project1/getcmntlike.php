<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include "db.php";

try {
    // Get input data
    $post_id = $_POST['post_id'] ?? null;
    $comment_id = $_POST['id'] ?? null;
    
    // Validate inputs
    if (!$post_id || !$comment_id) {
        throw new Exception("Missing required parameters");
    }

    // Query to get reaction count and emoji for this comment
    $sql = "SELECT 
                COUNT(*) as count, 
                reaction_type as emoji
            FROM cmntreactions 
            WHERE post_id = ? AND comment_id = ?
            GROUP BY reaction_type
            ORDER BY count DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("si", $post_id, $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode([
            "success" => true,
            "count" => $data['count'],
            "emoji" => $data['emoji']
        ]);
    } else {
        // No reactions yet
        echo json_encode([
            "success" => true,
            "count" => 0,
            "emoji" => "👍" // Default emoji when no reactions
        ]);
    }

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
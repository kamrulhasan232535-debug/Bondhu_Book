<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $comment_id = $_POST['comment_id'] ?? null;

    // Validate input
    if (!$comment_id) {
        throw new Exception("Missing required parameter: comment_id");
    }

    // Prepare SQL statement to get replies with user information and like counts
    $sql = "SELECT 
                r.reply_id,
                r.reply,
                r.created_at,
                r.comment_id,
                r.post_id,
                u.id,
                u.name,
                u.profile_picture
            FROM reply r
            JOIN users u ON r.user_id = u.id
            WHERE r.comment_id = ? 
            ORDER BY r.created_at ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $comment_id);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $replies = [];

    while ($row = $result->fetch_assoc()) {
        $replies[] = [
            "replyid" => $row['reply_id'],
            "post_id" => $row['post_id'],
            "comment_id" => $row['comment_id'],
            "text" => htmlspecialchars($row['reply'], ENT_QUOTES, 'UTF-8'),
            "timestamp" => $row['created_at'],
            "user" => [
                "id" => $row['id'],
                "name" => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
                "profile_picture" => $row['profile_picture']
            ]
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $replies
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
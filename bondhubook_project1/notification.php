<?php
include "db.php";
session_start();
header('Content-Type: application/json');

if (empty($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];

try {
    // Fetch notifications with sender details
    $stmt = $conn->prepare("
        SELECT n.*, u.name as sender_name, u.profile_picture, u.id as sender_id
        FROM notifications n
        JOIN users u ON n.sender_id = u.id
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'sender_name' => $row['sender_name'],
            'sender_image' => $row['profile_picture'] ?: 'default-profile.png',
            'message' => getNotificationMessage($row['type']),
            'sender_id' => $row['sender_id'],
            'time' => $row['created_at']
        ];
    }

    // Count unread notifications
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $countStmt->bind_param("i", $user_id);
    $countStmt->execute();
    $count = $countStmt->get_result()->fetch_assoc()['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => (int)$count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

function getNotificationMessage($type) {
    switch ($type) {
        case 'likes': return 'liked your post';
        case 'comments': return 'commented on your post';
        case 'follow': return 'started following you';
        case 'request': return 'sent you a friend request';
        default: return 'sent you a notification';
    }
}
?>

<?php
header('Content-Type: application/json');

// Start output buffering to prevent accidental output
ob_start();

try {
    session_start();
    
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required', 401);
    }

    require 'db.php';

    // Validate input
    if (!isset($_POST['sender_id']) || !is_numeric($_POST['sender_id'])) {
        throw new Exception('Invalid sender ID', 400);
    }

    $sender_id = (int)$_POST['sender_id'];
    $recipient_id = $_SESSION['user_id'];

    // Verify the request exists before updating
    $checkStmt = $conn->prepare("SELECT id FROM friend_requests 
                                WHERE sender_id = ? AND recipient_id = ? 
                                AND status = 'pending'");
    $checkStmt->bind_param("ii", $sender_id, $recipient_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        throw new Exception('Friend request not found or already processed', 404);
    }

    // Update the request status
    $updateStmt = $conn->prepare("UPDATE friend_requests 
                                 SET status = 'accepted'
                                 WHERE sender_id = ? AND recipient_id = ?");
    $updateStmt->bind_param("ii", $sender_id, $recipient_id);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update friend request', 500);
    }
    $stmt = $conn->prepare("INSERT INTO friends(user_id,sender_id)VALUES(?,?)");
    $stmt->bind_param("ii", $recipient_id,$sender_id);
    $stmt->execute();
    $stmt1 = $conn->prepare("INSERT INTO messenger_friends(user_id,friend_id)VALUES(?,?)");
    $stmt1->bind_param("ii", $recipient_id,$sender_id);
    $stmt1->execute();
    echo json_encode([
        'success' => true,
        'message' => 'Friend request accepted successfully'
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
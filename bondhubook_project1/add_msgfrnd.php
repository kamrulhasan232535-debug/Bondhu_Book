<?php
header('Content-Type: application/json');

ob_start();

try {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required', 401);
    }

    require 'db.php';

    if (!isset($_POST['sender_id']) || !is_numeric($_POST['sender_id'])) {
        throw new Exception('Invalid sender ID', 400);
    }

    $sender_id = (int)$_POST['sender_id'];
    $recipient_id = $_SESSION['user_id'];

    // Check if friendship exists in either direction
    $checkStmt = $conn->prepare("
        SELECT 1 
        FROM messenger_friends 
        WHERE (user_id = ? AND friend_id = ?)
           OR (user_id = ? AND friend_id = ?)
        LIMIT 1
    ");
    $checkStmt->bind_param("iiii", $recipient_id, $sender_id, $sender_id, $recipient_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        throw new Exception('Already friends', 409);
    }

    // Insert new friendship
    $stmt1 = $conn->prepare("INSERT INTO messenger_friends(user_id, friend_id) VALUES (?, ?)");
    $stmt1->bind_param("ii", $recipient_id, $sender_id);
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

<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['whom'])) {
    echo json_encode(['success' => false, 'message' => 'No recipient specified']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$recipient_id = $_POST['whom'];

// Check if request already exists
$stmt = $conn->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND recipient_id = ?");
$stmt->bind_param("ii", $sender_id, $recipient_id);
$stmt->execute();
$stmt->store_result();
$stmnt3= $conn->prepare("INSERT INTO notifications (user_id,sender_id,type) VALUES (?, ?, 'request')");
$stmnt3->bind_param("ii", $recipient_id, $sender_id);
$stmnt3->execute();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Request already sent']);
    exit;
}

// Insert new request
$stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, recipient_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $sender_id, $recipient_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Friend request sent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
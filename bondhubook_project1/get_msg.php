<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Authentication required"]);
    exit;
}

$receiver = (int)$_POST["receiver"];
$user_id = $_SESSION["user_id"];

// Get ALL messages between these two users
$stmnt = $conn->prepare("
    SELECT user_id, receiver_id, type, content, created_at 
    FROM messages 
    WHERE (receiver_id=? AND user_id=?) 
       OR (receiver_id=? AND user_id=?)
    ORDER BY created_at ASC
");
$stmnt->bind_param("iiii", $receiver, $user_id, $user_id, $receiver);
$stmnt->execute();
$result = $stmnt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'sender' => $row['user_id'] == $user_id ? 'me' : 'other',
        'type' => $row['type'],
        'content' => $row['content'],
        'time' => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
]);
?>

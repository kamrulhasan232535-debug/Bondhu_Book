<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmnt4 = $conn->prepare("
    SELECT story_id, image_url, video_url, audio_url, text_content
    FROM story
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmnt4->bind_param("i", $user_id);
$stmnt4->execute();
$result4 = $stmnt4->get_result();

if ($row = $result4->fetch_assoc()) {
    echo json_encode([
        'story_id' => $row['story_id'],
        'image'    => $row['image_url'],
        'video'    => $row['video_url'],
        'audio'    => $row['audio_url'],
        'text'     => $row['text_content'],
        'user_id'  => $user_id
    ]);
} else {
    echo json_encode(['error' => 'No stories found']);
}

$stmnt4->close();
$conn->close();

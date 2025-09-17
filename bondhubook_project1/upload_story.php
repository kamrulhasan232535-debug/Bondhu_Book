<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

$response = ['success' => false, 'error' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];
    $uploadDir = 'uploads/stories/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $hasContent = false;
    $textContent = null;
    $imageUrl = null;
    $videoUrl = null;
    $audioUrl = null;

    // Process text content
    if (!empty($_POST['text_content'])) {
        $textContent = trim($_POST['text_content']);
        $hasContent = true;
    }

    // Process media upload
    if (!empty($_FILES['media']['tmp_name'])) {
        $file = $_FILES['media'];
        $fileType = mime_content_type($file['tmp_name']);
        $filename = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $hasContent = true;
            if (strpos($fileType, 'image/') === 0) {
                $imageUrl = $targetPath;
            } elseif (strpos($fileType, 'video/') === 0) {
                $videoUrl = $targetPath;
            }
        }
    }

    // Process audio upload
    if (!empty($_FILES['audio']['tmp_name'])) {
        $file = $_FILES['audio'];
        $filename = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $hasContent = true;
            $audioUrl = $targetPath;
        }
    }

    if (!$hasContent) {
        throw new Exception('Please add at least one content type');
    }

    $stmt = $conn->prepare("INSERT INTO story 
        (user_id, image_url, video_url, audio_url, text_content) 
        VALUES (?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issss", $user_id, $imageUrl, $videoUrl, $audioUrl, $textContent);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Story posted successfully';
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>
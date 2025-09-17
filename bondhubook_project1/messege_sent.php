<?php
session_start();
include "db.php";
header('Content-Type: application/json');
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Authentication required"]);
    exit;
}
if (!isset($_POST['receiver']) || !is_numeric($_POST['receiver'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid receiver"]);
    exit;
}

$user_id = $_SESSION["user_id"];
$receiver_id = (int)$_POST['receiver'];
$baseDir = "uploads/";
$userDir = $baseDir . "conversation_" . min($user_id, $receiver_id) . "_" . max($user_id, $receiver_id) . "/";

// Create directory if needed
if (!file_exists($userDir)) {
    if (!mkdir($userDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to create directory"]);
        exit;
    }
}
if (isset($_POST['type']) && $_POST['type'] == "text") {
    $message = trim($_POST['content'] ?? '');
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Message cannot be empty"]);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, type, content, receiver_id) VALUES (?, 'text', ?, ?)");
        $stmt->bind_param("isi", $user_id, $message, $receiver_id);
        $stmt->execute();
        echo json_encode(["status" => "success", "message_id" => $stmt->insert_id]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "error" => "Database error"]);
    }
    exit;
}

// Handle file uploads
if (isset($_FILES['content'])) {
    $file = $_FILES['content'];
    $type = $_POST['type'] ?? '';
    
    // Validate file type
    $allowedTypes = ['image', 'video', 'audio'];
    if (!in_array($type, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid file type"]);
        exit;
    }
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "File upload error"]);
        exit;
    }
    
    // Generate safe filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $targetFile = $userDir . $filename;
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Store relative path
        $relativePath = "conversation_" . min($user_id, $receiver_id) . "_" . max($user_id, $receiver_id) . "/" . $filename;
        
        try {
            $stmt = $conn->prepare("INSERT INTO messages (user_id, type, content, receiver_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $user_id, $type, $relativePath, $receiver_id);
            $stmt->execute();
            echo json_encode(["status" => "success", "file" => $relativePath]);
        } catch (Exception $e) {
            unlink($targetFile); // Clean up
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error"]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "File move failed"]);
    }
    exit;
}

http_response_code(400);
echo json_encode(["status" => "error", "message" => "Invalid request"]);
?>
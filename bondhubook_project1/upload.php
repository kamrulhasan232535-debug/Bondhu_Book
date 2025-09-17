<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    die(json_encode(['error' => 'Not logged in']));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profile_image'])) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['error' => 'Invalid request']));
}
$target_dir = "uploads/";
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; 
if (!file_exists($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        header('HTTP/1.1 500 Internal Server Error');
        die(json_encode(['error' => 'Failed to create upload directory']));
    }
}
$file = $_FILES['profile_image'];
$file_type = mime_content_type($file['tmp_name']);
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_type, $allowed_types)) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['error' => 'Only JPG, PNG, and GIF images are allowed']));
}

// Check file size
if ($file['size'] > $max_size) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['error' => 'File size exceeds 5MB limit']));
}

$user_id = $_SESSION['user_id'];
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
$target_file = $target_dir . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $target_file)) {
    header('HTTP/1.1 500 Internal Server Error');
    die(json_encode(['error' => 'Error uploading file']));
}
try {
    $media_type = "image";
    $media_url = $target_file;

    $stmt = $conn->prepare("INSERT INTO user_media (user_id, media_type, media_url, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $media_type, $media_url);
    
    if (!$stmt->execute()) {
        // If DB insert fails, delete the uploaded file
        unlink($target_file);
        throw new Exception("Database insert failed");
    }

    // Update user's profile picture reference
    $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $update_stmt->bind_param("si", $media_url, $user_id);
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update profile picture");
    }
    $update_stmt->close();

    // Return success response
    echo json_encode([
        'success' => true,
        'imageUrl' => $media_url,
        'message' => 'Profile image uploaded successfully'
    ]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => $conn->error ?? null
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>
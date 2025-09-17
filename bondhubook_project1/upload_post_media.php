<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['media_files']) || empty($_FILES['media_files']['name'][0])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
    exit;
}

// Optional caption
$caption = isset($_POST['post12']) ? trim($_POST['post12']) : null;

// Upload configuration
$target_dir = 'uploads/';
$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
$max_size = 1024 * 1024 * 1024; // 1GB

// Ensure directory exists
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
        exit;
    }
}

$response = [];
$conn->begin_transaction();

try {
    foreach ($_FILES['media_files']['tmp_name'] as $index => $tmp_name) {
        $original_name = $_FILES['media_files']['name'][$index];
        $file_size = $_FILES['media_files']['size'][$index];
        $tmp_path = $_FILES['media_files']['tmp_name'][$index];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $mime_type = mime_content_type($tmp_path) ?: $_FILES['media_files']['type'][$index];

        $is_image = in_array($mime_type, $allowed_image_types);
        $is_video = in_array($mime_type, $allowed_video_types);

        if (!$is_image && !$is_video) {
            $response[] = ['success' => false, 'error' => "Invalid file type: $original_name"];
            continue;
        }

        if ($file_size > $max_size) {
            $response[] = ['success' => false, 'error' => "File too large: $original_name"];
            continue;
        }

        $media_type = $is_image ? 'image' : 'video';
        $new_filename = $media_type . '_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
        $destination = $target_dir . $new_filename;

        if (!move_uploaded_file($tmp_path, $destination)) {
            $response[] = ['success' => false, 'error' => "Failed to save file: $original_name"];
            continue;
        }

        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO user_media (user_id, media_type, media_url, caption, created_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("isss", $user_id, $media_type, $destination, $caption);
        if (!$stmt->execute()) {
            unlink($destination); // Cleanup
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $response[] = [
            'success' => true,
            'url' => $destination,
            'media_type' => $media_type
        ];

        $stmt->close();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
    exit;
}

// Final JSON response
echo json_encode([
    'success' => count(array_filter($response, fn($r) => $r['success'])) > 0,
    'results' => $response
]);

$conn->close();
?>

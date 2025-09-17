<?php
// Enable strict error reporting
declare(strict_types=1);

// Start output buffering and set headers
ob_start();
header('Content-Type: application/json');

// Error handling setup
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'],
            'debug' => [
                'file' => $error['file'],
                'line' => $error['line']
            ]
        ]));
    }
});

try {
    // Start session
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false, // Change to true in HTTPS
        'use_strict_mode' => true
    ]);

    if (!isset($_SESSION['user_id'])) {
        throw new RuntimeException('Authentication required', 401);
    }

    require 'db.php';

    if (!$conn || $conn->connect_error) {
        throw new RuntimeException('Database connection failed', 500);
    }

    $conn->set_charset("utf8mb4");

    // Check required tables
    $tables = ['friend_requests', 'users'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$result || $result->num_rows === 0) {
            throw new RuntimeException("Required table '$table' not found", 500);
        }
    }

    $query = "SELECT fr.id, fr.sender_id, fr.status, fr.created_at, 
              u.name, u.profile_picture
              FROM friend_requests fr
              JOIN users u ON fr.sender_id = u.id
              WHERE fr.recipient_id = ? AND fr.status = 'pending'
              ORDER BY fr.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];

    while ($row = $result->fetch_assoc()) {
        $requests[] = [
            'id' => (int)$row['id'],
            'sender_id' => (int)$row['sender_id'],
            'status' => $row['status'],
            'name' => $row['name'],
            'profile_picture' => $row['profile_picture'],
            'created_at' => $row['created_at']
        ];
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'requests' => $requests,
            'count' => count($requests)
        ]
    ]);

} catch (RuntimeException $e) {
    ob_end_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 500
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    exit;
}

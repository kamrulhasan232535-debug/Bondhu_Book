<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$userid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

try {
    $stmt_followers = $conn->prepare("SELECT COUNT(*) as total FROM friends WHERE user_id = ?");
    $stmt_followers->bind_param("i", $userid);
    $stmt_followers->execute();
    $followers = $stmt_followers->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt_following = $conn->prepare("SELECT COUNT(*) as total FROM friends WHERE sender_id = ?");
    $stmt_following->bind_param("i", $userid);
    $stmt_following->execute();
    $following = $stmt_following->get_result()->fetch_assoc()['total'] ?? 0;

    echo json_encode([
        "status" => "success",
        "follower_count" => (int)$followers,
        "following_count" => (int)$following,
        "total_connections" => (int)$followers + (int)$following
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>

<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
include 'db.php';

$response = ["status" => "error", "message" => "Unknown error"];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$userid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

try {
    // Followers
    $stmt_followers = $conn->prepare("
        SELECT u.id, u.name, u.profile_picture 
        FROM friends f 
        JOIN users u ON f.sender_id = u.id 
        WHERE f.user_id = ?
    ");
    $stmt_followers->bind_param("i", $userid);
    $stmt_followers->execute();
    $followers_result = $stmt_followers->get_result();
    $followers = $followers_result->fetch_all(MYSQLI_ASSOC);

    // Following
    $stmt_following = $conn->prepare("
        SELECT u.id, u.name, u.profile_picture 
        FROM friends f 
        JOIN users u ON f.user_id = u.id 
        WHERE f.sender_id = ?
    ");
    $stmt_following->bind_param("i", $userid);
    $stmt_following->execute();
    $following_result = $stmt_following->get_result();
    $following = $following_result->fetch_all(MYSQLI_ASSOC);

    // Merge all friends
    $all_friends = array_merge($followers, $following);

    echo json_encode([
        "status" => "success",
        "total_connections" => count($all_friends),
        "friends" => $all_friends
    ]);
    exit;

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Unable to fetch friend details"]);
    exit;
}
?>
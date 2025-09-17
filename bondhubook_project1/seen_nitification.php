<?php
include "db.php";
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];

try {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Update failed"]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
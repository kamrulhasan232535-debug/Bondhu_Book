<?php 
include "db.php";
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$id = $_POST["iduser"] ?? null;
if (!$id) {
    echo json_encode(["status" => "error", "message" => "Missing user ID"]);
    exit;
}

$userStmnt = $conn->prepare("SELECT name, profile_picture, id FROM users WHERE id = ?");
$userStmnt->bind_param("i", $id);
$userStmnt->execute();
$userResult = $userStmnt->get_result();

if ($userRow = $userResult->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "friends" => [
            'name' => $userRow["name"],
            'avatar' => $userRow["profile_picture"],
            'other_id' => $userRow["id"]  // Using the actual ID from database
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "User not found"]);
}
?>
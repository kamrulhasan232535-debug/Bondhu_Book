<?php
header("Content-Type: application/json");
session_start();

include "db.php"; // Make sure this file defines $conn = new mysqli(...)

$query = $_POST["query"] ?? "";

// Protect against empty search
if (trim($query) === "") {
    echo json_encode(["users" => []]);
    exit;
}

$current_user_id = $_SESSION['user_id'] ?? 0;

$sql = "SELECT name, profile_picture, id FROM users WHERE name LIKE ? AND id != ?";
$stmt = $conn->prepare($sql);
$searchTerm = "%$query%";
$stmt->bind_param("si", $searchTerm, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];

while ($row = $result->fetch_assoc()) {
    $other_user_id = $row['id'];

    // Check friend request status
    $statusStmt = $conn->prepare("
        SELECT status, sender_id, recipient_id 
        FROM friend_requests 
        WHERE (sender_id = ? AND recipient_id = ?) 
           OR (sender_id = ? AND recipient_id = ?)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $statusStmt->bind_param("iiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();

    $status = "none"; // default status

    if ($statusRow = $statusResult->fetch_assoc()) {
        if ($statusRow['status'] === "accepted") {
            $status = "Friends";
        } elseif ($statusRow['status'] === "pending") {
            if ((int)$statusRow['sender_id'] === $current_user_id) {
                $status = "Request Sent";
            } else {
                $status = "Request Received";
            }
        }else{
            $status = "Add Friend";
        }
    }else{
        $status = "Add Friend";
    }

    $users[] = [
        "name" => $row["name"],
        "img" => $row["profile_picture"] ?: "icon/user.png",
        "id" => $row["id"],
        "status" => $status,
        "others_user_id"=>$other_user_id
    ];
}

echo json_encode(["users" => $users]);
?>

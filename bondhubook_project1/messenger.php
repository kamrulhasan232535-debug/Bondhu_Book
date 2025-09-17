<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION["user_id"];

try {
    // Prepare statement to get all friends (both where user is user_id OR friend_id)
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN user_id = ? THEN friend_id 
                WHEN friend_id = ? THEN user_id 
            END as friend_id,
            u.name as friend_name,
            u.profile_picture as friend_avatar
        FROM 
            messenger_friends mf
        JOIN 
            users u ON (mf.user_id = u.id OR mf.friend_id = u.id) AND u.id != ?
        WHERE 
            (mf.user_id = ? OR mf.friend_id = ?)
            AND mf.user_id != mf.friend_id
    ");
    
    $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $friends = [];
    while ($row = $result->fetch_assoc()) {
        $friends[] = [
            'id' => $row['friend_id'],
            'name' => $row['friend_name'],
            'avatar' => $row['friend_avatar']
        ];
    }
    $friends=array_reverse($friends);
    echo json_encode([
        'success' => true,
        'friends' => $friends,
        'count' => count($friends)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
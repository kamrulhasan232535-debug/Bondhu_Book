<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$userid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

try {
    // Prepare statement
    $stmtUser = $conn->prepare("SELECT media_url FROM user_media WHERE user_id = ? AND media_type = 'video'");
    $stmtUser->bind_param("i", $userid);
    $stmtUser->execute();
    $result = $stmtUser->get_result();

    $vedios = [];
    while ($row = $result->fetch_assoc()) {
        $vedios[] = $row['media_url'];
    }

    echo json_encode([
        "status" => "success",
        "videos" => $vedios
    ]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        "status" => "error", 
        "message" => "Unable to fetch vedios"
    ]);
}

$stmtUser->close();
$conn->close();
?>

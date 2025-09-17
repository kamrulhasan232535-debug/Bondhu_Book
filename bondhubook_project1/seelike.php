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
$id = $_POST["id"] ?? null;

if (!$id) {
    echo json_encode(['error' => 'post_id missing']);
    exit;
}$stmnt2 = $conn->prepare("SELECT post_id FROM posts WHERE creator = ?");
$stmnt2->bind_param("s", $id);
$stmnt2->execute();
$result2 = $stmnt2->get_result();
$row3=$result2->fetch_assoc();
$stmnt = $conn->prepare("SELECT user_id, reaction FROM likes WHERE post_id = ?");
$stmnt->bind_param("i", $row3["post_id"]);
$stmnt->execute();
$result = $stmnt->get_result();

$likers = [];

while ($row = $result->fetch_assoc()) {
    $user_id = $row["user_id"];
    $reaction = $row["reaction"];

    $userStmnt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
    $userStmnt->bind_param("i", $user_id);
    $userStmnt->execute();
    $userResult = $userStmnt->get_result();

    if ($userRow = $userResult->fetch_assoc()) {
        $likers[] = [
            'name' => $userRow["name"],
            'profile_picture' => $userRow["profile_picture"],
            'other_id'=>$user_id,
            'reaction' => $reaction
        ];
    }
}

if (empty($likers)) {
    echo json_encode(['error' => 'No likes found']);
    exit;
}

echo json_encode($likers);
?>

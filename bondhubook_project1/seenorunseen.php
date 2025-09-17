<?php
include "db.php";
session_start();

header('Content-Type: application/json'); // Force JSON header
error_reporting(0); // Warning/notice hide for JSON safety

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION["user_id"];
$sender = $_POST["sender"] ?? null;

if (!$sender) {
    echo json_encode(["error" => "Sender not provided"]);
    exit;
}

$stmnt = $conn->prepare("SELECT is_read FROM messages WHERE receiver_id=? AND user_id=?");
$stmnt->bind_param("ii", $user_id, $sender);
$stmnt->execute();
$result = $stmnt->get_result();

$count = 0;
while ($row = $result->fetch_assoc()) {
    if ($row["is_read"] ==NULL) {
        $count++;
    }
}

echo json_encode(["data" => $count]);
exit;
?>

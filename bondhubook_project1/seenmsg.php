<?php
include "db.php";
session_start();

header('Content-Type: application/json');
error_reporting(0);

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

$stmnt = $conn->prepare("UPDATE messages SET is_read='yes' WHERE receiver_id=? AND user_id=?");
$stmnt->bind_param("ii", $user_id, $sender);

if ($stmnt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmnt->error]);
}

$stmnt->close();
$conn->close();
?>

<?php
session_start();
header('Content-Type: application/json');
require 'db.php'; // DB connect

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmnt2 = $conn->prepare("
    SELECT media_url, caption, id,media_type
    FROM user_media 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmnt2->bind_param("i", $user_id);
$stmnt2->execute();
$result = $stmnt2->get_result();

if ($row = $result->fetch_assoc()) {
    $media_url = $row["media_url"];
    $caption   = $row["caption"];
    $id        = (int)$row["id"];

    $extension = strtolower(pathinfo($media_url, PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg','jpeg','png','gif','webp'])) {
        $type = "image";
    } elseif (in_array($extension, ['mp4','webm','mov','avi'])) {
        $type = "video";
    } else {
        $type = "unknown";
    }


    echo json_encode([
        "id"        => $id,
        "caption"   => $caption,
        "media_url" => $media_url,
        "type"      => $type
    ]);
} else {
    echo json_encode(["message" => "No media found"]);
}

$stmnt2->close();

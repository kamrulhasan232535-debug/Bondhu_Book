<?php
include "db.php";
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// Fetch posts
$stmnt3 = $conn->prepare("SELECT post_type, post_content,expired_at FROM posts");
if (!$stmnt3) {
    echo json_encode(["success" => false, "message" => "DB prepare failed"]);
    exit;
}

$stmnt3->execute();
$result3 = $stmnt3->get_result();
$current_time = date('Y-m-d H:i:s');
$allpost = $vedioarray = $story = [];
while ($row = $result3->fetch_assoc()) {
    if ($row["post_type"] != "story") {
        $allpost[] = $row["post_content"];
    }
    if ($row['post_type'] == "video") {
        $vedioarray[] = $row['post_content'];
    }
    if ($row['post_type'] == "story") {
        if($row["expired_at"]>$current_time){
             $story[] = $row['post_content'];
        }
       
    }
}

$joinedVedioHTML = implode("", array_reverse($vedioarray));
$joinedStoryHTML = implode("", array_reverse($story));
$joinedPostHTML = implode("", array_reverse($allpost));

echo json_encode(["success" => true, "html" => $joinedPostHTML,
"story"=>$joinedStoryHTML,
"vedios"=>$joinedVedioHTML,]);
?>
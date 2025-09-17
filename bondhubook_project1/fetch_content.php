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

$userid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Step 1: Fetch all posts
$stmnt3 = $conn->prepare("SELECT  post_content,post_type FROM posts WHERE user_id=?");
$stmnt3->bind_param("i",$userid);
$stmnt3->execute();
$result3 = $stmnt3->get_result();

// Step 2: Prepare categorized arrays
$allpost = [];
$vedioarray = [];
$story = [];

if ($result3->num_rows > 0) {
    while ($row = $result3->fetch_assoc()) {
        $postType = $row["post_type"];
        $content = $row["post_content"];

        if ($postType !== "story") {
            $allpost[] = $content;
        }
        if ($postType === "video") {
            $vedioarray[] = $content;
        }
        if ($postType === "story") {
            $story[] = $content;
        }
    }
}

// Step 3: Reverse and Join HTML content
$joinedVedioHTML = implode("", array_reverse($vedioarray));
$joinedStoryHTML = implode("", array_reverse($story));
$joinedPostHTML = implode("", array_reverse($allpost));

// Step 4: Return JSON response
echo json_encode([
    "success" => true,
    "videos" => $joinedVedioHTML,
    "stories" => $joinedStoryHTML,
    "posts" => $joinedPostHTML
]);
?>

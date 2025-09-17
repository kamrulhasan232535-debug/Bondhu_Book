<?php
include "db.php";
header('Content-Type: application/json');

// Step 1: Receive POST
$id      = $_POST["creator"] ?? '';
$comment = $_POST["commenttext"] ?? '';
$userid  = $_POST["user_id"] ?? '';
$div     = $_POST["commentdiv"] ?? '';
// Step 1.5: Validate input
if (empty($id) || empty($userid)) {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

// Step 2: Find post_id using creator
$stmnt = $conn->prepare("SELECT post_id ,user_id FROM posts WHERE creator = ?");
$stmnt->bind_param("s", $id);
$stmnt->execute();
$result = $stmnt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "error" => "No post found for this creator"]);
    exit;
}

$post_id = $row["post_id"];

// Step 3: Begin transaction
$conn->begin_transaction();

try {
    // Insert comment
    $stmnt1 = $conn->prepare("INSERT INTO comments(post_id, comment_text, user_id) VALUES (?, ?, ?)");
    $stmnt1->bind_param("isi", $post_id, $comment, $userid);
    $stmnt1->execute();

    // Check if comment_div already exists
    $stmnt3 = $conn->prepare("SELECT comment_div FROM commentsupdate WHERE post_id = ?");
    $stmnt3->bind_param("i", $post_id);
    $stmnt3->execute();
    $result = $stmnt3->get_result();
    $stmnt5= $conn->prepare("INSERT INTO notifications (user_id,sender_id,type) VALUES (?, ?, 'comments')");
    $stmnt5->bind_param("ii", $row["user_id"], $userid);
    $stmnt5->execute();
    if ($result->num_rows > 0) {
        // Update existing comment_div
        $stmt4 = $conn->prepare("UPDATE commentsupdate SET comment_div = ? WHERE post_id = ?");
        $stmt4->bind_param("si", $div, $post_id);
        $stmt4->execute();
    } else {
        // Insert new comment_div
        $stmnt2 = $conn->prepare("INSERT INTO commentsupdate(post_id, comment_div) VALUES (?, ?)");
        $stmnt2->bind_param("is", $post_id, $div);
        $stmnt2->execute();
    }

    // Increment comment count
    $stmt5 = $conn->prepare("UPDATE posts SET comment_count = comment_count + 1 WHERE creator = ?");
    $stmt5->bind_param("s", $id);
    $stmt5->execute();
    if (!$row) {
    echo json_encode(["success" => false, "error" => "No post found for this creator", "creator" => $id]);
    exit;
}

    // Finalize transaction
    $conn->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>

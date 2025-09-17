<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

$story_id = $_POST['story_id'];
$reaction = $_POST['reaction'];
$user_id = $_SESSION["user_id"];
$stmt1 = $conn->prepare("SELECT user_id FROM story_reactions  WHERE story_id=? ");
$stmt1->bind_param("s", $story_id);
$stmt1->execute();
$result=$stmt1->get_result();
if($row=$result->fetch_assoc()>0){
    exit;
}
$stmt = $conn->prepare("INSERT INTO story_reactions (story_id, user_id, reaction_type) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $story_id, $user_id, $reaction);
$stmt->execute();

echo json_encode(["status" => "success", "message" => "Reaction saved"]);
?>

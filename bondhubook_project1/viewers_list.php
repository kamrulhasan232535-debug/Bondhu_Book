<?php
header('Content-Type: application/json');

try {
    include 'db.php';
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $storyId = $_POST['storyId'] ?? null;
    if (!$storyId) {
        throw new Exception("Story ID missing");
    }

    // Get viewer user IDs
    $stmt = $conn->prepare("SELECT user_id FROM post_viewers WHERE post_id = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $storyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $viewers = [];
    while ($row = $result->fetch_assoc()) {
        $viewers[] = $row['user_id'];
    }
    $stmt->close();

    $viewerData = [];
    foreach ($viewers as $userId) {
        // Get user info
        $stmtUser = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
        $stmtUser->bind_param("i", $userId);
        $stmtUser->execute();
        $user = $stmtUser->get_result()->fetch_assoc();
        $stmtUser->close();

        // Get reaction
        $stmtReact = $conn->prepare("SELECT reaction_type FROM story_reactions WHERE story_id = ? AND user_id = ?");
        $stmtReact->bind_param("si", $storyId, $userId);
        $stmtReact->execute();
        $reaction = $stmtReact->get_result()->fetch_assoc()['reaction_type'] ?? '';
        $stmtReact->close();

        // Get comment
        $stmtComment = $conn->prepare("SELECT comments FROM story_comments WHERE story_id = ? AND user_id = ?");
        $stmtComment->bind_param("si", $storyId, $userId);
        $stmtComment->execute();
        $comment = $stmtComment->get_result()->fetch_assoc()['comments'] ?? '';
        $stmtComment->close();

        $viewerData[] = [
            'name' => $user['name'] ?? 'Unknown',
            'profile_picture' => $user['profile_picture'] ?? '',
            'reaction' => $reaction,
            'comment' => $comment
        ];
    }

    echo json_encode([
        "status" => "success",
        "datastory" => $viewerData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
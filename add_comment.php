<?php
session_start();
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if(!isset($data['video_id']) || !isset($data['comment'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing video ID or comment']);
    exit;
}

$video_id = $data['video_id'];
$comment = trim($data['comment']);
$user_id = $_SESSION['user_id'];

// Check if comment is empty
if(empty($comment)) {
    echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty']);
    exit;
}

// Check if video exists
try {
    $stmt = $pdo->prepare("SELECT id FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    
    if($stmt->rowCount() == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Video not found']);
        exit;
    }
    
    // Add comment
    $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$video_id, $user_id, $comment]);
    
    echo json_encode(['status' => 'success', 'message' => 'Comment added successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

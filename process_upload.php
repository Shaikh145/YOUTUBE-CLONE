<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Process video upload
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if title is provided
    $title = trim($_POST['title']);
    if(empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Video title is required']);
        exit;
    }
    
    // Get description
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Check if file is uploaded
    if(!isset($_FILES['video']) || $_FILES['video']['error'] != UPLOAD_ERR_OK) {
        $error_message = '';
        switch($_FILES['video']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'File too large. Maximum size is 100MB.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded.';
                break;
            default:
                $error_message = 'Unknown error occurred during upload.';
        }
        echo json_encode(['status' => 'error', 'message' => $error_message]);
        exit;
    }
    
    // Check file size (100MB = 100 * 1024 * 1024 bytes)
    if($_FILES['video']['size'] > 100 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File size exceeds 100MB limit']);
        exit;
    }
    
    // Check file type
    $allowed_types = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-matroska'];
    if(!in_array($_FILES['video']['type'], $allowed_types)) {
        echo json_encode(['status' => 'error', 'message' => 'Only MP4, AVI, MOV, and MKV video formats are allowed']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    if(!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '_' . $_FILES['video']['name'];
    $upload_path = 'uploads/' . $filename;
    
    // Move uploaded file
    if(move_uploaded_file($_FILES['video']['tmp_name'], $upload_path)) {
        try {
            // Insert video information into database
            $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, description, file_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $upload_path]);
            
            $video_id = $pdo->lastInsertId();
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Video uploaded successfully!',
                'video_id' => $video_id
            ]);
        } catch(PDOException $e) {
            // Delete the uploaded file if database insertion fails
            @unlink($upload_path);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save the uploaded file']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
